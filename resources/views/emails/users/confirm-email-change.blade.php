<x-mail::message>
Hey {{ $user->name }},

Someone asked to change the email address of your {{ config('app.name') }} account to {{ $newEmail }}.
If that was you, confirm it with the button below. The link works for 24 hours.

<x-mail::button :url="$url">
    Confirm Email Address
</x-mail::button>

If you didn't ask for this, ignore this email and nothing will change.
</x-mail::message>
