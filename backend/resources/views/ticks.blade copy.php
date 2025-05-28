<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Live Ticks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: sans-serif;
            padding: 2rem;
            background-color: #f5f7fa;
        }

        h1 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #333;
        }

        #ticks {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .tick {
            padding: 1rem;
            background-color: #fff;
            border-left: 5px solid #22c55e;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .tick strong {
            display: inline-block;
            width: 80px;
        }
    </style>
</head>

<body>
    <h1>📡 Live Ticks</h1>
    <div id="ticks">Waiting for updates...</div>

    <!-- ✅ Laravel Echo + Pusher (via CDN) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        const ticksContainer = document.getElementById('ticks');

        // ✅ Define Reverb-compatible Pusher adapter
        window.Pusher = Pusher;

        // ✅ Proper Echo init without cluster
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: 'local', // 👈 Use 'local' if REVERB_APP_KEY isn't needed
            wsHost: 'localhost',
            wsPort: 8080,
            wssPort: 8080,
            forceTLS: false,
            disableStats: true,
            encrypted: false,
            cluster: 'mt1', // 👈 required even for self-hosted Reverb
            enabledTransports: ['ws'],
        });
    </script>

</body>

</html>