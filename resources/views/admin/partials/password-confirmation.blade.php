<div id="admin-password-confirmation" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="admin-password-confirmation-title" aria-describedby="admin-password-confirmation-message">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
        <h2 id="admin-password-confirmation-title" class="text-xl font-semibold text-slate-900">Confirm admin action</h2>
        <p id="admin-password-confirmation-message" class="mt-2 text-sm text-slate-600">Enter your password to continue.</p>
        <label class="mt-5 block">
            <span class="text-sm font-semibold text-slate-800">Current password</span>
            <input id="admin-action-password" type="password" autocomplete="current-password" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
        </label>
        <p id="admin-password-confirmation-error" class="mt-3 hidden text-sm text-rose-700" role="alert"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button id="admin-password-confirmation-cancel" type="button" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            <button id="admin-password-confirmation-submit" type="button" class="rounded-full bg-green-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-900">Confirm action</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('admin-password-confirmation');
        const message = document.getElementById('admin-password-confirmation-message');
        const passwordInput = document.getElementById('admin-action-password');
        const error = document.getElementById('admin-password-confirmation-error');
        const cancelButton = document.getElementById('admin-password-confirmation-cancel');
        const confirmButton = document.getElementById('admin-password-confirmation-submit');
        let pendingForm = null;
        let pendingSubmitter = null;

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            passwordInput.value = '';
            error.classList.add('hidden');
            pendingForm = null;
            pendingSubmitter = null;
        }

        document.addEventListener('submit', function(event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            const action = new URL(form.action, window.location.origin);
            if (action.origin !== window.location.origin
                || !action.pathname.startsWith('/admin/')
                || form.method.toUpperCase() !== 'POST'
                || form.dataset.adminPasswordConfirmed === 'true') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            pendingForm = form;
            pendingSubmitter = event.submitter;
            const actionLabel = pendingSubmitter?.textContent?.trim() || 'Continue';
            message.textContent = form.dataset.confirm
                || form.dataset.confirmMessage
                || `${actionLabel}: enter your password to authorize this admin action.`;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            passwordInput.focus();
        }, true);

        cancelButton.addEventListener('click', closeModal);
        modal.addEventListener('click', function(event) {
            if (event.target === modal) closeModal();
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        confirmButton.addEventListener('click', function() {
            if (!pendingForm) return;
            if (!passwordInput.value) {
                error.textContent = 'Enter your current password to continue.';
                error.classList.remove('hidden');
                passwordInput.focus();
                return;
            }

            const form = pendingForm;
            const submitter = pendingSubmitter;
            let passwordField = form.querySelector('input[name="current_password"]');
            if (!passwordField) {
                passwordField = document.createElement('input');
                passwordField.type = 'hidden';
                passwordField.name = 'current_password';
                form.appendChild(passwordField);
            }
            passwordField.value = passwordInput.value;
            form.dataset.adminPasswordConfirmed = 'true';
            closeModal();

            if (submitter instanceof HTMLElement && submitter.form === form) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        });

        passwordInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                confirmButton.click();
            }
        });
    })();
</script>
@endpush
