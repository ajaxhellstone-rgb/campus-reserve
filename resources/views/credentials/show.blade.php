@extends('layouts.app')

@section('title', $credential->site_name . ' - Campus Reserve')

@section('content')
<!-- View credential details page. Password display and clipboard actions are handled with JavaScript. -->
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
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">{{ $credential->site_name }}</h1>
                <div class="flex items-center gap-2">
                    <a href="{{ route('credentials.edit', $credential) }}" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('credentials.destroy', $credential) }}" onsubmit="return confirm('Are you sure you want to delete this credential?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition-colors">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Credential Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Site URL -->
            @if($credential->site_url)
            <div class="p-6 border-b border-gray-200">
                <label class="block text-sm font-medium text-gray-500 mb-1">Site URL</label>
                <a href="{{ $credential->site_url }}" target="_blank" class="text-blue-600 hover:underline flex items-center">
                    {{ $credential->site_url }}
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
            @endif

            <!-- Username -->
            <div class="p-6 border-b border-gray-200">
                <label class="block text-sm font-medium text-gray-500 mb-1">Username / Email</label>
                <div class="flex items-center justify-between">
                    <span class="text-gray-900 font-mono">{{ $credential->username }}</span>
                    <button onclick="copyToClipboard('{{ $credential->username }}')" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Copy">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Password -->
            <div class="p-6 border-b border-gray-200">
                <label class="block text-sm font-medium text-gray-500 mb-1">Password</label>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span id="password-display" class="text-gray-900 font-mono">••••••••••••</span>
                        <button type="button" onclick="togglePasswordVisibility()" class="rounded p-1 text-gray-500 transition hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500" id="toggle-btn" aria-label="Show password" aria-pressed="false" title="Show password">
                            <svg data-password-eye-open class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg data-password-eye-closed class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                    <button onclick="copyPassword()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Copy Password">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Notes -->
            @if($credential->notes)
            <div class="p-6">
                <label class="block text-sm font-medium text-gray-500 mb-1">Notes</label>
                <p class="text-gray-900 whitespace-pre-wrap">{{ $credential->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Metadata -->
        <div class="mt-6 p-4 bg-gray-100 rounded-lg">
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Created: {{ $credential->created_at->format('M d, Y h:i A') }}</span>
                <span>Updated: {{ $credential->updated_at->format('M d, Y h:i A') }}</span>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-blue-900">Encrypted Storage</h4>
                    <p class="text-sm text-blue-700 mt-1">This credential is stored with AES-256-CBC encryption. The password is decrypted only when you view or copy it.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let passwordVisible = false;
let cachedPassword = null;

async function fetchPassword() {
    if (cachedPassword) return cachedPassword;
    
    try {
        const response = await fetch('/credentials/{{ $credential->id }}/password');
        const data = await response.json();
        cachedPassword = data.password;
        return cachedPassword;
    } catch (error) {
        return null;
    }
}

async function togglePasswordVisibility() {
    const display = document.getElementById('password-display');
    const btn = document.getElementById('toggle-btn');
    const isVisible = passwordVisible;
    
    if (!isVisible) {
        const password = await fetchPassword();
        if (password) {
            display.textContent = password;
            passwordVisible = true;
        }
    } else {
        display.textContent = '••••••••••••';
        passwordVisible = false;
    }

    const action = passwordVisible ? 'Hide' : 'Show';
    btn.setAttribute('aria-label', `${action} password`);
    btn.setAttribute('aria-pressed', String(passwordVisible));
    btn.title = `${action} password`;
    btn.querySelector('[data-password-eye-open]').classList.toggle('hidden', passwordVisible);
    btn.querySelector('[data-password-eye-closed]').classList.toggle('hidden', !passwordVisible);
}

async function copyPassword() {
    const password = await fetchPassword();
    if (password) {
        await navigator.clipboard.writeText(password);
        alert('Password copied to clipboard!');
    } else {
        alert('Failed to copy password');
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    alert('Copied to clipboard!');
}
</script>
@endsection
