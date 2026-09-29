<x-mail::message>
Hey {{ $user->name }},

The email address of your {{ config('app.name') }} account was changed from {{ $previousEmail }} to {{ $newEmail }}.

If this wasn't expected, contact us right away.
</x-mail::message>
