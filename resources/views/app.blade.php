<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
        <meta name="theme-color" content="#344c28" />
        <meta name="color-scheme" content="light" />

        <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2" />
        <link rel="icon" type="image/png" href="/icons/icon-32.png?v=2" sizes="32x32" />
        <link rel="apple-touch-icon" href="/icons/icon-192.png?v=2" />
        <link rel="manifest" href="/manifest.json" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@400;500&family=Hanken+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head />
    </head>
    <body class="bg-surface-bg text-on-surface font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
