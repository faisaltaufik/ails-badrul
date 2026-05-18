<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Dashboard AILS</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
    </head>
    <body>
        <div class="app-shell">
            <div class="workspace">
                @include('dashboard.partials.sidebar')

                <main class="content-pane">
                    @include('dashboard.partials.topbar')
                    @include('dashboard.partials.flash-messages')

                    <div class="main-grid">
                        @switch($page)
                            @case('dashboard')
                                @include('dashboard.pages.dashboard')
                                @break

                            @case('sintak')
                                @include('dashboard.pages.sintak')
                                @break

                            @case('progress')
                                @include('dashboard.pages.progress')
                                @break

                            @case('help')
                                @include('dashboard.pages.help')
                                @break
                        @endswitch

                        @include('dashboard.partials.footer-note')
                    </div>
                </main>
            </div>
        </div>

        @include('dashboard.partials.scripts')
    </body>
</html>
