<!DOCTYPE html>
<html>
<head>
    <title>Koel</title>

    <meta name="description" content="{{ config('app.tagline') }}">
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    
    <link rel="manifest" href="{{ App::staticUrl('manifest.json') }}" />
    <link rel="icon" type="image/x-icon" href="{{ App::staticUrl('public/img/favicon.ico') }}" />
    <link rel="icon" href="{{ App::staticUrl('public/img/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ App::staticUrl('public/img/icon.png') }}">

    <link rel="stylesheet" href="{{ App::rev('css/vendors.css') }}">
    <link rel="stylesheet" href="{{ App::rev('css/app.css') }}">
</head>
<body>
    <script>
         !function(m,y,k,o,e,l){m.GoogleAnalyticsObject=k;m[k]||(m[k]=function(){
        (m[k].q=m[k].q||[]).push(arguments)});m[k].l=+new Date;e=y.createElement(o);
        l=y.getElementsByTagName(o)[0];e.src='//www.google-analytics.com/analytics.js';
        l.parentNode.insertBefore(e,l)}(window,document,'ga','script');

        ga('create', 'UA-73152563-1', 'demo.koel.phanan.net');
        ga('send', 'pageview');
    </script>
    
    <app></app>

    <script src="{{ App::rev('js/vendors.js') }}"></script>
    <script src="{{ App::rev('js/main.js') }}"></script>
</body>
</html>
