@extends('layouts.app')

@section('title', 'Profile Settings - Campus Reserve')

@push('styles')
<style>
    .profile-picture-input::file-selector-button {
        margin-right: 0.75rem;
        border: 0;
        border-radius: 9999px;
        background: #4338ca;
        padding: 0.5rem 1rem;
        color: #fff;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 150ms ease;
    }

    .profile-picture-input::file-selector-button:hover {
        background: #3730a3;
    }

    .profile-picture-input::-webkit-file-upload-button {
        margin-right: 0.75rem;
        border: 0;
        border-radius: 9999px;
        background: #4338ca;
        padding: 0.5rem 1rem;
        color: #fff;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<!-- Profile settings page for updating user account details, profile picture, and phone information. -->
<main class="min-h-[calc(100vh-73px)] bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50 px-4 py-10 md:px-8 xl:px-16">
    <div class="mx-auto grid w-full max-w-7xl gap-8 xl:grid-cols-[280px_1fr]">
        <aside class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm shadow-indigo-950/5">
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900">Settings</h2>
                <p class="mt-2 text-sm text-gray-500">Manage your account settings from one place.</p>
            </div>

            <div class="space-y-3">
                <div class="rounded-3xl border border-indigo-100 bg-indigo-50/70 px-4 py-4 text-sm font-semibold text-indigo-900">Account</div>
            </div>

            <div class="mt-10 border-t border-gray-200 pt-8">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">System</p>
                <div class="mt-4 space-y-3">
                    <a href="{{ route('notifications.index') }}" class="block w-full rounded-3xl border border-slate-200 bg-white px-4 py-4 text-left text-sm text-slate-600 transition hover:border-indigo-300 hover:text-indigo-800">Notifications</a>
                </div>
            </div>
        </aside>

        <section class="space-y-8">
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 shadow-sm shadow-indigo-950/5">
                <div class="bg-gradient-to-r from-indigo-700 via-violet-700 to-cyan-700 px-8 py-8 text-white">
                    <h1 class="text-3xl font-semibold">Account</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">Update your profile, security preferences, and manage how people see your account.</p>
                </div>

                <div class="p-8 space-y-8">
                    <div id="profile-status" class="{{ session('status') ? '' : 'hidden' }} rounded-3xl bg-emerald-50 border border-emerald-200 px-5 py-4 text-sm text-emerald-800">{{ session('status') }}</div>
                    <div id="password-change-alert" class="hidden rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                        <span>Password changed successfully. If you did not make this change, </span><a href="{{ route('security.compromised') }}" class="font-semibold underline">secure your account immediately</a>.
                    </div>
                    @if(session('error'))
                        <div class="rounded-3xl bg-red-50 border border-red-200 px-5 py-4 text-sm text-red-800">{{ session('error') }}</div>
                    @endif

                    <form id="profile-settings-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-8">
                        @csrf
                        <input type="hidden" id="password-2fa-otp" name="password_2fa_otp">
                        
                        <!-- Hidden inputs for phone and country code submission -->
                        <input type="hidden" name="phone" id="form_phone_input" value="{{ old('phone', auth()->user()->phone ?? '') }}" />
                        <input type="hidden" name="country_code" id="form_country_code_input" value="" />
                        <input type="hidden" name="email_notifications" value="0" />

                        <div class="grid gap-6 xl:grid-cols-[280px_1fr]">
                            <div class="rounded-3xl border border-indigo-100 bg-indigo-50/50 p-6">
                                <p class="text-sm font-semibold text-gray-900">Profile</p>
                                <p class="mt-2 text-sm text-gray-500">Your avatar, name, and contact details.</p>
                            </div>

                            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-sm shadow-indigo-950/5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                    <div class="relative h-24 w-24 overflow-hidden rounded-3xl border border-gray-200 bg-gray-100">
                                        <img
                                            src="{{ auth()->user()->profile_picture ? asset('storage/' . auth()->user()->profile_picture) : asset('images/guest-avatar.svg') }}"
                                            onerror="this.onerror=null;this.src='{{ asset('images/guest-avatar.svg') }}';"
                                            alt="Profile picture"
                                            class="h-full w-full object-cover"
                                        >
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <label class="block text-sm font-semibold text-gray-900">Profile picture</label>
                                        <p class="mt-1 text-sm text-gray-500">Square image works best.</p>
                                        <input type="file" name="profile_picture" accept="image/png,image/jpeg,image/webp" class="profile-picture-input mt-3 block w-full text-sm text-gray-700" />
                                        <p id="profile-picture-upload-status" class="mt-2 text-sm text-gray-600" role="status" aria-live="polite"></p>
                                        @error('profile_picture')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="grid gap-6 md:grid-cols-2">
                                    <label class="block">
                                        <span class="text-sm font-semibold text-gray-900">Full name</span>
                                        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required class="mt-2 w-full rounded-3xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
                                        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </label>

                                    <label class="block">
                                        <span class="text-sm font-semibold text-gray-900">Email address</span>
                                        <input type="email" value="{{ auth()->user()->email }}" readonly class="mt-2 w-full rounded-3xl border border-gray-200 bg-slate-100 px-4 py-3 text-sm text-gray-600" />
                                    </label>
                                </div>

                                <label class="block">
                                    <span class="text-sm font-semibold text-gray-900">Phone number</span>
                                    
                                    <!-- Display Mode -->
                                    <div id="phone_display_mode" class="mt-2 hidden">
                                        <div class="flex items-center justify-between rounded-3xl border border-slate-300 bg-slate-50 px-4 py-3">
                                            <span class="text-sm text-gray-900" id="phone_display_text">
                                                @if(auth()->user()->phone)
                                                    {{ auth()->user()->phone }}
                                                @else
                                                    <span class="text-gray-500">Not set</span>
                                                @endif
                                            </span>
                                            <button type="button" id="phone_edit_btn" class="text-gray-500 hover:text-gray-900 transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-7-10l7-7m0 0l-7 7m7-7v12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Edit Mode -->
                                    <div id="phone_edit_mode" class="mt-2 space-y-3">
                                        <div class="grid gap-3 sm:grid-cols-[150px_1fr]">
                                            <label class="block">
                                                <span class="text-xs font-semibold text-gray-700">Country Code</span>
                                                <select id="country_code_select" class="mt-1 w-full rounded-3xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-black">
                                                    <option value="+1">+1 (USA/Canada)</option>
                                                    <option value="+44">+44 (UK)</option>
                                                    <option value="+63">+63 (Philippines)</option>
                                                    <option value="+81">+81 (Japan)</option>
                                                    <option value="+91">+91 (India)</option>
                                                    <option value="+86">+86 (China)</option>
                                                    <option value="+33">+33 (France)</option>
                                                    <option value="+49">+49 (Germany)</option>
                                                    <option value="+39">+39 (Italy)</option>
                                                    <option value="+61">+61 (Australia)</option>
                                                </select>
                                            </label>
                                            <label class="block">
                                                <span class="text-xs font-semibold text-gray-700">Phone Number</span>
                                                <input type="tel" id="phone_number_input" placeholder="9123456789" class="mt-1 w-full rounded-3xl border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-black" />
                                            </label>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" id="phone_save_btn" class="flex-1 rounded-3xl bg-indigo-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-800">Save</button>
                                            <button type="button" id="phone_cancel_btn" class="flex-1 rounded-3xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 transition">Cancel</button>
                                        </div>
                                    </div>
                                    @error('phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                </label>
                            </div>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[280px_1fr]">
                            <div class="rounded-3xl border border-gray-200 bg-slate-50 p-6">
                                <p class="text-sm font-semibold text-gray-900">Email notifications</p>
                                <p class="mt-2 text-sm text-gray-500">Choose whether to receive reservation updates by email.</p>
                            </div>
                            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-sm shadow-indigo-950/5">
                                <label class="flex items-start gap-3">
                                    <input id="email-notifications-toggle" type="checkbox" name="email_notifications" value="1" @checked(old('email_notifications', auth()->user()->email_notifications)) class="mt-1 rounded border-gray-300 text-indigo-700 focus:ring-indigo-500" />
                                    <span>
                                        <span class="block text-sm font-semibold text-gray-900">Email me about my reservations</span>
                                        <span class="mt-1 block text-sm text-gray-500">Receive an email when a reservation is submitted, approved, or denied. In-app notifications are always on.</span>
                                    </span>
                                </label>
                                <p id="email-notifications-status" class="mt-3 text-sm text-gray-600" role="status" aria-live="polite"></p>
                            </div>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[280px_1fr]">
                            <div class="rounded-3xl border border-gray-200 bg-slate-50 p-6">
                                <p class="text-sm font-semibold text-gray-900">Security</p>
                                <p class="mt-2 text-sm text-gray-500">Control password access and two-factor authentication.</p>
                            </div>

                            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-sm shadow-indigo-950/5">
                                <div class="pb-6">
                                    <p class="text-sm font-semibold text-gray-900">Password Reset</p>
                                    <p class="mt-2 text-sm text-gray-500">Change your account password securely.</p>
                                    <button type="button" id="open-password-modal" class="mt-4 rounded-full bg-indigo-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-800">Change password</button>
                                </div>

                                <div class="mt-6">
                                    <span class="text-sm font-semibold text-gray-900">Two-factor authentication</span>
                                    @if (!auth()->user()->two_fa_enabled)
                                        <div id="authenticator-setup-panel" class="mt-3 {{ session('authenticator_setup_qr') || old('two_fa_enabled') ? '' : 'hidden' }} rounded-3xl border border-blue-200 bg-blue-50 p-5">
                                            <p class="text-sm font-semibold text-blue-900">Use an authenticator app</p>
                                            <p class="mt-1 text-sm text-blue-800">Set up Google Authenticator or Microsoft Authenticator to generate login codes.</p>
                                            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                                                <input id="setup-current-password" type="password" name="setup_current_password" class="w-full rounded-3xl border border-blue-200 bg-white px-4 py-3 text-sm" placeholder="Current password" autocomplete="current-password">
                                                <button type="button" id="generate-authenticator-qr" class="rounded-full bg-indigo-700 px-5 py-3 text-sm font-semibold whitespace-nowrap text-white hover:bg-indigo-800">Generate QR code</button>
                                                <button type="button" id="cancel-authenticator-setup" class="rounded-md bg-red-600 px-5 py-3 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-700">Cancel</button>
                                            </div>
                                            <p id="authenticator-setup-error" class="{{ $errors->has('setup_current_password') ? '' : 'hidden' }} mt-2 text-sm text-red-600">{{ $errors->first('setup_current_password') }}</p>
                                        </div>
                                    @endif
                                    <div id="authenticator-qr-result" class="{{ session('authenticator_setup_qr') ? '' : 'hidden' }} mt-4 rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                                            <p class="text-sm font-semibold text-emerald-900">Scan this QR code</p>
                                            <img id="authenticator-qr-image" src="{{ session('authenticator_setup_qr') }}" alt="Authenticator setup QR code" class="mt-3 h-48 w-48 bg-white p-2">
                                            <p class="mt-3 text-sm text-emerald-900">Manual setup key: <strong id="authenticator-secret">{{ session('authenticator_setup_secret') }}</strong></p>
                                            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                                                <input type="text" name="authenticator_otp" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" class="w-full rounded-3xl border border-emerald-200 bg-white px-4 py-3 text-sm" placeholder="6-digit authenticator code" autocomplete="one-time-code">
                                                <button type="submit" formaction="{{ route('profile.2fa.confirm') }}" formmethod="POST" class="rounded-full bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Save authenticator</button>
                                            </div>
                                            @error('authenticator_otp')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="mt-3 inline-flex items-center gap-4 rounded-3xl border border-gray-300 bg-slate-50 p-4">
                                        <input type="hidden" name="two_fa_enabled" value="0">
                                        <input type="checkbox" id="two_fa_enabled" name="two_fa_enabled" value="1" class="h-5 w-5 rounded border-gray-300 text-indigo-700 focus:ring-indigo-500" {{ old('two_fa_enabled', auth()->user()->two_fa_enabled) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">Enable two-factor authentication</span>
                                    </div>
                                    @error('two_fa_enabled')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    <div id="disable-2fa-confirmation" class="hidden"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row justify-end">
                            <a href="{{ route('reserve') }}" class="inline-flex items-center justify-center rounded-full border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all">Cancel</a>
                            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-indigo-700 px-6 py-3 text-sm font-semibold text-white transition-all hover:bg-indigo-800">Save settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<div id="disable-2fa-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="disable-2fa-title">
    <div class="w-full max-w-md rounded-3xl border border-white/20 bg-white p-6 shadow-2xl">
        <h2 id="disable-2fa-title" class="text-xl font-semibold text-gray-900">Disable two-factor authentication?</h2>
        <p class="mt-2 text-sm text-gray-600">For your security, enter your current password and the code from your authenticator app.</p>
        <div class="mt-5 space-y-4">
            <label class="block">
                <span class="text-sm font-semibold text-gray-900">Current password</span>
                <input id="disable-current-password" form="profile-settings-form" type="password" class="mt-2 w-full rounded-3xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black" autocomplete="current-password">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-gray-900">Authenticator code</span>
                <input id="disable-authenticator-code" form="profile-settings-form" type="text" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" class="mt-2 w-full rounded-3xl border border-gray-300 px-4 py-3 text-sm tracking-widest focus:outline-none focus:ring-2 focus:ring-black" placeholder="000000" autocomplete="one-time-code">
            </label>
            <p id="disable-2fa-modal-error" class="{{ $errors->has('disable_current_password') || $errors->has('disable_2fa_otp') ? '' : 'hidden' }} text-sm font-medium text-red-600">{{ $errors->first('disable_current_password') ?: $errors->first('disable_2fa_otp') }}</p>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="cancel-disable-2fa" class="rounded-full border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700">Cancel</button>
            <button type="button" id="continue-disable-2fa" class="rounded-full bg-black px-5 py-3 text-sm font-semibold text-white">Continue</button>
        </div>
    </div>
</div>

<div id="password-2fa-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/20 p-4" style="backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);" role="dialog" aria-modal="true" aria-labelledby="password-2fa-title">
    <div class="w-full max-w-md rounded-3xl border border-white/20 bg-white p-6 shadow-2xl">
        <h2 id="password-2fa-title" class="text-xl font-semibold text-gray-900">Confirm password change</h2>
        <p class="mt-2 text-sm text-gray-600">Enter the current six-digit code from your authenticator app to save your new password.</p>
        <p id="password-2fa-attempts" class="mt-4 text-sm font-medium text-amber-700">You have 3 attempts remaining.</p>
        <input id="password-authenticator-code" form="profile-settings-form" type="text" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" class="mt-5 w-full rounded-3xl border border-gray-300 px-4 py-3 text-center text-lg tracking-widest focus:outline-none focus:ring-2 focus:ring-black" placeholder="000000" autocomplete="one-time-code">
        <p id="password-2fa-error" class="hidden mt-3 text-sm font-medium text-red-600"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="cancel-password-2fa" class="rounded-md border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700">Cancel</button>
            <button type="button" id="confirm-password-2fa" class="rounded-md bg-black px-5 py-3 text-sm font-semibold text-white">Confirm</button>
        </div>
    </div>
</div>

<div id="password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/20 p-4" style="backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);" role="dialog" aria-modal="true" aria-labelledby="password-title">
    <div class="w-full max-w-md rounded-3xl border border-white/20 bg-white p-6 shadow-2xl">
        <h2 id="password-title" class="text-xl font-semibold text-gray-900">Change password</h2>
        <p class="mt-2 text-sm text-gray-600">Enter your current password and choose a new password.</p>
        <div class="mt-5 space-y-4">
            <label class="block">
                <span class="text-sm font-semibold text-gray-900">Current password</span>
                <input id="password-modal-current" form="profile-settings-form" type="password" name="current_password" class="mt-2 w-full rounded-3xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black" autocomplete="current-password">
                <button type="button" id="forgot-password-link" class="mt-2 text-sm font-medium text-blue-700 hover:underline">Forgot password?</button>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                    <p id="forgot-password-status" class="hidden flex-1 text-sm" role="status" aria-live="polite"></p>
                    <button type="button" id="resend-password-reset-link" class="hidden text-sm font-medium text-blue-700 hover:underline">Resend email</button>
                </div>
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-gray-900">New password</span>
                <div class="relative mt-2">
                    <input id="password-modal-new" form="profile-settings-form" type="password" name="password" class="w-full rounded-3xl border border-gray-300 px-4 py-3 pr-16 text-sm focus:outline-none focus:ring-2 focus:ring-black" autocomplete="new-password">
                    <button type="button" data-password-toggle="password-modal-new" class="absolute inset-y-0 right-3 z-10 bg-transparent px-1 text-xs font-semibold text-gray-600 hover:text-black" aria-label="Show new password">Show</button>
                </div>
                <p class="mt-2 text-xs leading-5 text-gray-500">Use 5-30 characters, including uppercase and lowercase letters, a number, and a symbol.</p>
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-gray-900">Confirm new password</span>
                <div class="relative mt-2">
                    <input id="password-modal-confirm" form="profile-settings-form" type="password" name="password_confirmation" class="w-full rounded-3xl border border-gray-300 px-4 py-3 pr-16 text-sm focus:outline-none focus:ring-2 focus:ring-black" autocomplete="new-password">
                    <button type="button" data-password-toggle="password-modal-confirm" class="absolute inset-y-0 right-3 z-10 bg-transparent px-1 text-xs font-semibold text-gray-600 hover:text-black" aria-label="Show password confirmation">Show</button>
                </div>
            </label>
            <p id="password-modal-error" class="hidden text-sm font-medium text-red-600"></p>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="cancel-password-modal" class="rounded-md border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700">Cancel</button>
            <button type="button" id="save-password-modal" class="rounded-md bg-black px-5 py-3 text-sm font-semibold text-white">Save password</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const twoFactorToggle = document.getElementById('two_fa_enabled');
        const authenticatorSetupPanel = document.getElementById('authenticator-setup-panel');
        const setupCurrentPassword = document.getElementById('setup-current-password');
        const disableTwoFactorModal = document.getElementById('disable-2fa-modal');
        const profileSettingsForm = document.getElementById('profile-settings-form');
        const emailNotificationsToggle = document.getElementById('email-notifications-toggle');
        const emailNotificationsStatus = document.getElementById('email-notifications-status');
        const profilePictureInput = profileSettingsForm?.querySelector('input[name="profile_picture"]');
        const profilePictureUploadStatus = document.getElementById('profile-picture-upload-status');
        const disableCurrentPassword = document.getElementById('disable-current-password');
        const disableAuthenticatorCode = document.getElementById('disable-authenticator-code');

        profilePictureInput?.addEventListener('change', async function() {
            const picture = this.files?.[0];
            if (!picture) return;

            this.disabled = true;
            profilePictureUploadStatus.textContent = 'Uploading profile picture...';
            profilePictureUploadStatus.className = 'mt-2 text-sm text-gray-600';

            const formData = new FormData();
            formData.append('profile_picture', picture);
            formData.append('_token', profileSettingsForm.querySelector('input[name="_token"]').value);

            try {
                const response = await fetch('{{ route('profile.picture.upload') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (!response.ok) {
                    const validationMessage = data.errors?.profile_picture?.[0];
                    throw new Error(validationMessage || data.message || 'Unable to upload profile picture.');
                }

                window.location.reload();
            } catch (error) {
                profilePictureUploadStatus.textContent = error.message;
                profilePictureUploadStatus.className = 'mt-2 text-sm text-red-600';
                this.disabled = false;
                this.value = '';
            }
        });
        const disableModalError = document.getElementById('disable-2fa-modal-error');
        const passwordTwoFactorModal = document.getElementById('password-2fa-modal');
        const passwordAuthenticatorCode = document.getElementById('password-authenticator-code');
        const passwordTwoFactorOtp = document.getElementById('password-2fa-otp');
        const passwordTwoFactorError = document.getElementById('password-2fa-error');
        const passwordTwoFactorAttempts = document.getElementById('password-2fa-attempts');
        const passwordModal = document.getElementById('password-modal');
        const passwordModalCurrent = document.getElementById('password-modal-current');
        const passwordModalNew = document.getElementById('password-modal-new');
        const passwordModalConfirm = document.getElementById('password-modal-confirm');
        const passwordModalError = document.getElementById('password-modal-error');
        const passwordRequirement = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+])[A-Za-z\d!@#$%^&*()_+]{5,30}$/;
        let passwordTwoFactorConfirmed = false;
        let disableConfirmed = false;

        emailNotificationsToggle?.addEventListener('change', async function() {
            const toggle = this;
            const previousValue = !toggle.checked;
            toggle.disabled = true;
            emailNotificationsStatus.textContent = 'Saving preference...';
            emailNotificationsStatus.className = 'mt-3 text-sm text-gray-600';

            try {
                const response = await fetch('{{ route('profile.email-notifications.update') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': profileSettingsForm.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({ email_notifications: toggle.checked }),
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.errors?.email_notifications?.[0] || data.message || 'Unable to save this preference.');
                }

                emailNotificationsStatus.textContent = data.status || 'Email notification preference saved.';
                emailNotificationsStatus.className = 'mt-3 text-sm text-emerald-700';
            } catch (error) {
                toggle.checked = previousValue;
                emailNotificationsStatus.textContent = `${error.message} Your previous setting has been restored.`;
                emailNotificationsStatus.className = 'mt-3 text-sm text-red-600';
            } finally {
                toggle.disabled = false;
            }
        });

        document.querySelectorAll('[data-password-toggle]').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const input = document.getElementById(this.dataset.passwordToggle);
                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                this.textContent = isVisible ? 'Show' : 'Hide';
                this.setAttribute('aria-label', `${isVisible ? 'Show' : 'Hide'} ${input.id === 'password-modal-new' ? 'new password' : 'password confirmation'}`);
            });
        });

        function resetPasswordVisibility() {
            document.querySelectorAll('[data-password-toggle]').forEach(function(toggle) {
                const input = document.getElementById(toggle.dataset.passwordToggle);
                input.type = 'password';
                toggle.textContent = 'Show';
                toggle.setAttribute('aria-label', input.id === 'password-modal-new' ? 'Show new password' : 'Show password confirmation');
            });
        }

        twoFactorToggle?.addEventListener('change', function() {
            const isDisabling = !this.checked && {{ auth()->user()->two_fa_enabled ? 'true' : 'false' }};
            const isEnabling = this.checked && {{ auth()->user()->two_fa_enabled ? 'false' : 'true' }};
            authenticatorSetupPanel?.classList.toggle('hidden', !isEnabling);
            if (setupCurrentPassword) {
                setupCurrentPassword.required = false;
            }

            if (isEnabling) {
                setupCurrentPassword?.focus();
            }

            if (isDisabling) {
                disableConfirmed = false;
                disableTwoFactorModal?.classList.remove('hidden');
                disableTwoFactorModal?.classList.add('flex');
                disableCurrentPassword?.focus();
            }
        });

        document.getElementById('cancel-disable-2fa')?.addEventListener('click', function() {
            twoFactorToggle.checked = true;
            disableTwoFactorModal?.classList.add('hidden');
            disableTwoFactorModal?.classList.remove('flex');
        });

        document.getElementById('continue-disable-2fa')?.addEventListener('click', function() {
            disableModalError?.classList.add('hidden');
            if (!disableCurrentPassword?.value || !disableAuthenticatorCode?.value.match(/^\d{6}$/)) {
                disableModalError.textContent = 'Enter your current password and a valid 6-digit authenticator code.';
                disableModalError.classList.remove('hidden');
                return;
            }

            const passwordInput = profileSettingsForm.querySelector('input[name="current_password"]');
            passwordInput.value = disableCurrentPassword.value;
            disableCurrentPassword.name = 'disable_current_password';
            disableAuthenticatorCode.name = 'disable_2fa_otp';
            disableConfirmed = true;
            disableTwoFactorModal?.classList.add('hidden');
            disableTwoFactorModal?.classList.remove('flex');
            profileSettingsForm.requestSubmit();
        });

        document.getElementById('generate-authenticator-qr')?.addEventListener('click', async function() {
            const button = this;
            const setupError = document.getElementById('authenticator-setup-error');
            if (!setupCurrentPassword?.value) {
                setupError.textContent = 'Enter your current password.';
                setupError.classList.remove('hidden');
                setupCurrentPassword?.focus();
                return;
            }

            button.disabled = true;
            button.textContent = 'Generating...';
            setupError.classList.add('hidden');

            try {
                const response = await fetch('{{ route('profile.2fa.setup') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: new FormData(profileSettingsForm),
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Unable to generate the QR code.');
                }

                document.getElementById('authenticator-qr-image').src = data.authenticator_setup_qr;
                document.getElementById('authenticator-secret').textContent = data.authenticator_setup_secret;
                document.getElementById('authenticator-qr-result').classList.remove('hidden');
                const authenticatorOtpInput = document.querySelector('input[name="authenticator_otp"]');
                authenticatorOtpInput.required = false;
                setupCurrentPassword.value = '';
                authenticatorOtpInput.focus();
            } catch (error) {
                setupError.textContent = error.message;
                setupError.classList.remove('hidden');
            } finally {
                button.disabled = false;
                button.textContent = 'Generate QR code';
            }
        });

        document.getElementById('cancel-authenticator-setup')?.addEventListener('click', function() {
            twoFactorToggle.checked = false;
            authenticatorSetupPanel?.classList.add('hidden');
            setupCurrentPassword.required = false;
            setupCurrentPassword.value = '';
            const authenticatorOtpInput = document.querySelector('input[name="authenticator_otp"]');
            if (authenticatorOtpInput) {
                authenticatorOtpInput.value = '';
                authenticatorOtpInput.required = false;
            }
            document.getElementById('authenticator-setup-error')?.classList.add('hidden');
            document.getElementById('authenticator-qr-result')?.classList.add('hidden');
        });

        profileSettingsForm?.addEventListener('submit', function(event) {
            const isAuthenticatorAction = event.submitter?.formAction?.includes('/profile/2fa/');

            if (!isAuthenticatorAction && {{ auth()->user()->two_fa_enabled ? 'true' : 'false' }} && this.querySelector('input[name="password"]')?.value && !passwordTwoFactorConfirmed) {
                event.preventDefault();
                passwordTwoFactorModal?.classList.remove('hidden');
                passwordTwoFactorModal?.classList.add('flex');
                passwordAuthenticatorCode?.focus();
                return;
            }

            if (!isAuthenticatorAction && !{{ auth()->user()->two_fa_enabled ? 'true' : 'false' }} && twoFactorToggle.checked) {
                event.preventDefault();
                authenticatorSetupPanel?.classList.remove('hidden');
                const setupError = document.getElementById('authenticator-setup-error');
                setupError.textContent = 'Finish authenticator setup and verify a code before enabling two-factor authentication. Your other profile settings have not been saved yet.';
                setupError.classList.remove('hidden');
                if (document.getElementById('authenticator-qr-result').classList.contains('hidden')) {
                    setupCurrentPassword?.focus();
                } else {
                    document.querySelector('input[name="authenticator_otp"]')?.focus();
                }
                return;
            }

            if ({{ auth()->user()->two_fa_enabled ? 'true' : 'false' }} && !twoFactorToggle.checked && !disableConfirmed) {
                event.preventDefault();
                disableTwoFactorModal?.classList.remove('hidden');
                disableTwoFactorModal?.classList.add('flex');
                disableCurrentPassword?.focus();
            }
        });

        document.getElementById('cancel-password-2fa')?.addEventListener('click', function() {
            passwordAuthenticatorCode.value = '';
            passwordTwoFactorOtp.value = '';
            passwordTwoFactorConfirmed = false;
            passwordTwoFactorModal.classList.add('hidden');
            passwordTwoFactorModal.classList.remove('flex');
        });

        document.getElementById('open-password-modal')?.addEventListener('click', function() {
            passwordTwoFactorConfirmed = false;
            passwordModalError.classList.add('hidden');
            passwordModal.classList.remove('hidden');
            passwordModal.classList.add('flex');
            passwordModalCurrent.focus();
        });

        document.getElementById('cancel-password-modal')?.addEventListener('click', function() {
            passwordModal.classList.add('hidden');
            passwordModal.classList.remove('flex');
            passwordModalCurrent.value = '';
            passwordModalNew.value = '';
            passwordModalConfirm.value = '';
            resetPasswordVisibility();
        });

        const forgotPasswordButton = document.getElementById('forgot-password-link');
        const resendPasswordResetButton = document.getElementById('resend-password-reset-link');
        const forgotPasswordStatus = document.getElementById('forgot-password-status');

        async function requestPasswordResetEmail(isResend) {
            const activeButton = isResend ? resendPasswordResetButton : forgotPasswordButton;
            const status = forgotPasswordStatus;
            const originalText = activeButton.textContent;
            forgotPasswordButton.disabled = true;
            resendPasswordResetButton.disabled = true;
            activeButton.textContent = isResend ? 'Resending...' : 'Sending reset link...';
            status.classList.remove('hidden');
            status.textContent = isResend
                ? 'Queuing another password reset email...'
                : 'Queuing a secure password reset email to your account address...';
            status.className = 'flex-1 text-sm text-gray-600';

            try {
                const response = await fetch('{{ route('profile.password.reset-link') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': profileSettingsForm.querySelector('input[name="_token"]').value,
                    },
                    credentials: 'same-origin',
                    signal: AbortSignal.timeout(20000),
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Unable to send the reset link. Please try again shortly.');
                }

                status.textContent = isResend
                    ? 'Another password reset email was queued. Check your inbox and spam folder.'
                    : data.status;
                status.className = 'flex-1 text-sm text-emerald-700';
                resendPasswordResetButton.classList.remove('hidden');
            } catch (error) {
                status.textContent = error.name === 'TimeoutError' || error.name === 'AbortError'
                    ? 'The request timed out. The server did not confirm that a reset email was queued. Please try again, or contact an administrator.'
                    : error.message;
                status.className = 'flex-1 text-sm text-red-600';
            } finally {
                forgotPasswordButton.disabled = false;
                resendPasswordResetButton.disabled = false;
                activeButton.textContent = originalText;
            }
        }

        forgotPasswordButton?.addEventListener('click', function() {
            requestPasswordResetEmail(false);
        });

        resendPasswordResetButton?.addEventListener('click', function() {
            requestPasswordResetEmail(true);
        });

        document.getElementById('save-password-modal')?.addEventListener('click', async function() {
            passwordModalError.classList.add('hidden');
            if (!passwordModalCurrent.value || !passwordModalNew.value || !passwordModalConfirm.value) {
                passwordModalError.textContent = 'Complete all password fields.';
                passwordModalError.classList.remove('hidden');
                return;
            }

            if (passwordModalNew.value !== passwordModalConfirm.value) {
                passwordModalError.textContent = 'The new password and confirmation do not match.';
                passwordModalError.classList.remove('hidden');
                return;
            }

            if (!passwordRequirement.test(passwordModalNew.value)) {
                passwordModalError.textContent = 'Password must be at least 5 characters.';
                passwordModalError.classList.remove('hidden');
                passwordModalNew.focus();
                return;
            }

            if ({{ auth()->user()->two_fa_enabled ? 'true' : 'false' }}) {
                passwordModal.classList.add('hidden');
                passwordModal.classList.remove('flex');
                passwordTwoFactorModal.classList.remove('hidden');
                passwordTwoFactorModal.classList.add('flex');
                passwordTwoFactorAttempts.textContent = 'You have 3 attempts remaining.';
                passwordAuthenticatorCode.focus();
                return;
            }

            this.disabled = true;
            this.textContent = 'Saving...';

            try {
                const response = await fetch(profileSettingsForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': profileSettingsForm.querySelector('input[name="_token"]').value,
                    },
                    body: new FormData(profileSettingsForm),
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (!response.ok) {
                    if (data.errors?.current_password) {
                        passwordModalError.textContent = data.errors.current_password[0];
                        passwordModalError.classList.remove('hidden');
                        passwordModalCurrent.focus();
                        return;
                    }

                    throw new Error(data.errors?.password?.[0] || data.message || 'Unable to change password.');
                }

                passwordModal.classList.add('hidden');
                passwordModal.classList.remove('flex');
                passwordModalCurrent.value = '';
                passwordModalNew.value = '';
                passwordModalConfirm.value = '';
                resetPasswordVisibility();
                const profileStatus = document.getElementById('profile-status');
                if (profileStatus) {
                    profileStatus.textContent = data.status || 'Password changed successfully.';
                    profileStatus.classList.remove('hidden');
                }
                if (data.password_changed) {
                    document.getElementById('password-change-alert')?.classList.remove('hidden');
                }
            } catch (error) {
                passwordModalError.textContent = error.message;
                passwordModalError.classList.remove('hidden');
            } finally {
                this.disabled = false;
                this.textContent = 'Save password';
            }
        });

        document.getElementById('confirm-password-2fa')?.addEventListener('click', async function() {
            passwordTwoFactorError.classList.add('hidden');
            if (!/^\d{6}$/.test(passwordAuthenticatorCode.value)) {
                passwordTwoFactorError.textContent = 'Enter a valid 6-digit authenticator code.';
                passwordTwoFactorError.classList.remove('hidden');
                return;
            }

            passwordTwoFactorOtp.value = passwordAuthenticatorCode.value;
            this.disabled = true;
            this.textContent = 'Saving...';

            try {
                const response = await fetch(profileSettingsForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': profileSettingsForm.querySelector('input[name="_token"]').value,
                    },
                    body: new FormData(profileSettingsForm),
                    credentials: 'same-origin',
                });
                const data = await response.json();
                if (response.status === 429) {
                    passwordTwoFactorError.textContent = data.message || 'Too many invalid authenticator codes. Try again.';
                    passwordTwoFactorError.classList.remove('hidden');
                    passwordTwoFactorAttempts.textContent = 'No attempts remaining.';
                    passwordAuthenticatorCode.value = '';
                    passwordAuthenticatorCode.focus();
                    return;
                }
                if (!response.ok) {
                    if (data.errors?.current_password) {
                        passwordTwoFactorModal.classList.add('hidden');
                        passwordTwoFactorModal.classList.remove('flex');
                        passwordModal.classList.remove('hidden');
                        passwordModal.classList.add('flex');
                        passwordModalError.textContent = data.errors.current_password[0];
                        passwordModalError.classList.remove('hidden');
                        passwordModalCurrent.focus();
                        return;
                    }
                    if (data.errors?.password) {
                        passwordTwoFactorModal.classList.add('hidden');
                        passwordTwoFactorModal.classList.remove('flex');
                        passwordModal.classList.remove('hidden');
                        passwordModal.classList.add('flex');
                        passwordModalError.textContent = data.errors.password[0];
                        passwordModalError.classList.remove('hidden');
                        passwordModalNew.focus();
                        return;
                    }
                    passwordTwoFactorError.textContent = data.message || 'Invalid Authenticator Code';
                    passwordTwoFactorError.classList.remove('hidden');
                    passwordTwoFactorAttempts.textContent = `You have ${data.attempts_remaining || 2} attempts remaining.`;
                    passwordAuthenticatorCode.value = '';
                    passwordAuthenticatorCode.focus();
                    return;
                }

                passwordTwoFactorConfirmed = true;
                passwordTwoFactorModal.classList.add('hidden');
                passwordTwoFactorModal.classList.remove('flex');
                passwordModalCurrent.value = '';
                passwordModalNew.value = '';
                passwordModalConfirm.value = '';
                passwordTwoFactorOtp.value = '';
                const profileStatus = document.getElementById('profile-status');
                if (profileStatus) {
                    profileStatus.textContent = data.status || 'Password updated successfully.';
                    profileStatus.classList.remove('hidden');
                }
                if (data.password_changed) {
                    document.getElementById('password-change-alert')?.classList.remove('hidden');
                }
            } catch (requestError) {
                passwordTwoFactorError.textContent = requestError.message;
                passwordTwoFactorError.classList.remove('hidden');
                passwordAuthenticatorCode.value = '';
                passwordAuthenticatorCode.focus();
            } finally {
                this.disabled = false;
                this.textContent = 'Confirm';
            }
        });

        @if (session('show_disable_2fa_otp') || $errors->has('disable_current_password') || $errors->has('disable_2fa_otp'))
            disableTwoFactorModal?.classList.remove('hidden');
            disableTwoFactorModal?.classList.add('flex');
            disableCurrentPassword?.focus();
        @endif

        // Phone number edit functionality
        const displayMode = document.getElementById('phone_display_mode');
        const editMode = document.getElementById('phone_edit_mode');
        const editBtn = document.getElementById('phone_edit_btn');
        const saveBtn = document.getElementById('phone_save_btn');
        const cancelBtn = document.getElementById('phone_cancel_btn');
        const countryCodeSelect = document.getElementById('country_code_select');
        const phoneNumberInput = document.getElementById('phone_number_input');
        const displayText = document.getElementById('phone_display_text');
        const phoneHiddenInput = document.getElementById('form_phone_input');
        const countryCodeHiddenInput = document.getElementById('form_country_code_input');

        function syncPhoneFieldsToForm() {
            const countryCode = countryCodeSelect.value;
            const phoneNumber = phoneNumberInput.value.trim();

            if (phoneNumber) {
                phoneHiddenInput.value = countryCode + phoneNumber;
                countryCodeHiddenInput.value = countryCode;
                displayText.textContent = formatPhoneDisplay(countryCode, phoneNumber);
                return;
            }

            phoneHiddenInput.value = '';
            countryCodeHiddenInput.value = '';
            displayText.textContent = 'Not set';
        }

        // Parse current phone value to extract country code and number
        function parsePhone(phone) {
            if (!phone) {
                return { countryCode: '+63', number: '' };
            }

            const countryCodes = ['+1', '+44', '+63', '+81', '+91', '+86', '+33', '+49', '+39', '+61'];
            
            for (const code of countryCodes) {
                if (phone.startsWith(code)) {
                    return {
                        countryCode: code,
                        number: phone.substring(code.length)
                    };
                }
            }
            
            return { countryCode: '+63', number: phone };
        }

        // Format phone for display
        function formatPhoneDisplay(countryCode, number) {
            if (!number) return 'Not set';
            return countryCode + ' ' + number;
        }

        // Initialize with current phone data
        const currentPhone = '{{ auth()->user()->phone }}';
        const parsed = parsePhone(currentPhone);
        countryCodeSelect.value = parsed.countryCode;
        phoneNumberInput.value = parsed.number;
        if (currentPhone) {
            phoneHiddenInput.value = currentPhone;
            countryCodeHiddenInput.value = parsed.countryCode;
            displayMode.classList.remove('hidden');
            editMode.classList.add('hidden');
        } else {
            phoneHiddenInput.value = '';
            countryCodeHiddenInput.value = '';
            displayMode.classList.add('hidden');
            editMode.classList.remove('hidden');
        }

        profileSettingsForm?.addEventListener('submit', function() {
            syncPhoneFieldsToForm();
        });

        // Edit button click
        editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            displayMode.classList.add('hidden');
            editMode.classList.remove('hidden');
            phoneNumberInput.focus();
        });

        // Save button click
        saveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const countryCode = countryCodeSelect.value;
            const phoneNumber = phoneNumberInput.value.trim();

            if (!phoneNumber) {
                alert('Please enter a phone number');
                return;
            }

            syncPhoneFieldsToForm();

            if (profileSettingsForm) {
                profileSettingsForm.submit();
                return;
            }

            const formattedPhone = formatPhoneDisplay(countryCode, phoneNumber);
            displayText.textContent = formattedPhone;
            displayMode.classList.remove('hidden');
            editMode.classList.add('hidden');
        });

        // Cancel button click
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Reset to current values
            countryCodeSelect.value = parsed.countryCode;
            phoneNumberInput.value = parsed.number;
            
            // Switch back to display mode
            displayMode.classList.remove('hidden');
            editMode.classList.add('hidden');
        });

        // Allow Enter key to save
        phoneNumberInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveBtn.click();
            }
        });
    });
</script>
@endsection
