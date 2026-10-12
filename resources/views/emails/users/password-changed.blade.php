<x-mail::message :branding="$branding">
Hey {{ $user->name }},

The password of your {{ $branding->name }} account was changed.

If this wasn't expected, reset your password right away.
</x-mail::message>
