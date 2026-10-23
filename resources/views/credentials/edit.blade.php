@extends('layouts.app')

@section('title', 'Edit Credential - Campus Reserve')

@section('content')
<!-- Edit credential form: user can update stored credential data with encryption on save. -->
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
            <h1 class="text-3xl font-bold text-gray-900">Edit Credential</h1>
            <p class="text-gray-600 mt-2">Update your saved credential for {{ $credential->site_name }}.</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('credentials.update', $credential) }}">
                @csrf
                @method('PUT')

                <!-- Site Name -->
                <div class="mb-6">
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">Site Name *</label>
                    <input type="text" name="site_name" id="site_name" value="{{ old('site_name', $credential->site_name) }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all @error('site_name') border-red-500 @enderror"
                        placeholder="e.g., Gmail, GitHub, Netflix">
                    @error('site_name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Site URL -->
                <div class="mb-6">
                    <label for="site_url" class="block text-sm font-medium text-gray-700 mb-2">Site URL (Optional)</label>
                    <input type="text" name="site_url" id="site_url" value="{{ old('site_url', $credential->site_url) }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all @error('site_url') border-red-500 @enderror"
                        placeholder="e.g., https://gmail.com">
                    @error('site_url')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username / Email -->
                <div class="mb-6">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username / Email *</label>
                    <input type="text" name="username" id="username" value="{{ old('username', $credential->username) }}" required
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
                        <input type="password" name="password" id="password" value="{{ old('password', $credential->password) }}" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all pr-12 @error('password') border-red-500 @enderror"
                            placeholder="Enter the password for this site">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Leave unchanged to keep the current password, or enter a new one to update it.</p>
                    @error('password')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div class="mb-8">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all resize-none @error('notes') border-red-500 @enderror"
                        placeholder="Any additional notes about this credential">{{ old('notes', $credential->notes) }}</textarea>
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
                        Update Credential
                    </button>
                </div>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="mt-8 p-6 bg-red-50 border border-red-200 rounded-xl">
            <h3 class="text-lg font-semibold text-red-900 mb-2">Danger Zone</h3>
            <p class="text-sm text-red-700 mb-4">Once you delete this credential, there is no going back. Please be certain.</p>
            <form method="POST" action="{{ route('credentials.destroy', $credential) }}" onsubmit="return confirm('Are you sure you want to delete this credential? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Delete This Credential
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
