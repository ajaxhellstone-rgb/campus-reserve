<?php
// ...existing code...
use App\Models\Reservation;
use App\Models\Facility;
use App\Models\User;
use App\Notifications\ReservationPendingApproval;
use Illuminate\Support\Facades\Notification;
// Calendar management subpage (not in navbar)
Route::middleware('auth')->get('/calendar/manage', function () {
    return view('calendar-manage');
});
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\DebugController;
// API endpoint for facility dropdown
Route::get('/api/facilities', [FacilityController::class, 'list']);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatbotController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home page
Route::get('/', function () {
    // Public landing page for visitors and signed-out users.
    return view('welcome');
})->name('home');


use App\Http\Controllers\CalendarController;
// Static pages and protected views that require authentication.
Route::middleware('auth')->group(function () {
    Route::post('/api/chatbot', [ChatbotController::class, 'respond'])->name('chatbot.respond');
    Route::post('/api/chatbot/reset', [ChatbotController::class, 'reset'])->name('chatbot.reset');
    Route::get('/calendar', function () {
        return view('calendar');
    })->name('calendar');

    Route::get('/api/calendar/events', [CalendarController::class, 'events']);
    Route::get('/api/calendar/pending', [CalendarController::class, 'pending']);
    Route::get('/api/facilities/{facility}/item-availability', [CalendarController::class, 'itemAvailability'])
        ->name('api.facilities.items.availability');
});

Route::middleware('auth')->post('/api/calendar/reserve', [CalendarController::class, 'store']);

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Guest only)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Guest-only authentication routes are accessible only when the user is not logged in.
    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');

    // Register
    Route::get('/signup', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/signup', [AuthController::class, 'register'])->middleware('throttle:5,1');

    // Google OAuth
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

// Admin secret login
Route::middleware('guest')->group(function () {
    Route::get('/admin-secret', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin-secret', [AuthController::class, 'loginAdmin'])->middleware('throttle:5,1');
});

// 2FA routes (after initial password validation)
Route::middleware('web')->group(function () {
    Route::get('/2fa', [AuthController::class, 'show2faVerify'])->name('2fa.show');
    Route::post('/2fa/verify', [AuthController::class, 'verify2fa'])->name('2fa.verify')->middleware('throttle:5,1');
    Route::post('/2fa/resend', [AuthController::class, 'resend2fa'])->name('2fa.resend')->middleware('throttle:3,1');
    Route::get('/admin-2fa', [AuthController::class, 'showAdmin2fa'])->name('admin.2fa');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Authenticated user routes. All of these pages require the user to be signed in.
    // Reserve page (Dashboard) - requires login
    Route::get('/reserve', function () {
        $user = auth()->user();
        $reservations = Reservation::with(['facility', 'items'])
            ->where('user_id', $user->id)
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->get();

        $upcomingCount = $reservations->filter(function ($reservation) {
            return in_array($reservation->status, ['pending', 'approved'])
                && $reservation->reservation_date >= now()->toDateString();
        })->count();

        $completedCount = $reservations->filter(function ($reservation) {
            return $reservation->status === 'completed'
                || $reservation->reservation_date < now()->toDateString();
        })->count();

        $facilities = Facility::active()->with('items')->orderBy('name')->get();

        return view('reserve', compact('reservations', 'upcomingCount', 'completedCount', 'facilities'));
    })->name('reserve');

    Route::get('/reserve/reservations/{reservation}/edit', function (Reservation $reservation) {
        $reservation = Reservation::with('facility')
            ->where('user_id', auth()->id())
            ->findOrFail($reservation->id);

        $facilities = Facility::active()->orderBy('name')->get();

        return view('reservations.edit', compact('reservation', 'facilities'));
    })->name('reservations.edit');

    Route::put('/reserve/reservations/{reservation}', function (Illuminate\Http\Request $request, Reservation $reservation) {
        $reservation = Reservation::where('user_id', auth()->id())->findOrFail($reservation->id);

        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'purpose' => 'required|string|max:255',
            'reservation_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:500',
        ]);

        $facility = Facility::findOrFail($request->facility_id);
        if ($reservation->items()->exists() && $facility->id !== $reservation->facility_id) {
            return back()->withInput()->with('error', 'Property reservation items must remain assigned to their original facility. Cancel this reservation and create a new one to reserve items in another facility.');
        }

        if (!$facility->isAvailable($request->reservation_date, $request->start_time, $request->end_time, $reservation->id)) {
            return back()->withInput()->with('error', 'Selected time slot is unavailable. Please choose another slot.');
        }

        $reservation->update([
            'facility_id' => $facility->id,
            'reservation_date' => $request->reservation_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        $admins = User::where('is_admin', true)
            ->orWhere('is_super_admin', true)
            ->get();
        Notification::send($admins, new ReservationPendingApproval($reservation));

        return redirect()->route('reserve')->with('success', 'Reservation updated and resubmitted for approval.');
    })->name('reservations.update');

    Route::delete('/reserve/reservations/{reservation}', function (Reservation $reservation) {
        $reservation = Reservation::where('user_id', auth()->id())->findOrFail($reservation->id);
        $reservation->delete();

        return redirect()->route('reserve')->with('success', 'Reservation removed successfully.');
    })->name('reservations.destroy');

    Route::post('/reserve/reservations/delete-selected', function (Illuminate\Http\Request $request) {
        $request->validate([
            'selected_reservations' => 'array',
            'selected_reservations.*' => 'integer|exists:reservations,id',
        ]);

        $selectedIds = $request->input('selected_reservations', []);
        $reservations = Reservation::whereIn('id', $selectedIds)
            ->where('user_id', auth()->id())
            ->get();

        $deletedCount = 0;
        foreach ($reservations as $reservation) {
            $reservation->delete();
            $deletedCount++;
        }

        return redirect()->route('reserve')->with('success', $deletedCount > 0 ? "{$deletedCount} reservation(s) deleted successfully." : 'No reservations were selected.');
    })->name('reservations.destroySelected');

    // Saved Credentials (Password Manager)
    Route::resource('credentials', CredentialController::class);
    Route::get('/credentials/{credential}/password', [CredentialController::class, 'getPassword'])
        ->name('credentials.password');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile settings
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::post('/profile/picture', [AuthController::class, 'uploadProfilePicture'])->name('profile.picture.upload');
    Route::post('/profile/password/verify', [AuthController::class, 'verifyCurrentPassword'])->name('profile.password.verify');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::view('/security/account-compromised', 'security.compromised')->name('security.compromised');
    Route::post('/profile/2fa/setup', [AuthController::class, 'setupAuthenticator'])->name('profile.2fa.setup');
    Route::post('/profile/2fa/confirm', [AuthController::class, 'confirmAuthenticator'])->name('profile.2fa.confirm');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/api/notifications/unread-summary', [NotificationController::class, 'unreadSummary'])
        ->name('notifications.unreadSummary');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAllRead'])->name('notifications.markRead');
    Route::post('/notifications/delete-selected', [NotificationController::class, 'destroySelected'])->name('notifications.destroySelected');

    // Debug utility: simple developer decrypt page for Laravel encrypted strings.
    // Requires OTP verification for security.
    Route::get('/debug/decrypt', [DebugController::class, 'showDecryptForm'])->name('debug.decrypt.form');
    Route::post('/debug/decrypt/otp', [DebugController::class, 'issueDecryptOtp'])->name('debug.decrypt.otp');
    Route::post('/debug/decrypt/otp/resend', [DebugController::class, 'resendDecryptOtp'])->name('debug.decrypt.otp.resend');
    Route::post('/debug/decrypt/otp-verify', [DebugController::class, 'verifyDecryptOtp'])->name('debug.decrypt.otp.verify');
    Route::get('/debug/decrypt/user-details', [DebugController::class, 'getUserDetails'])->name('debug.decrypt.user-details');
    Route::post('/debug/decrypt', [DebugController::class, 'decrypt'])->name('debug.decrypt');

    // Admin portal routes (hidden / secret access only)
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/facilities/create', [AdminController::class, 'createFacility'])->name('admin.facilities.create');
        Route::get('/property-management', [AdminController::class, 'propertyManagement'])->name('admin.property-management.index');
        Route::post('/property-management/items', [AdminController::class, 'storeFacilityItem'])->name('admin.property-management.items.store');
        Route::put('/property-management/items/{item}', [AdminController::class, 'updateFacilityItem'])->name('admin.property-management.items.update');
        Route::delete('/property-management/items/{item}', [AdminController::class, 'destroyFacilityItem'])->name('admin.property-management.items.destroy');
        Route::post('/facilities', [AdminController::class, 'storeFacility'])->name('admin.facilities.store');
        Route::put('/facilities/{facility}', [AdminController::class, 'updateFacility'])->name('admin.facilities.update');
        Route::delete('/facilities/{facility}', [AdminController::class, 'destroyFacility'])->name('admin.facilities.destroy');
        Route::post('/reservations/{reservation}/approve', [AdminController::class, 'approve'])->name('admin.reservations.approve');
        Route::post('/reservations/{reservation}/reject', [AdminController::class, 'reject'])->name('admin.reservations.reject');
        Route::delete('/reservations/{reservation}', [AdminController::class, 'destroyReservation'])->name('admin.reservations.destroy');
        Route::post('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('admin.users.role');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    });
});
