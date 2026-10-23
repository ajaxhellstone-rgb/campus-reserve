@extends('layouts.app')

@section('title', 'Add Credential - Campus Reserve')

@section('content')
<!-- Create credential form: user can submit site login details to be encrypted and stored. -->
<div class="min-h-screen bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('credentials.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Credentials
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Add New Credential</h1>
            <p class="text-gray-600 mt-2">Your credentials will be securely encrypted with AES-256.</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('credentials.store') }}">
                @csrf

                <!-- Site Name -->
                <div class="mb-6">
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">Site Name *</label>
                    <input type="text" name="site_name" id="site_name" value="{{ old('site_name') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all @error('site_name') border-red-500 @enderror"
                        placeholder="e.g., Gmail, GitHub, Netflix">
                    @error('site_name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Site URL -->
                <div class="mb-6">
                    <label for="site_url" class="block text-sm font-medium text-gray-700 mb-2">Site URL (Optional)</label>
                    <input type="text" name="site_url" id="site_url" value="{{ old('site_url') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all @error('site_url') border-red-500 @enderror"
                        placeholder="e.g., https://gmail.com">
                    @error('site_url')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username / Email -->
                <div class="mb-6">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username / Email *</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all @error('username') border-red-500 @enderror"
                        placeholder="Your login username or email">
                    @error('username')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all pr-12 @error('password') border-red-500 @enderror"
                            placeholder="Enter the password for this site">
                    </div>
                    @error('password')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div class="mb-8">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all resize-none @error('notes') border-red-500 @enderror"
                        placeholder="Any additional notes about this credential">{{ old('notes') }}</textarea>
                    @error('notes')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('credentials.index') }}" class="px-6 py-3 text-gray-700 hover:text-gray-900 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-800 transition-colors">
                        Save Credential
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-green-900">Your data is secure</h4>
                    <p class="text-sm text-green-700 mt-1">All fields are encrypted using AES-256-CBC before being stored in the database.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
