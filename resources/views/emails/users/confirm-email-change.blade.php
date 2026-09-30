<x-mail::message :branding="$branding">
Hey {{ $user->name }},

Someone asked to change the email address of your {{ $branding->name }} account to {{ $newEmail }}.
If that was you, use the button below to review and confirm it. The link expires in 24 hours.

<x-mail::button :url="$url">
    Review Change
</x-mail::button>

If you didn't ask for this, simply ignore this email.
</x-mail::message>
