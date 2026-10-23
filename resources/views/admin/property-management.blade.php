@extends('layouts.app')

@section('title', 'Property Management - Campus Reserve')

@section('content')
<div class="min-h-screen bg-[#f5f3f7]">
    <div class="flex min-h-screen flex-col md:flex-row">
        @include('admin.partials.sidebar')
        <main class="min-w-0 flex-1 px-3 py-5 sm:px-6 sm:py-8 xl:px-10">
    <div class="mx-auto max-w-[1600px]">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Property Management</h1>
            <p class="mt-2 text-gray-600">Manage property items and inventory independently of rooms and facilities.</p>
        </div>

        @if(session('status'))
            <div class="mb-6 rounded-xl border border-green-300 bg-green-100 px-4 py-3 text-green-800">{{ session('status') }}</div>
        @endif
        @if($errors->has('current_password'))
            <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">{{ $errors->first('current_password') }}</div>
        @endif

        <section class="mb-8 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-gray-900">Add an Item</h2>
            <form method="POST" action="{{ route('admin.property-management.items.store') }}" data-confirm-message="Add this property item?" class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
                @csrf
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700">Item name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. Projector" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="quantity" class="mb-2 block text-sm font-medium text-gray-700">Quantity</label>
                    <input id="quantity" name="quantity" type="number" value="{{ old('quantity', 1) }}" required min="1" step="1" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                    @error('quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-fit rounded-xl bg-black px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-gray-800">Add Item</button>
            </form>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-xl font-semibold text-gray-900">Property Inventory</h2>
                <p class="mt-1 text-sm text-gray-600">Edit item names and quantities, or remove an item.</p>
            </div>

            @if($items->isEmpty())
                <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-gray-500">No property items have been added yet.</p>
            @else
                <div class="space-y-4">
                    @foreach($items as $item)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <form id="update-item-{{ $item->id }}" method="POST" action="{{ route('admin.property-management.items.update', $item) }}" data-confirm-message="Save these property item changes?" class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
                                @csrf
                                @method('PUT')
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
                            <form id="delete-item-{{ $item->id }}" method="POST" action="{{ route('admin.property-management.items.destroy', $item) }}" data-confirm-message="Remove this property item?">
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
    </div>
</div>

@include('admin.partials.password-confirmation')
@endsection
