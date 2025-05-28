<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Reverb Test</title>
    @vite('resources/js/app.js')
    <script type="module">
        // Wait for DOM to load
        window.addEventListener('DOMContentLoaded', () => {
            window.Echo.channel('ticks')
                .listen('.TickUpdate', (e) => {
                    console.log('Live tick received:', e.tick);

                    const log = document.getElementById('log');
                    const { token, ltp, time } = e.tick;

                    const row = document.createElement('div');
                    row.textContent = `Token: ${token} — ₹${ltp} — ${new Date(time).toLocaleTimeString()}`;
                    log.prepend(row); // latest first
                });
        });
    </script>
    <style>
        body {
            font-family: monospace;
            padding: 1rem;
            background: #f4f4f4;
        }
        #log div {
            margin-bottom: 5px;
            padding: 4px 8px;
            background: #fff;
            border-left: 3px solid #3490dc;
        }
    </style>
</head>
<body>
    <h2>📡 Live Tick Feed</h2>
    <div id="log"></div>
</body>
</html>
