@extends('base')

@section('title', koel_branding('name'))

@push('scripts')
    <script>
        @php
            $koelExtraGlobals = [
                'mailer_configured' => mailer_configured(),
                'sso_providers' => collect_sso_providers(),
                'sso_oidc_label' => config('services.oidc.button_label'),
                'accepted_audio_extensions' => collect_accepted_audio_extensions(),
            ];
        @endphp
        Object.assign(window.KOEL, @json($koelExtraGlobals));

        @if (session()->has('demo_account'))
            window.KOEL.demo_account = @json(session('demo_account'));
        @elseif (isset($token))
            window.KOEL.auth_token = @json($token);
        @elseif (isset($twoFactorLoginToken))
            window.KOEL.two_factor_login_token = @json($twoFactorLoginToken);
        @endif
    </script>
    @vite(['resources/assets/js/app.ts'])
@endpush
