<x-mail::message>
Hey hey,

{{ $invitee->invitedBy->name }} has invited you to join them on {{ config('app.name') }}.
Click the button below to accept the invitation.

<x-mail::button :url="$url">
    Accept Invitation
</x-mail::button>

Enjoy!
</x-mail::message>
