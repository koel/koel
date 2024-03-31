@extends('base')

@section('title', 'Koel')

@push('scripts')
    <script>
        window.MAILER_CONFIGURED = @json(mailer_configured());
        window.SSO_PROVIDERS = @json(collect_sso_providers());
        window.AUTH_TOKEN = @json($token);
    </script>
    @vite(['resources/assets/js/app.ts'])
@endpush
