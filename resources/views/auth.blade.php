<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" type="image/png" href="favicon.png"/>
        <title>Trofit Partner</title>
        <style>
            .antialiased {
                background: #F2F2F2;
            }
        </style>
        <link rel="stylesheet" href="{{ mix('css/styles.css') }}" />
    </head>
    <body class="antialiased">
        <div id="app">
            <router-view></router-view>
        </div>
    </body>
    <script src="{{ mix('js/auth-app.js') }}"></script>
</html>
