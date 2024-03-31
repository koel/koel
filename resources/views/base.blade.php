<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title')</title>

    <meta name="description" content="{{ config('app.tagline') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">

    <meta name="theme-color" content="#282828">
    <meta name="msapplication-navbutton-color" content="#282828">

    <link rel="manifest" href="{{ static_url('manifest.json') }}"/>
    <meta name="msapplication-config" content="{{ static_url('browserconfig.xml') }}"/>
    <link rel="icon" type="image/x-icon" href="{{ static_url('img/favicon.ico') }}"/>
    <link rel="icon" href="{{ static_url('img/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ static_url('img/icon.png') }}">

    @unless(License::isPlus())
    <script src="https://app.lemonsqueezy.com/js/lemon.js" defer></script>
    @endunless

    <script>
        // Work around for "global is not defined" error with local-storage.js
        window.global = window
    </script>
</head>
<body>
<div id="app"></div>

<noscript>It may sound funny, but Koel requires JavaScript to sing. Please enable it.</noscript>

<script>
    window.BASE_URL = @json(asset(''));
    window.IS_DEMO = @json(config('koel.misc.demo'));

    window.PUSHER_APP_KEY = @json(config('broadcasting.connections.pusher.key'));
    window.PUSHER_APP_CLUSTER = @json(config('broadcasting.connections.pusher.options.cluster'));
</script>

@stack('scripts')
</body>
</html>
