<x-mail::message :branding="$branding">
Hey {{ $user->name }},

The email address of your {{ $branding->name }} account was changed from {{ $previousEmail }} to {{ $newEmail }}.

If this wasn't expected, change your password right away.
</x-mail::message>
