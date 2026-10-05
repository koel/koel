<x-mail::message :branding="$branding">
Hey {{ $user->name }},

Two-factor authentication was turned off for your {{ $branding->name }} account.

If this wasn't expected, change your password and turn two-factor authentication back on right away.
</x-mail::message>
