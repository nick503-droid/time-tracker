<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand:    '#4A7C59',
                        brandHov: '#3B6347',
                        bgPage:   '#F5F4F0',
                        bgCard:   '#FFFFFF',
                        muted:    '#6B7280',
                        subtle:   '#E8E6E1',
                        warn:     '#92622A',
                        warnBg:   '#FDF3E3',
                        warnBrd:  '#E8C97A',
                        danger:   '#9B3535',
                        dangerBg: '#FDF0F0',
                        dangerBrd:'#E8A0A0',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>

    @yield('styles')
</head>
<body class="bg-bgPage text-gray-800 antialiased min-h-screen flex flex-col">

    <main class="flex-grow flex items-center justify-center p-6">
        @yield('content')
    </main>

</body>
</html>
