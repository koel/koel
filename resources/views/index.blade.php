@extends('base')

@section('title', 'Koel')

@push('scripts')
    <script>
        window.MAILER_CONFIGURED = @json(mailer_configured());
        window.SSO_PROVIDERS = @json(collect_sso_providers());
        window.AUTH_TOKEN = @json($token);
        window.ACCEPTED_AUDIO_EXTENSIONS = @json(collect_accepted_audio_extensions());
    </script>
    @vite(['resources/assets/js/app.ts'])
@endpush
