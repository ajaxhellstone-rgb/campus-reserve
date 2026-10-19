@extends('layouts.app')

@section('title', 'Notifications - Campus Reserve')

@section('content')
<div class="min-h-[calc(100vh-73px)] bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50 px-4 py-10 md:px-8 xl:px-16">
    <div class="mx-auto max-w-5xl">
        <div class="mb-8 flex flex-col gap-4 rounded-3xl border border-white/80 bg-gradient-to-r from-indigo-100/90 via-pink-50 to-cyan-100/80 p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Notifications</h1>
                <p class="mt-2 text-sm text-slate-600">Review your recent alerts and mark them as read or remove selected messages.</p>
            </div>
            <!-- Actions for notification batch processing: mark all read or remove selected. -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <label class="inline-flex items-center text-sm text-slate-600">
                    <input id="selectAllNotifications" type="checkbox" class="mr-2 h-4 w-4 rounded border-slate-300 text-indigo-700 focus:ring-indigo-500">
                    Select all
                </label>
                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('notifications.markRead') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-indigo-700 to-violet-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-800 hover:to-violet-800">
                            Mark all as read
                        </button>
                    </form>
                    <button type="submit" form="notificationsDeleteForm" class="inline-flex items-center justify-center rounded-full bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                        Remove selected
                    </button>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900" role="status">
                {{ session('success') }}
            </div>
        @elseif(session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-900" role="alert">
                {{ session('error') }}
            </div>
        @elseif(session('info'))
            <div class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sky-900" role="status">
                {{ session('info') }}
            </div>
        @endif

        @if($notifications->isEmpty())
            <div class="rounded-3xl border border-dashed border-indigo-200 bg-white/90 p-10 text-center text-slate-600 shadow-sm">
                <p class="text-lg font-medium">No notifications yet.</p>
                <p class="mt-2 text-sm">You will receive reservation approval or rejection updates here.</p>
            </div>
        @else
            <form id="notificationsDeleteForm" method="POST" action="{{ route('notifications.destroySelected') }}" class="hidden">
                @csrf
            </form>
            <div class="space-y-4">
                @foreach($notifications as $notification)
                    <div class="rounded-3xl border p-5 shadow-sm transition {{ is_null($notification->read_at) ? 'border-indigo-200 bg-white shadow-indigo-950/5' : 'border-slate-200/80 bg-white/75' }}">
                        @php $isApprovalRequest = $notification->type === \App\Notifications\ReservationPendingApproval::class; @endphp
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="flex items-start gap-3">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" form="notificationsDeleteForm" name="selected_notifications[]" value="{{ $notification->id }}" data-approval-request="{{ $isApprovalRequest ? '1' : '0' }}" class="notification-checkbox h-4 w-4 rounded border-slate-300 text-indigo-700 focus:ring-indigo-500">
                                </label>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                        @if($isApprovalRequest)
                                            <span class="rounded-full bg-pink-100 px-2 py-1 text-[11px] uppercase tracking-[0.2em] text-pink-900">Request</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $notification->data['message'] ?? '' }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-2 text-right text-xs text-slate-500 md:items-end">
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                @if(isset($notification->data['status']))
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] uppercase tracking-[0.2em] text-slate-700">
                                        {{ ucfirst($notification->data['status']) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if($notification->type === \App\Notifications\PropertyReturnDue::class && empty($notification->data['response']))
                            <div class="mt-4 flex flex-wrap gap-3">
                                <form method="POST" action="{{ route('notifications.propertyReturn.respond', $notification->data['reservation_id']) }}">
                                    @csrf
                                    <input type="hidden" name="notification_id" value="{{ $notification->id }}">
                                    <button type="submit" name="response" value="returned" class="rounded-full bg-gradient-to-r from-indigo-700 to-violet-700 px-4 py-2 text-sm font-semibold text-white transition hover:from-indigo-800 hover:to-violet-800">
                                        Yes, returned
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('notifications.propertyReturn.respond', $notification->data['reservation_id']) }}">
                                    @csrf
                                    <input type="hidden" name="notification_id" value="{{ $notification->id }}">
                                    <button type="submit" name="response" value="not_returned" class="rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                        No, not returned
                                    </button>
                                </form>
                            </div>
                        @elseif($notification->type === \App\Notifications\PropertyReturnDue::class && !empty($notification->data['response']))
                            <p class="mt-4 text-sm font-medium text-slate-600">
                                Your response: {{ $notification->data['response'] === 'returned' ? 'Items returned' : 'Items not returned' }}.
                            </p>
                        @endif
                        @if(!empty($notification->data['action_url']))
                            <div class="mt-4">
                                <a href="{{ $notification->data['action_url'] }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800">View details</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Notification page JavaScript to handle select-all, checkbox sync, and deletion confirmation.
    document.addEventListener('DOMContentLoaded', function() {
        var selectAll = document.getElementById('selectAllNotifications');
        var checkboxes = document.querySelectorAll('.notification-checkbox');
        var deleteForm = document.getElementById('notificationsDeleteForm');

        if (!deleteForm || checkboxes.length === 0) {
            return;
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAll.checked;
                });
            });
        }

        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (selectAll) {
                    selectAll.checked = Array.from(checkboxes).every(function(box) {
                        return box.checked;
                    });
                }
            });
        });

        deleteForm.addEventListener('submit', function(event) {
            var selectedCheckboxes = Array.from(checkboxes).filter(function(checkbox) {
                return checkbox.checked;
            });

            if (selectedCheckboxes.length === 0) {
                return;
            }

            var selectedRequestNotifications = selectedCheckboxes.filter(function(checkbox) {
                return checkbox.dataset.approvalRequest === '1';
            });

            var confirmationMessage = selectedRequestNotifications.length > 0
                ? 'You are deleting approval request notification(s). This may clear pending reservation requests. Do you want to continue?'
                : 'Delete selected notification(s)?';

            if (!confirm(confirmationMessage)) {
                event.preventDefault();
            }
        });
    });
</script>
@endpush
@endsection
