<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>📈 Live Ticks</title>
    @vite('resources/js/app.js')
    <script type="module">
        window.addEventListener('DOMContentLoaded', () => {
            window.Echo.channel('ticks')
                .listen('.TickUpdate', (e) => {
                    const {
                        token,
                        symbol,
                        lp,
                        ts
                    } = e.tick;

                    const rowId = `tick-${token}`;
                    let row = document.getElementById(rowId);

                    const content =
                        `${symbol} (Token: ${token}) — ₹${lp} — ${new Date(parseInt(ts) * 1000).toLocaleTimeString()}`;

                    if (row) {
                        row.textContent = content;
                    } else {
                        row = document.createElement('div');
                        row.id = rowId;
                        row.textContent = content;
                        row.className = 'tick-row';
                        log.appendChild(row);
                    }


                });
        });
    </script>
    <style>
        body {
            font-family: monospace;
            padding: 1rem;
            background: #f9f9f9;
        }

        #log {
            display: grid;
            gap: 6px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            background: #fff;
        }

        .tick-row {
            padding: 8px;
            background: #eef6fb;
            border-left: 3px solid #38bdf8;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <h2>📡 Live Tick Dashboard</h2>
    <div id="log"></div>
</body>

</html>
