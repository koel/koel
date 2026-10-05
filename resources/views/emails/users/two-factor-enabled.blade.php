<x-mail::message :branding="$branding">
Hey {{ $user->name }},

Two-factor authentication was turned on for your {{ $branding->name }} account.

If this wasn't expected, change your password right away.
</x-mail::message>
