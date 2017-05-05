<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
    <style>
        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            background: #181818;
            display: table;
            font-size: 24px;
            font-family: system, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: #a0a0a0;
        }

        .container {
            display: table-cell;
            vertical-align: middle;
        }

        .content {
            padding: 76px;
        }

        .title {
            font-weight: 100;
            font-size: 48px;
            margin-bottom: 40px;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <div class="title">@yield('title')</div>
        <div class="details">@yield('details')</div>
    </div>
</div>
</body>
</html>
