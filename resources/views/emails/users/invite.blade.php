<x-mail::message :branding="$branding">
Hey hey,

{{ $invitee->invitedBy->name }} has invited you to join them on {{ $branding->name }}.
Click the button below to accept the invitation. The invitation expires in a week.

<x-mail::button :url="$url">
    Accept Invitation
</x-mail::button>

Enjoy!
</x-mail::message>
