@extends('layouts.app')

@section('title', 'Sign Up - Campus Reserve')

@section('content')
<!-- User registration form with validation error display and account creation flow. -->
<main class="flex min-h-[calc(100vh-73px)] items-stretch">
    <!-- Left side - Form -->
    <div class="w-full md:w-1/2 flex flex-col items-center justify-center px-8 py-12">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <div class="flex items-center border border-black px-2 py-1">
                    <span class="text-xs font-medium tracking-wide">CAMPUS</span>
                    <span class="bg-black text-white text-xs font-medium px-1 ml-1">RESERVE</span>
                </div>
            </div>
            
            <h1 class="text-3xl font-bold text-center text-gray-900 mb-8">
                CREATE ACCOUNT
            </h1>
            
            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf

                @if(!empty($googleSignup))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        Finish creating your account with a password of your choice.
                    </div>
                @endif
                
                <div class="space-y-2">
                    <label for="name" class="block text-sm text-gray-700">Full Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        class="w-full px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent"
                        value="{{ old('name', $googleSignup ? $googleName : '') }}"
                    >
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="space-y-2">
                    <label for="email" class="block text-sm text-gray-700">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        @if($googleSignup) readonly @endif
                        class="w-full px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent {{ $googleSignup ? 'bg-gray-100 text-gray-600' : '' }}"
                        value="{{ old('email', $googleSignup ? $googleEmail : '') }}"
                    >
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="space-y-2">
                    <label for="password" class="block text-sm text-gray-700">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="w-full px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent"
                    >
                    
                    <!-- Password Strength Meter -->
                    <div class="mt-2">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs text-gray-600">Strength:</span>
                            <div class="flex-1 h-2 bg-gray-300 rounded-full overflow-hidden">
                                <div 
                                    id="passwordStrengthBar" 
                                    class="h-full w-0 transition-all duration-300 rounded-full"
                                    style="background-color: #ef4444;"
                                ></div>
                            </div>
                            <span id="passwordStrengthText" class="text-xs font-semibold text-red-600 w-16">Weak</span>
                        </div>
                    </div>
                    
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="space-y-2">
                    <label for="password_confirmation" class="block text-sm text-gray-700">Confirm Password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        class="w-full px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent"
                    >
                    <p class="text-xs text-gray-500 mt-1 mb-2">Use at least 5 characters. Longer passwords are easier to keep secure.</p>
                </div>
                
                <div class="flex justify-center pt-4">
                    <button
                        type="submit"
                        id="submitBtn"
                        class="px-8 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors"
                    >
                        Sign Up
                    </button>
                </div>
            </form>
            
            <script>
                // Password strength checker
                const passwordInput = document.getElementById('password');
                const passwordConfirmInput = document.getElementById('password_confirmation');
                const submitBtn = document.getElementById('submitBtn');
                const strengthBar = document.getElementById('passwordStrengthBar');
                const strengthText = document.getElementById('passwordStrengthText');

                function checkPasswordStrength(password) {
                    let strength = 0;

                    if (password.length >= 5) strength++;
                    if (password.length >= 8) strength++;
                    if (password.length >= 12) strength++;
                    if (/[a-z]/.test(password)) strength++;
                    if (/[A-Z]/.test(password)) strength++;
                    if (/\d/.test(password)) strength++;
                    if (/[!@#$%^&*()_+]/.test(password)) strength++;

                    return { strength };
                }

                function updatePasswordUI() {
                    const password = passwordInput.value;
                    const { strength } = checkPasswordStrength(password);

                    let level = 'Start';
                    let color = '#d1d5db';
                    let percentage = 0;

                    if (!password.length) {
                        level = 'Start';
                        color = '#d1d5db';
                        percentage = 0;
                    } else if (password.length < 5) {
                        level = 'Too short';
                        color = '#f59e0b';
                        percentage = 25;
                    } else if (password.length >= 5 && strength < 6) {
                        level = 'Good';
                        color = '#10b981';
                        percentage = 70;
                    } else {
                        level = 'Strong';
                        color = '#10b981';
                        percentage = 100;
                    }

                    strengthBar.style.backgroundColor = color;
                    strengthBar.style.width = percentage + '%';

                    strengthText.textContent = level;
                    strengthText.style.color = color;

                    const isValidLength = password.length >= 5;
                    submitBtn.disabled = !isValidLength;
                    submitBtn.style.opacity = isValidLength ? '1' : '0.5';
                    submitBtn.style.cursor = isValidLength ? 'pointer' : 'not-allowed';
                }

                // Add event listener
                passwordInput.addEventListener('input', updatePasswordUI);

                // Initial check
                updatePasswordUI();
            </script>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 mb-4">Or continue with Google</p>
                
                <div class="flex justify-center">
                    <a href="{{ route('auth.google') }}" class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50 transition-colors" title="Sign up with Google">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </a>
                </div>
                
                <p class="mt-6 text-sm text-gray-600">
                    Already have an account?
                    <a href="/login" class="text-blue-600 hover:underline">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Right side - Gradient -->
    <div class="hidden md:block w-1/2 relative overflow-hidden h-full min-h-[calc(100vh-73px)]">
        <div class="absolute inset-0" style="background: linear-gradient(135deg, #c4b5fd 0%, #fbcfe8 45%, #fb923c 100%);"></div>
    </div>
</main>
@endsection
