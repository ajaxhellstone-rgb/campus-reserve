@extends('layouts.app')

@section('title', 'Property Management - Campus Reserve')

@section('content')
<main class="min-h-screen bg-gray-50 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('admin.dashboard') }}" class="mb-4 inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                &larr; Back to Admin Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Property Management</h1>
            <p class="mt-2 text-gray-600">Add and manage items and quantities assigned to rooms and facilities.</p>
        </div>

        @if(session('status'))
            <div class="mb-6 rounded-xl border border-green-300 bg-green-100 px-4 py-3 text-green-800">{{ session('status') }}</div>
        @endif

        <section class="mb-8 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-gray-900">Add an Item</h2>
            <form method="POST" action="{{ route('admin.property-management.items.store') }}" data-confirm-message="Add this item to the selected facility?" class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-4">
                @csrf
                <div class="md:col-span-2">
                    <label for="facility_id" class="mb-2 block text-sm font-medium text-gray-700">Room or facility</label>
                    <select id="facility_id" name="facility_id" required class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                        <option value="">Select a room or facility</option>
                        @foreach($facilities as $facility)
                            <option value="{{ $facility->id }}" @selected(old('facility_id') == $facility->id)>
                                {{ $facility->building }} — {{ $facility->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('facility_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700">Item name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. Projector" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div style="display: flex; align-items: flex-end; gap: 0.75rem;">
                    <div class="min-w-0 flex-1">
                        <label for="quantity" class="mb-2 block text-sm font-medium text-gray-700">Quantity</label>
                        <input id="quantity" name="quantity" type="number" value="{{ old('quantity', 1) }}" required min="1" step="1" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                        @error('quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" style="height: 36px; flex-shrink: 0;" class="rounded-xl bg-black px-5 text-sm font-semibold text-white transition-colors hover:bg-gray-800">Add Item</button>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-xl font-semibold text-gray-900">Facility Items</h2>
                <p class="mt-1 text-sm text-gray-600">Edit the room, item name, and quantity, or remove an item.</p>
            </div>

            @if($items->isEmpty())
                <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-gray-500">No facility items have been added yet.</p>
            @else
                <div class="space-y-4">
                    @foreach($items as $item)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <form id="update-item-{{ $item->id }}" method="POST" action="{{ route('admin.property-management.items.update', $item) }}" data-confirm-message="Save these changes to this facility item?" class="grid grid-cols-1 items-end gap-4 md:grid-cols-5">
                                @csrf
                                @method('PUT')
                                <div class="md:col-span-2">
                                    <label for="facility-{{ $item->id }}" class="mb-2 block text-sm font-medium text-gray-700">Room or facility</label>
                                    <select id="facility-{{ $item->id }}" name="facility_id" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                                        @foreach($facilities as $facility)
                                            <option value="{{ $facility->id }}" @selected($item->facility_id === $facility->id)>
                                                {{ $facility->building }} — {{ $facility->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="item-name-{{ $item->id }}" class="mb-2 block text-sm font-medium text-gray-700">Item name</label>
                                    <input id="item-name-{{ $item->id }}" name="name" type="text" value="{{ $item->name }}" required maxlength="100" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                                </div>
                                <div>
                                    <label for="item-quantity-{{ $item->id }}" class="mb-2 block text-sm font-medium text-gray-700">Quantity</label>
                                    <input id="item-quantity-{{ $item->id }}" name="quantity" type="number" value="{{ $item->quantity }}" required min="1" step="1" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="rounded-full bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Save</button>
                                    <button form="delete-item-{{ $item->id }}" type="submit" class="rounded-full border border-red-200 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50">Remove</button>
                                </div>
                            </form>
                            <form id="delete-item-{{ $item->id }}" method="POST" action="{{ route('admin.property-management.items.destroy', $item) }}" data-confirm-message="Remove this item from the facility?">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</main>

<div id="property-confirmation-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="property-confirmation-title" aria-describedby="property-confirmation-message">
    <div class="w-full max-w-md rounded-3xl border border-gray-200 bg-white p-6 shadow-2xl">
        <h2 id="property-confirmation-title" class="text-xl font-semibold text-gray-900">Confirm action</h2>
        <p id="property-confirmation-message" class="mt-3 text-sm leading-6 text-gray-600"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="property-confirmation-cancel" class="rounded-full border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</button>
            <button type="button" id="property-confirmation-accept" class="rounded-full bg-black px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-gray-800">Confirm</button>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('property-confirmation-modal');
        const message = document.getElementById('property-confirmation-message');
        const cancelButton = document.getElementById('property-confirmation-cancel');
        const acceptButton = document.getElementById('property-confirmation-accept');
        let pendingForm = null;
        let pendingSubmitter = null;
        let confirmedForm = null;

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            pendingForm = null;
            pendingSubmitter = null;
        }

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.dataset.confirmMessage) {
                return;
            }

            if (confirmedForm === form) {
                confirmedForm = null;
                return;
            }

            event.preventDefault();
            pendingForm = form;
            pendingSubmitter = event.submitter;
            message.textContent = form.dataset.confirmMessage;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            cancelButton.focus();
        });

        cancelButton.addEventListener('click', closeModal);

        acceptButton.addEventListener('click', () => {
            if (!pendingForm) {
                return;
            }

            if (!pendingForm.reportValidity()) {
                closeModal();
                return;
            }

            const form = pendingForm;
            const submitter = pendingSubmitter;
            closeModal();
            confirmedForm = form;

            if (submitter instanceof HTMLElement && submitter.form === form) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
@endsection
