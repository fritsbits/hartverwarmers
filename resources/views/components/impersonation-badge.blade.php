@if(session('original_user_id'))
    @php $impersonatedUser = auth()->user(); @endphp
    <div
        id="impersonation-badge"
        role="status"
        aria-label="Je bekijkt de site als {{ $impersonatedUser->full_name }}"
        style="
            position: fixed;
            bottom: 90px;
            left: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #92400e;
            color: white;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-family: system-ui, sans-serif;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            z-index: 9999;
        "
    >
        <x-user-avatar :user="$impersonatedUser" size="xs" />
        <span>{{ $impersonatedUser->full_name }}</span>
        <span style="
            display: inline-block;
            padding: 1px 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
        ">{{ $impersonatedUser->role }}</span>
        <form method="POST" action="{{ route('admin.impersonate.stop') }}" style="display: inline; margin: 0;">
            @csrf
            <button type="submit" style="
                background: rgba(255,255,255,0.9);
                color: #92400e;
                border: none;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
                font-family: inherit;
            ">Stop</button>
        </form>
    </div>
@endif
