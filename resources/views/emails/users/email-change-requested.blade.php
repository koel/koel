<x-mail::message>
Hey {{ $user->name }},

Someone asked to change the email address of your {{ config('app.name') }} account to {{ $newEmail }}.
Nothing changes until the new address is confirmed.

If this wasn't you, change your password right away.
</x-mail::message>
