@extends('layouts.app')

@section('title', 'Facility Management - Campus Reserve')

@section('content')
<div class="min-h-screen bg-gray-50 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('admin.dashboard') }}" class="mb-4 inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                &larr; Back to Admin Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Facility Management</h1>
            <p class="mt-2 text-gray-600">Add facilities and control the website cards shown to visitors. Manage facility items and quantities in Property Management.</p>
        </div>

        @if(session('status'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-xl">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-xl">{{ session('error') }}</div>
        @endif
        @if($errors->has('current_password'))
            <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">{{ $errors->first('current_password') }}</div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Add Facility</h2>
                    <p class="mt-2 text-sm text-gray-600">Add a room or space to Campus Reserve.</p>
                </div>

                <form id="facility-form" method="POST" action="{{ route('admin.facilities.store') }}" data-confirm-message="Save this facility change?" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_method" id="facility-form-method" value="POST">

                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-gray-700">Facility name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="100" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="building" class="mb-2 block text-sm font-medium text-gray-700">Building</label>
                        <input id="building" name="building" type="text" value="{{ old('building') }}" required maxlength="100" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                        @error('building')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="type" class="mb-2 block text-sm font-medium text-gray-700">Facility type</label>
                            <input id="type" name="type" type="text" value="{{ old('type') }}" required maxlength="50" placeholder="e.g. Classroom, Laboratory" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                            @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="capacity" class="mb-2 block text-sm font-medium text-gray-700">Capacity</label>
                            <input id="capacity" name="capacity" type="number" value="{{ old('capacity') }}" required min="1" step="1" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                            @error('capacity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" id="facility-reset-form" class="rounded-full px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100">Cancel</button>
                        <button id="facility-submit-button" type="submit" class="rounded-full bg-black px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">Save Facility</button>
                    </div>
                </form>
            </section>

            <aside class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Edit/Remove</h2>
                        <p class="mt-1 text-sm text-gray-600">Update or remove cards shown on the public site.</p>
                    </div>

                    <div class="flex w-52 items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input id="facility-search" type="text" placeholder="Search" class="w-full border-0 bg-transparent p-0 text-sm text-gray-700 placeholder:text-gray-400 focus:outline-none focus:ring-0">
                    </div>
                </div>

                <div id="facility-scroll-area" class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50/40 p-2 pr-3" style="height: 420px; max-height: 420px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #5f5f5f #f3f4f6;">
                    @forelse($facilities as $facility)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 facility-card" data-name="{{ strtolower($facility->name) }}" data-description="{{ strtolower($facility->description ?? '') }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $facility->name }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">{{ $facility->description }}</p>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <button type="button"
                                        data-edit-id="{{ $facility->id }}"
                                        data-name="{{ $facility->name }}"
                                        data-building="{{ $facility->building }}"
                                        data-type="{{ $facility->type }}"
                                        data-capacity="{{ $facility->capacity }}"
                                        class="rounded-full border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                        Edit
                                    </button>
                                    <button type="submit" form="delete-facility-form-{{ $facility->id }}" class="rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">Remove</button>
                                </div>
                            </div>
                        </div>
                        <form id="delete-facility-form-{{ $facility->id }}" method="POST" action="{{ route('admin.facilities.destroy', $facility) }}" data-confirm-message="Remove this facility?" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500">
                            No facilities found yet.
                        </div>
                    @endforelse
                    <div id="no-facilities-match" class="hidden rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500">
                        No matching facilities found.
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
    function resetFacilityForm() {
        const form = document.getElementById('facility-form');
        if (!form) {
            return;
        }

        form.action = '{{ route('admin.facilities.store') }}';
        document.getElementById('facility-form-method').value = 'POST';
        document.getElementById('facility-submit-button').textContent = 'Save Facility';
        form.dataset.confirmMessage = 'Add this facility?';
        form.reset();
    }

    function openFacilityEditForm(button) {
        const form = document.getElementById('facility-form');
        if (!form) {
            return;
        }

        const facilityId = button.dataset.editId;
        if (!facilityId) {
            return;
        }

        form.action = `/admin/facilities/${facilityId}`;
        document.getElementById('facility-form-method').value = 'PUT';
        document.getElementById('facility-submit-button').textContent = 'Update Facility';
        form.dataset.confirmMessage = `Update ${button.dataset.name || 'this facility'}?`;

        document.getElementById('name').value = button.dataset.name || '';
        document.getElementById('building').value = button.dataset.building || '';
        document.getElementById('type').value = button.dataset.type || '';
        document.getElementById('capacity').value = button.dataset.capacity || '';

        const formTop = form.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top: formTop, behavior: 'smooth' });
    }

    document.getElementById('facility-reset-form')?.addEventListener('click', resetFacilityForm);
    document.querySelectorAll('[data-edit-id]').forEach((button) => {
        button.addEventListener('click', function () {
            openFacilityEditForm(this);
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('facility-search');
        const cards = Array.from(document.querySelectorAll('.facility-card'));
        const noMatch = document.getElementById('no-facilities-match');

        if (!searchInput || cards.length === 0) {
            return;
        }

        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            cards.forEach((card) => {
                const text = (card.dataset.name || '') + ' ' + (card.dataset.description || '');
                const visible = text.includes(query);
                card.style.display = visible ? '' : 'none';

                if (visible) {
                    visibleCount += 1;
                }
            });

            if (noMatch) {
                noMatch.classList.toggle('hidden', visibleCount > 0 || query === '');
            }
        });
    });
</script>

<style>
    #facility-scroll-area::-webkit-scrollbar {
        width: 10px;
    }

    #facility-scroll-area::-webkit-scrollbar-track {
        background: #f3f4f6;
        border-radius: 9999px;
    }

    #facility-scroll-area::-webkit-scrollbar-thumb {
        background: #5f5f5f;
        border: 2px solid #f3f4f6;
        border-radius: 9999px;
    }

    #facility-scroll-area::-webkit-scrollbar-thumb:hover {
        background: #4b5563;
    }
</style>
@include('admin.partials.password-confirmation')
@endsection
