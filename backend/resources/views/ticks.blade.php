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

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: 'w0nvxjcnxxxt33sfmyl0',
            cluster: "undefined", // ✅ disable cloud cluster requirement
            wsHost: 'localhost',
            wsPort: 8080,
            wssPort: 8080,
            forceTLS: false,
            disableStats: true,
            enabledTransports: ['ws'],
        });


        window.Echo.channel('ticks')
            .listen('.TickUpdated', (e) => {
                console.log("Received tick:", e);

                if (ticksContainer.innerText.includes('Waiting')) {
                    ticksContainer.innerHTML = '';
                }

                const el = document.createElement('div');
                el.className = 'tick';
                el.innerHTML = `
                    <strong>Token:</strong> ${e.token}<br>
                    <strong>LTP:</strong> ₹${e.data.ltp}<br>
                    <strong>Time:</strong> ${e.data.time}
                `;
                ticksContainer.prepend(el);
            });
    </script>
</body>

</html>
