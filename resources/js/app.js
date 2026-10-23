// Main frontend entrypoint for the Laravel Vite build.
// This file loads shared bootstrap behavior and can be extended with component scripts.
import './bootstrap';

const eyeIcon = `
    <svg data-password-eye-open class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
    </svg>
    <svg data-password-eye-closed class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
    </svg>`;

function initializePasswordVisibilityToggles() {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        if (!(input instanceof HTMLInputElement) || input.dataset.passwordToggleInitialized) return;

        let wrapper = input.parentElement;
        if (!wrapper?.classList.contains('relative')) {
            wrapper = document.createElement('div');
            wrapper.className = 'relative';
            input.before(wrapper);
            wrapper.append(input);
        }

        input.classList.add('pr-12');

        const label = input.labels?.[0];
        const labelText = label?.querySelector('span')?.textContent?.trim()
            || label?.textContent?.trim()
            || 'password';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'absolute right-3 top-1/2 -translate-y-1/2 rounded p-1 text-gray-500 transition hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500';
        button.setAttribute('aria-label', `Show ${labelText.toLowerCase()}`);
        button.setAttribute('aria-pressed', 'false');
        button.title = `Show ${labelText.toLowerCase()}`;
        button.innerHTML = eyeIcon;
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            const isVisible = input.type === 'text';
            const action = isVisible ? 'Hide' : 'Show';
            button.setAttribute('aria-label', `${action} ${labelText.toLowerCase()}`);
            button.setAttribute('aria-pressed', String(isVisible));
            button.title = `${action} ${labelText.toLowerCase()}`;
            button.querySelector('[data-password-eye-open]')?.classList.toggle('hidden', isVisible);
            button.querySelector('[data-password-eye-closed]')?.classList.toggle('hidden', !isVisible);
            input.focus();
        });
        wrapper.append(button);
        input.dataset.passwordToggleInitialized = 'true';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePasswordVisibilityToggles);
} else {
    initializePasswordVisibilityToggles();
}
