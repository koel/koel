<!DOCTYPE html>
<html>
<head>
    <title>Authentication successful!</title>
    <meta charset="utf-8">
</head>
<body>
    <h3>Perfecto!</h3>

    <p>Koel has successfully connected to your Last.fm account and is now restarting for the exciting features.</p>
    <p>This window will automatically close in 3 seconds.</p>

    <script>
        window.opener.onbeforeunload = function () {};
        window.opener.location.reload(false);

        window.setTimeout(function () {
            window.close();
        }, 3000);
    </script>
</body>
</html>
