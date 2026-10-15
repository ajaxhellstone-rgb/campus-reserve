<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use OTPHP\TOTP;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin()
    {
        $pendingUserId = session('pending_2fa_user_id');
        if ($pendingUserId) {
            $otp = Otp::where('user_id', $pendingUserId)->latest()->first();

            if (! $otp || $otp->isExpired()) {
                session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'time_left', 'pending_admin_2fa']);
            }
        }

        return view('auth.login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $email = strtolower($request->email);
        $user = User::where('email_hash', hash('sha256', $email))->first();

        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $resetUrl = route('password.reset', ['token' => $token, 'email' => $email]);
            Mail::raw("Reset your Campus Reserve password using this link:\n\n{$resetUrl}\n\nThis link expires in 60 minutes. If you did not request a reset, ignore this email.", function ($message) use ($email): void {
                $message->to($email)->subject('Reset your Campus Reserve password');
            });
        }

        return back()->with('status', 'If an account exists for that email, a password reset link has been sent.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:5', 'max:30'],
        ]);

        $email = strtolower($request->email);
        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $reset || Carbon::parse($reset->created_at)->addHour()->isPast() || ! Hash::check($request->token, $reset->token)) {
            return back()->withErrors(['email' => 'This password reset link is invalid or expired.'])->withInput();
        }

        $user = User::where('email_hash', hash('sha256', $email))->first();
        if (! $user) {
            return back()->withErrors(['email' => 'This password reset link is invalid or expired.'])->withInput();
        }

        $user->update(['password' => $request->password]);
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now log in.');
    }

    /**
     * Show the hidden admin login page.
     */
    public function showAdminLogin()
    {
        session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'time_left', 'pending_admin_2fa']);

        return view('auth.admin-login');
    }

    /**
     * Show admin 2FA verification page.
     */
    public function showAdmin2fa()
    {
        if (! session('pending_2fa_user_id') || ! session('pending_admin_2fa')) {
            return redirect()->route('admin.login');
        }

        return view('auth.admin-login');
    }

    /**
     * Handle admin login request.
     */
    public function loginAdmin(Request $request)
    {
        // Check admin email and password. This route is hidden from regular users.
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:5', 'max:30'],
        ]);

        $emailHash = hash('sha256', strtolower($request->email));
        $user = User::where('email_hash', $emailHash)->first();

        logger()->debug('Admin login request received', [
            'email' => $request->email,
            'remember_device' => $request->boolean('remember_device'),
        ]);

        if (! $user || ! $user->is_admin || ! Hash::check($request->password, $user->password)) {
            logger()->debug('Admin login request failed credentials', [
                'email' => $request->email,
                'user_exists' => $user !== null,
                'is_admin' => $user ? $user->is_admin : null,
            ]);

            return back()->withErrors([
                'email' => 'The provided admin credentials are invalid.',
            ])->onlyInput('email');
        }

        if (! $user->two_fa_enabled) {
            logger()->debug('Admin login user has 2FA disabled', ['user_id' => $user->id]);
            Auth::login($user);
            $request->session()->regenerate();

            return redirect('/admin/dashboard');
        }

        logger()->debug('Admin login user requires 2FA', ['user_id' => $user->id]);
        session()->put('remember_device', $request->boolean('remember_device'));

        try {
            $this->prepareLoginTwoFactor($user);
        } catch (\Exception $e) {
            logger()->error('Admin OTP generation failed', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['email' => 'Unable to generate the 2FA code. Please try again.'])
                ->onlyInput('email');
        }

        session()->put([
            'pending_2fa_user_id' => $user->id,
            'pending_2fa_email' => $user->email,
            'pending_admin_2fa' => true,
            'time_left' => 30,
        ]);

        return redirect('/admin-2fa')->with('show_2fa_modal', true);
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        // Check the user's email and password
        // If they have 2FA enabled, I'll send them a code
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:5', 'max:30'],
        ]);

        logger()->debug('Login request received', [
            'route' => $request->path(),
            'email' => $request->email,
            'remember_device' => $request->boolean('remember_device'),
        ]);

        $emailHash = hash('sha256', strtolower($request->email));
        $user = User::where('email_hash', $emailHash)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            logger()->debug('Login request failed credentials', [
                'email' => $request->email,
                'user_exists' => $user !== null,
            ]);

            session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'time_left', 'pending_admin_2fa']);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'The provided credentials do not match our records.'], 422);
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        if ($this->hasValidRememberedDevice($request, $user)) {
            Auth::login($user);
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json(['redirect' => url('/reserve')])->withCookie($this->refreshRememberDeviceCookie($user));
            }

            return redirect('/reserve')->withCookie($this->refreshRememberDeviceCookie($user));
        }

        if (! $user->two_fa_enabled) {
            logger()->debug('Login user has 2FA disabled', ['user_id' => $user->id]);
            Auth::login($user);
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json(['redirect' => url('/reserve')]);
            }

            return redirect('/reserve');
        }

        logger()->debug('Login user requires 2FA', ['user_id' => $user->id]);
        session()->put('remember_device', $request->boolean('remember_device'));

        try {
            $this->prepareLoginTwoFactor($user);
        } catch (\Exception $e) {
            logger()->error('OTP generation failed', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to generate the 2FA code. Please try again.'], 422);
            }

            return back()->withErrors(['email' => 'Unable to generate the 2FA code. Please try again.'])->onlyInput('email');
        }

        session()->put([
            'pending_2fa_user_id' => $user->id,
            'pending_2fa_email' => $user->email,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'two_factor_required' => true,
                'method' => session('pending_2fa_method'),
                'email' => $user->email,
            ]);
        }

        return back()->with([
            'show_2fa_modal' => true,
            'status' => session('pending_2fa_method') === 'authenticator'
                ? 'Enter the code from your authenticator app.'
                : 'A 6-digit verification code has been sent to your email.',
        ]);
    }

    private function issueOtp(User $user)
    {
        // Create a brand new one-time password (OTP) for 2FA
        // Hash it and set to expire in 30 seconds
        logger()->debug('issueOtp called', ['user_id' => $user->id, 'email' => $user->email]);
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete any existing active OTPs for this user
        Otp::where('user_id', $user->id)->delete();

        // Create new OTP record in database
        Otp::create([
            'user_id' => $user->id,
            'code' => Hash::make($otpCode),
            'expires_at' => now()->addSeconds(30),
            'attempts' => 0,
        ]);

        $this->sendOtpEmail($user, $otpCode);

        session()->put('time_left', 30);
    }

    private function prepareLoginTwoFactor(User $user): void
    {
        if ($user->two_fa_secret) {
            session()->put([
                'pending_2fa_method' => 'authenticator',
                'authenticator_2fa_attempts' => 0,
                'two_fa_attempts_remaining' => 3,
            ]);

            return;
        }

        session()->put('pending_2fa_method', 'email');
        $this->issueOtp($user);
    }

    private function sendOtpEmail(User $user, string $otp): void
    {
        Mail::raw(
            "Campus Reserve\n\nYour one-time password (OTP) is:\n\n{$otp}\n\nEnter this 6-digit code on the verification screen. It expires in 30 seconds. If you did not try to sign in, you can ignore this email.",
            function ($message) use ($user): void {
                $message->to($user->email)
                    ->subject('Your Campus Reserve OTP code');
            }
        );
    }

    public function setupAuthenticator(Request $request)
    {
        $user = Auth::user();

        $isChangingPassword = $request->filled('password');
        if ($isChangingPassword) {
            $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'confirmed', 'min:5', 'max:30'],
                'password_2fa_otp' => ['nullable', 'digits:6'],
            ]);

            if (! Hash::check($request->current_password, $user->password)) {
                return $this->profileValidationResponse($request, 'current_password', 'The current password is incorrect.');
            }

            if ($user->two_fa_secret && ! $request->filled('password_2fa_otp')) {
                return $this->profileValidationResponse($request, 'password_2fa_otp', 'Enter your authenticator code to change your password.');
            }

            if ($user->two_fa_secret && ! TOTP::createFromSecret($user->two_fa_secret)->verify($request->password_2fa_otp)) {
                $attempts = (int) session('password_2fa_attempts', 0) + 1;
                if ($attempts >= 3) {
                    session()->forget('password_2fa_attempts');
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Too many invalid authenticator codes. Try again.'], 429);
                    }

                    return back()->withErrors(['password_2fa_otp' => 'Too many invalid authenticator codes. Try again.'])->withInput();
                }

                session(['password_2fa_attempts' => $attempts]);
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Invalid Authenticator Code', 'attempts_remaining' => 3 - $attempts], 422);
                }

                return $this->profileValidationResponse($request, 'password_2fa_otp', 'Invalid Authenticator Code');
            }

            session()->forget('password_2fa_attempts');
        }
        if (! Hash::check($request->input('setup_current_password'), $user->password)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'The current password is incorrect.'], 422);
            }

            return back()->withErrors(['setup_current_password' => 'The current password is incorrect.']);
        }

        $totp = TOTP::generate();
        $totp->setLabel($user->email);
        $totp->setIssuer('Campus Reserve');
        session(['pending_authenticator_secret' => $totp->getSecret()]);

        $setup = [
            'authenticator_setup_uri' => $totp->getProvisioningUri(),
            'authenticator_setup_qr' => (new SvgWriter)->write(new QrCode($totp->getProvisioningUri()))->getDataUri(),
            'authenticator_setup_secret' => $totp->getSecret(),
        ];

        if ($request->expectsJson()) {
            return response()->json($setup);
        }

        return back()->with($setup);
    }

    public function confirmAuthenticator(Request $request)
    {
        $request->validate(['authenticator_otp' => ['required', 'digits:6']]);
        $secret = session('pending_authenticator_secret');
        $user = Auth::user();

        if (! $secret || ! TOTP::createFromSecret($secret)->verify($request->authenticator_otp, null, 29)) {
            return back()->withErrors(['authenticator_otp' => 'The authenticator code is invalid.'])->withInput();
        }

        $user->update(['two_fa_secret' => $secret, 'two_fa_enabled' => true]);
        session()->forget('pending_authenticator_secret');

        return back()->with('status', 'Authenticator app 2FA is now enabled.');
    }

    public function showProfile()
    {
        return view('profile');
    }

    public function verifyCurrentPassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->current_password, Auth::user()->password)) {
            return response()->json(['message' => 'The current password is incorrect.'], 422);
        }

        return response()->json(['valid' => true]);
    }

    public function updateEmailNotifications(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => ['required', 'boolean'],
        ]);

        Auth::user()->update([
            'email_notifications' => $validated['email_notifications'],
        ]);

        return response()->json([
            'status' => $validated['email_notifications']
                ? 'Email notifications enabled.'
                : 'Email notifications disabled.',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $isChangingPassword = $request->filled('password');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'in:+1,+44,+63,+81,+91,+86,+33,+49,+39,+61'],
            'phone' => ['nullable', 'string', 'regex:/^[\+]?[0-9]{7,15}$/'],
            'two_fa_enabled' => ['sometimes', 'boolean'],
            'email_notifications' => ['sometimes', 'boolean'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();

        if ($isChangingPassword) {
            $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'confirmed', 'min:5', 'max:30'],
                'password_2fa_otp' => ['nullable', 'digits:6'],
            ]);

            if (! Hash::check($request->current_password, $user->password)) {
                return $this->profileValidationResponse($request, 'current_password', 'The current password is incorrect.');
            }

            if ($user->two_fa_enabled && ! $user->two_fa_secret) {
                return $this->profileValidationResponse($request, 'password_2fa_otp', 'Authenticator verification is unavailable. Please set up 2FA again.');
            }

            if ($user->two_fa_enabled && ! $request->filled('password_2fa_otp')) {
                return $this->profileValidationResponse($request, 'password_2fa_otp', 'Enter your authenticator code to change your password.');
            }

            if ($user->two_fa_enabled && ! TOTP::createFromSecret($user->two_fa_secret)->verify($request->password_2fa_otp)) {
                $attempts = (int) session('password_2fa_attempts', 0) + 1;
                if ($attempts >= 3) {
                    session()->forget('password_2fa_attempts');

                    return response()->json(['message' => 'Too many invalid authenticator codes. Try again.'], 429);
                }

                session(['password_2fa_attempts' => $attempts]);

                return response()->json(['message' => 'Invalid Authenticator Code', 'attempts_remaining' => 3 - $attempts], 422);
            }

            session()->forget('password_2fa_attempts');
        }

        if ($request->boolean('two_fa_enabled') && ! $user->two_fa_enabled && ! $user->two_fa_secret) {
            return redirect()->route('profile.show')->withErrors([
                'two_fa_enabled' => 'Set up and confirm your authenticator app before enabling 2FA.',
            ]);
        }

        $isDisablingTwoFactor = $user->two_fa_enabled && ! $request->boolean('two_fa_enabled');
        if ($isDisablingTwoFactor) {
            $request->validate([
                'disable_current_password' => ['required', 'string'],
                'disable_2fa_otp' => ['nullable', 'digits:6'],
            ]);

            if (! Hash::check($request->disable_current_password, $user->password)) {
                return back()->withErrors([
                    'disable_current_password' => 'The current password is incorrect.',
                ])->withInput();
            }

            if (! $request->filled('disable_2fa_otp')) {
                if ($user->two_fa_secret) {
                    return back()->with('show_disable_2fa_otp', true)->with(
                        'status',
                        'Enter the current code from your authenticator app to disable 2FA.'
                    )->withInput();
                }

                $this->issueOtp($user);

                return back()->with('show_disable_2fa_otp', true)->with(
                    'status',
                    'A verification code was sent to your email. Enter it to disable 2FA.'
                )->withInput();
            }

            if ($user->two_fa_secret) {
                if (! TOTP::createFromSecret($user->two_fa_secret)->verify($request->disable_2fa_otp, null, 29)) {
                    return back()->withErrors([
                        'disable_2fa_otp' => 'Invalid Authenticator Code',
                    ])->with('show_disable_2fa_otp', true)->withInput();
                }

                $user->update(['two_fa_enabled' => false]);

                return back()->with('status', 'Two-factor authentication has been disabled.');
            }

            $otp = Otp::where('user_id', $user->id)->latest()->first();
            if (! $otp || ! $otp->isValid() || ! Hash::check($request->disable_2fa_otp, $otp->code)) {
                if ($otp && $otp->isValid()) {
                    $otp->incrementAttempts();
                }

                return back()->withErrors([
                    'disable_2fa_otp' => 'Invalid Authenticator Code',
                ])->with('show_disable_2fa_otp', true)->withInput();
            }

            $otp->delete();
        }

        $data = [
            'name' => $request->name,
            'two_fa_enabled' => $request->boolean('two_fa_enabled'),
            'email_notifications' => $request->boolean('email_notifications'),
        ];

        if ($request->phone) {
            // If the phone number already has a country code (starts with +), keep it
            // Otherwise, I'll add the country code they selected
            if (str_starts_with($request->phone, '+')) {
                $data['phone'] = $request->phone;
            } else {
                $countryCode = $request->country_code ?? '+63';
                $data['phone'] = $countryCode.$request->phone;
            }
        }

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        if ($isChangingPassword) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        if ($isChangingPassword) {
            try {
                $user->notify(new PasswordChanged);
            } catch (\Throwable $exception) {
                logger()->error('Password change security email could not be sent.', [
                    'user_id' => $user->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $isChangingPassword ? 'Password changed successfully.' : 'Profile updated successfully.',
                'password_changed' => $isChangingPassword,
            ]);
        }

        return back()->with('status', $isChangingPassword ? 'Password changed successfully.' : 'Profile updated successfully.');
    }

    public function uploadProfilePicture(Request $request)
    {
        $validated = $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();
        $previousPicture = $user->profile_picture;
        $picturePath = $validated['profile_picture']->store('profile_pictures', 'public');

        if (! $picturePath) {
            return response()->json([
                'message' => 'The profile picture could not be saved. Please try again.',
            ], 500);
        }

        $user->update(['profile_picture' => $picturePath]);

        if ($previousPicture
            && str_starts_with($previousPicture, 'profile_pictures/')
            && $previousPicture !== 'profile_pictures/default-avatar.png') {
            Storage::disk('public')->delete($previousPicture);
        }

        return response()->json([
            'status' => 'Profile picture uploaded successfully.',
            'picture_url' => Storage::disk('public')->url($picturePath),
        ]);
    }

    private function profileValidationResponse(Request $request, string $field, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'field' => $field], 422);
        }

        return back()->withErrors([$field => $message])->withInput();
    }

    /**
     * Update theme preference via AJAX
     */
    private function hasValidRememberedDevice(Request $request, User $user): bool
    {
        $token = $request->cookie('remember_device');

        if (! $token || ! $user->remember_device_token || ! $user->remember_device_expires_at) {
            return false;
        }

        if ($user->remember_device_expires_at->isPast()) {
            return false;
        }

        return Hash::check($token, $user->remember_device_token);
    }

    private function rememberDeviceCookie(User $user)
    {
        $token = Str::random(60);
        $expires = now()->addDays(30);

        $user->update([
            'remember_device_token' => Hash::make($token),
            'remember_device_expires_at' => $expires,
        ]);

        return cookie(
            'remember_device',
            $token,
            60 * 24 * 30,
            '/',
            null,
            app()->environment('production'),
            true,
            false,
            'Strict'
        );
    }

    private function refreshRememberDeviceCookie(User $user)
    {
        if (! $user->remember_device_token) {
            return null;
        }

        return $this->rememberDeviceCookie($user);
    }

    private function forgetRememberDeviceCookie()
    {
        return Cookie::forget('remember_device');
    }

    /**
     * Show the registration form
     */
    public function showRegister()
    {
        $googleSignup = session('pending_google_signup');

        return view('auth.register', [
            'googleSignup' => ! empty($googleSignup),
            'googleName' => $googleSignup['name'] ?? null,
            'googleEmail' => $googleSignup['email'] ?? null,
        ]);
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $googleSignup = session('pending_google_signup');

        if ($googleSignup) {
            $request->merge([
                'name' => $request->input('name', $googleSignup['name'] ?? ''),
                'email' => $googleSignup['email'] ?? $request->input('email'),
            ]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', 'min:5', 'max:30'],
        ]);

        $emailHash = hash('sha256', strtolower($request->email));
        if (User::where('email_hash', $emailHash)->exists()) {
            return back()->withErrors([
                'email' => 'This email is already registered.',
            ])->onlyInput('email', 'name');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_picture' => 'profile_pictures/default-avatar.png', // Default avatar
        ]);

        session()->forget('pending_google_signup');
        Auth::login($user);

        return redirect('/reserve');
    }

    /**
     * Show 2FA verification form
     */
    public function show2faVerify(Request $request)
    {
        $userId = session('pending_2fa_user_id');
        if (! $userId) {
            return redirect('/login');
        }

        $user = User::findOrFail($userId);
        $otp = Otp::where('user_id', $userId)->latest()->first();

        if (! $user->two_fa_secret && (! $otp || $otp->isExpired())) {
            $request->session()->forget([
                'pending_2fa_user_id',
                'pending_2fa_email',
                'pending_2fa_method',
                'pending_2fa_provider',
                'pending_2fa_redirect',
                'pending_google_2fa',
                'show_2fa_modal',
                'time_left',
            ]);

            return redirect('/login')->with('error', '2FA code expired. Please login again.');
        }

        $method = session('pending_2fa_method', $user->two_fa_secret ? 'authenticator' : 'email');
        $provider = session('pending_2fa_provider');
        $attemptsRemaining = $user->two_fa_secret
            ? max(0, 3 - (int) session('authenticator_2fa_attempts', 0))
            : max(0, 3 - (int) $otp->attempts);

        return view('auth.2fa-verify', compact('method', 'provider', 'attemptsRemaining'));
    }

    /**
     * Verify 2FA code
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId = session('pending_2fa_user_id');
        $user = User::findOrFail($userId);
        $otp = Otp::where('user_id', $userId)->latest()->first();

        if ($user->two_fa_secret) {
            $attempts = (int) session('authenticator_2fa_attempts', 0);
            if ($attempts >= 3) {
                $request->session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'pending_2fa_method', 'pending_2fa_provider', 'pending_2fa_redirect', 'pending_google_2fa', 'authenticator_2fa_attempts']);
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Too many invalid authenticator codes. Please log in again.', 'redirect' => route('login')], 429);
                }

                return redirect()->route('login')->withErrors(['email' => 'Too many invalid authenticator codes. Please log in again.']);
            }

            if (! TOTP::createFromSecret($user->two_fa_secret)->verify($request->otp, null, 29)) {
                $attempts++;
                if ($attempts >= 3) {
                    $request->session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'pending_2fa_method', 'pending_2fa_provider', 'pending_2fa_redirect', 'pending_google_2fa', 'authenticator_2fa_attempts']);
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Too many invalid authenticator codes. Please log in again.', 'redirect' => route('login')], 429);
                    }

                    return redirect()->route('login')->withErrors(['email' => 'Too many invalid authenticator codes. Please log in again.']);
                }

                session([
                    'authenticator_2fa_attempts' => $attempts,
                    'two_fa_attempts_remaining' => 3 - $attempts,
                ]);
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Invalid Authenticator Code', 'attempts_remaining' => 3 - $attempts], 422);
                }

                return back()->with(['show_2fa_modal' => true, 'two_fa_attempts_remaining' => 3 - $attempts])->withErrors(['otp' => 'Invalid Authenticator Code']);
            }

            $redirectUrl = session()->pull('pending_2fa_redirect');
            $request->session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'time_left', 'pending_2fa_method', 'pending_2fa_provider', 'pending_google_2fa', 'authenticator_2fa_attempts']);
            $isAdminLogin = session()->pull('pending_admin_2fa', false);
            Auth::login($user);
            $request->session()->regenerate();
            if ($request->expectsJson()) {
                return response()->json(['redirect' => $redirectUrl ?? ($isAdminLogin ? url('/admin/dashboard') : url('/reserve'))]);
            }

            return redirect($redirectUrl ?? ($isAdminLogin ? '/admin/dashboard' : '/reserve'));
        }

        if (! $otp || ! $otp->isValid()) {
            return back()->with([
                'show_2fa_modal' => true,
            ])->withErrors(['otp' => 'Code expired or too many attempts. Please resend a new code.']);
        }

        if (Hash::check($request->otp, $otp->code)) {
            $otp->delete();

            $redirectUrl = session()->pull('pending_2fa_redirect');
            $request->session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'time_left', 'pending_2fa_method', 'pending_2fa_provider', 'pending_google_2fa']);
            $isAdminLogin = session()->pull('pending_admin_2fa', false);

            Auth::login($user);
            $request->session()->regenerate();

            $response = redirect($redirectUrl ?? ($isAdminLogin ? '/admin/dashboard' : '/reserve'));
            if (session()->pull('remember_device', false)) {
                $response->withCookie($this->rememberDeviceCookie($user));
            }

            return $response;
        }

        $otp->incrementAttempts();

        return back()->with([
            'show_2fa_modal' => true,
        ])->withErrors(['otp' => 'Invalid code. Try again.']);
    }

    /**
     * Resend OTP
     */
    public function resend2fa(Request $request)
    {
        $userId = session('pending_2fa_user_id');
        $user = User::find($userId);

        if (! $user) {
            session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'time_left', 'pending_admin_2fa']);
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Session expired. Login again.'], 440);
            }

            return redirect('/login')->with('error', 'Session expired. Login again.');
        }

        $this->prepareLoginTwoFactor($user);

        session()->flash('show_2fa_modal', true);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'A new 6-digit code has been generated and logged.',
                'time_left' => 30,
            ]);
        }

        return back()->with([
            'show_2fa_modal' => true,
            'status' => 'A new 6-digit code has been generated and logged.',
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            Otp::where('user_id', $user->id)->delete();
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->forget(['pending_2fa_user_id', 'pending_2fa_email', 'show_2fa_modal', 'time_left', 'pending_admin_2fa', 'pending_google_signup']);

        return redirect('/')->withCookie($this->forgetRememberDeviceCookie());
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        $redirectUrl = $request->getSchemeAndHttpHost().'/auth/google/callback';

        return Socialite::driver('google')
            ->redirectUrl($redirectUrl)
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Skip SSL checking for testing on localhost
            $httpClient = new Client(['verify' => false]);
            $googleUser = Socialite::driver('google')
                ->setHttpClient($httpClient)
                ->user();
        } catch (\Exception $e) {
            // Record the error for debugging
            \Log::error('Google OAuth Error: '.$e->getMessage());

            return redirect('/login')->with('error', 'Failed to authenticate with Google.');
        }

        // Check if user exists by email hash
        $emailHash = hash('sha256', strtolower($googleUser->getEmail()));
        $user = User::where('email_hash', $emailHash)->first();

        // New Google users complete the normal signup flow with their own password confirmation.
        if (! $user) {
            session()->put('pending_google_signup', [
                'name' => $googleUser->getName() ?: 'User',
                'email' => $googleUser->getEmail(),
            ]);

            return redirect()->route('register');
        }

        if ($user->two_fa_enabled) {
            try {
                $this->prepareLoginTwoFactor($user);
            } catch (\Exception $e) {
                logger()->error('Google sign-in 2FA setup failed', [
                    'user_id' => $user->id,
                    'exception' => $e->getMessage(),
                ]);

                return redirect('/login')->withErrors([
                    'email' => 'Unable to start two-factor verification. Please try again.',
                ]);
            }

            $request->session()->put([
                'pending_2fa_user_id' => $user->id,
                'pending_2fa_email' => $user->email,
                'pending_2fa_provider' => 'google',
                'pending_2fa_redirect' => '/reserve',
                'pending_google_2fa' => true,
            ]);

            return redirect()->route('2fa.show')->with(
                'status',
                session('pending_2fa_method') === 'authenticator'
                    ? 'Google sign-in succeeded. Enter your Campus Reserve authenticator code to continue.'
                    : 'Google sign-in succeeded. Enter the verification code sent to your email to continue.'
            );
        }

        // Sign users without Campus Reserve two-factor authentication in directly.
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/reserve');
    }
}
