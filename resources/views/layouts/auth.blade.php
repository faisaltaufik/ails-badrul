<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $pageTitle }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    </head>
    <body>
        @yield('content')

        @stack('scripts')
    </body>
</html>