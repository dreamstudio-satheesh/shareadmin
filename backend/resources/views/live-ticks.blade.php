<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tick Reverb Test</title>
</head>
<body>
    <h2>📡 Listening for Reverb Tick Updates</h2>
    <div id="log" style="margin-top: 1rem; font-family: monospace;"></div>

    <!-- Load Laravel Echo from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>

    <script>
        // Connect to Reverb WebSocket
        const echo = new Echo({
            broadcaster: 'reverb',
            key: 'pnxogxkyluuvpe7i4mhn',
            wsHost: window.location.hostname,
            wsPort: 6001,
            wssPort: 6001,
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
        });

        // Listen to 'ticks' channel
        echo.channel('ticks')
            .listen('.TickUpdate', (e) => {
                console.log('✅ Tick received:', e.tick);
                const div = document.createElement('div');
                div.innerText = `${e.tick.symbol}: ₹${e.tick.lp}`;
                document.getElementById('log').prepend(div);
            });
    </script>
</body>
</html>
