<!DOCTYPE html>
<html>
<head>
    <title>Koel</title>

    <meta name="description" content="{{ config('app.tagline') }}">
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" href="/public/img/apple-touch-icon-precomposed.png">

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100&subset=latin,latin-ext,vietnamese,greek-ext,greek,cyrillic,cyrillic-ext" rel="stylesheet">

    <link rel="stylesheet" href="{{ App::rev('css/vendors.css') }}">
    <link rel="stylesheet" href="{{ App::rev('css/app.css') }}">
</head>
<body>

    <app></app>

    <script src="{{ App::rev('js/vendors.js') }}"></script>
    <script src="{{ App::rev('js/main.js') }}"></script>
</body>
</html>
