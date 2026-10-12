<x-mail::message :branding="$branding">
Hey {{ $user->name }},

Someone asked to reset the password of your {{ $branding->name }} account.
If that was you, use the button below to choose a new one. The link expires in {{ $expiresInMinutes }} minutes.

<x-mail::button :url="$url">
    Reset Password
</x-mail::button>

If you didn't ask for this, simply ignore this email.
</x-mail::message>
