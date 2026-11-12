@php
    $flow = $flow ?? null;
@endphp
@if ($flow)
    <section class="panel panel--pad flow-banner is-{{ $flow['tone'] }} fade-up">
        <div class="flow-banner__copy">
            <p class="flow-banner__eyebrow">
                @if ($flow['tone'] === 'approval')
                    Pending approval
                @elseif ($flow['tone'] === 'waiting')
                    Waiting
                @elseif ($flow['tone'] === 'done')
                    Complete
                @else
                    Your next step
                @endif
            </p>
            <h2 class="section-heading">{{ $flow['title'] }}</h2>
            <p class="section-copy">{{ $flow['detail'] }}</p>
        </div>
        @if (! empty($flow['action_url']) && ! empty($flow['action_label']))
            <div class="flow-banner__action">
                <a href="{{ $flow['action_url'] }}" class="btn btn-primary btn-sm">{{ $flow['action_label'] }}</a>
            </div>
        @endif
    </section>
@endif
