@env('local')
    @auth
        @if(auth()->user()->isAdmin() && isset($queueStatus))
            @php
                $colors = match($queueStatus) {
                    'healthy' => ['bg' => '#166534', 'dot' => '#4ade80', 'label' => 'Queue ok'],
                    'starting' => ['bg' => '#92400e', 'dot' => '#fbbf24', 'label' => 'Starting queue worker…'],
                    'failed' => ['bg' => '#991b1b', 'dot' => '#fca5a5', 'label' => 'Queue down — run composer run dev'],
                    default => null,
                };
            @endphp

            @if($colors)
                <div
                    id="queue-badge"
                    style="
                        position: fixed;
                        bottom: 50px;
                        left: 12px;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        background: {{ $colors['bg'] }};
                        color: white;
                        padding: 4px 10px;
                        border-radius: 999px;
                        font-size: 12px;
                        font-family: system-ui, sans-serif;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
                        z-index: 9999;
                        transition: opacity 0.5s ease;
                        {{ $queueStatus === 'failed' ? 'animation: queue-badge-pulse 2s infinite;' : '' }}
                    "
                >
                    <span style="
                        width: 7px;
                        height: 7px;
                        background: {{ $colors['dot'] }};
                        border-radius: 50%;
                        display: inline-block;
                    "></span>
                    {{ $colors['label'] }}
                </div>

                @if($queueStatus === 'healthy')
                    <script>
                        setTimeout(() => {
                            const badge = document.getElementById('queue-badge');
                            if (badge) {
                                badge.style.opacity = '0';
                                setTimeout(() => badge.remove(), 500);
                            }
                        }, 3000);
                    </script>
                @endif

                @if($queueStatus === 'failed')
                    <style>
                        @keyframes queue-badge-pulse {
                            0%, 100% { opacity: 1; }
                            50% { opacity: 0.7; }
                        }
                    </style>
                @endif
            @endif
        @endif
    @endauth
@endenv
