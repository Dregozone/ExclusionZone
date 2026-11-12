<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<meta name="theme-color" content="#0c1315" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=geist:400,500,600,700|geist-mono:400,500,600|instrument-serif:400,400i|big-shoulders-display:300,500,800&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- The Zone is dark-only. Survivor display preferences (reduce effects, high contrast) apply before first paint. --}}
<script>
    (() => {
        const root = document.documentElement;

        try {
            window.localStorage.setItem('flux.appearance', 'dark');
        } catch (error) {
            // Storage blocked: the class below still forces the dark theme.
        }

        root.classList.add('dark');

        const read = (key) => {
            try {
                return window.localStorage.getItem(key) === '1';
            } catch (error) {
                return false;
            }
        };

        root.classList.toggle('ez-reduce-effects', read('ez.reduce-effects'));
        root.classList.toggle('ez-high-contrast', read('ez.high-contrast'));
    })();
</script>
