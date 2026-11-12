@php
    $state = $state ?? ((bool) ($done ?? false) ? 'done' : 'pending');
    $label = $label ?? 'Status';
    $detail = $detail ?? match ($state) {
        'done' => 'Done',
        'approval' => 'Pending approval',
        'waiting' => 'Waiting',
        default => 'Pending',
    };
@endphp
<div class="stage-status is-{{ $state }}">
    <span class="stage-status__icon" aria-hidden="true">
        @if ($state === 'done')
            <svg viewBox="0 0 20 20" width="18" height="18" fill="none">
                <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="1.6"/>
                <path d="M6 10.2l2.4 2.4L14 7.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        @elseif ($state === 'approval')
            <svg viewBox="0 0 20 20" width="18" height="18" fill="none">
                <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="1.6"/>
                <path d="M10 6v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="10" cy="13.5" r="1.1" fill="currentColor"/>
            </svg>
        @else
            <svg viewBox="0 0 20 20" width="18" height="18" fill="none">
                <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="1.6" stroke-dasharray="3.5 2.5"/>
                <circle cx="10" cy="10" r="2.2" fill="currentColor"/>
            </svg>
        @endif
    </span>
    <div class="stage-status__copy">
        <p class="stage-status__label">{{ $label }}</p>
        <p class="stage-status__detail">{{ $detail }}</p>
    </div>
</div>
