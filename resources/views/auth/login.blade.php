<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Koel</title>
        <meta name="description" content="{{ config('app.tagline') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="public/img/apple-touch-icon-precomposed.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,300,100&subset=latin">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">

        <style>
            *, *:before, *:after {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                background: #111;
                color: #ccc;
                display: flex;
                display: -webkit-flex;
                display: -moz-flex;
                align-items: center;
                -webkit-align-items: center;
                -moz-align-items: center;
                justify-content: center;
                -webkit-justify-content: center;
                -moz-justify-content: center;
                font: 14px/18px 'Roboto', sans-serif;
                font-weight: 300;
            }

            form {
                width: 300px;
                padding: 24px;
                background: rgba(255,255,255,.08);
                border-radius: 8px;
                border: 1px solid #333;
            }

            form.error {
                border-color: #8e4947;
            }

            form:before {
                content: " ";
                display: block;
                background: url(/public/img/logo.svg) center top no-repeat;
                background-size: 156px;
                height: 172px;

            }

            button, input {
                -webkit-appearance: none;
                display: block;
                margin-top: 12px;
                padding: 8px;
                border: 0;
                background: #fff;
                outline: none;
                width: 100%;
                border-radius: 3px;
                font-family: 'Roboto', sans-serif;
                font-size: 14px;
            }

            input:focus {
                background: lightyellow;
            }

            button {
                background: #0191f7;
                color: #fff;
            }

            button:active {
                box-shadow: inset 0px 10px 10px -10px rgba(0,0,0,1);
            }
        </style>
    </head>
    <body>
        <form method="post" action="/login" 
        @if (count($errors))
        class="error"
        @endif>
            <input type="email" name="email" placeholder="Email Address" autofocus>
            <input type="password" name="password" placeholder="Password">
            <button type="submit">Log In</button>
            {!! csrf_field() !!}
        </form>
    </body>
</html>
