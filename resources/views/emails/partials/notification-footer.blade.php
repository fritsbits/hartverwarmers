<table align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 40px;">
    <tr>
        <td align="center" style="font-size: 13px; color: #756C65; line-height: 1.6;">
            <a href="{{ route('profile.notifications') }}" style="color: #756C65; text-decoration: underline;">Meldingen beheren</a>
            &middot;
            <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('notifications.unsubscribe', ['user' => $notifiable->id, 'type' => $type]) }}" style="color: #756C65; text-decoration: underline;">Uitschrijven</a>
        </td>
    </tr>
</table>
