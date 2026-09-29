<x-mail::message>
Hey {{ $user->name }},

An administrator changed the email address of your {{ config('app.name') }} account from {{ $previousEmail }} to {{ $user->email }}.

If this wasn't expected, contact us right away.
</x-mail::message>
