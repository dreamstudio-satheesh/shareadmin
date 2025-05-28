<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Ticks</title>
    @vite('resources/js/app.js')
    <script type="module">
        window.addEventListener('DOMContentLoaded', () => {
            const log = document.getElementById('log');

            window.Echo.channel('ticks')
                .listen('.TickUpdate', (e) => {
                    const { token, ltp, time } = e.tick;

                    // Use token as unique ID for display row
                    const rowId = `tick-${token}`;
                    let row = document.getElementById(rowId);

                    const content = `Token: ${token} — ₹${ltp} — ${new Date(time).toLocaleTimeString()}`;

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
        }
        .tick-row {
            padding: 8px;
            background: #fff;
            border-left: 3px solid #38bdf8;
        }
    </style>
</head>
<body>
    <h2>📈 Live Tick Dashboard</h2>
    <div id="log"></div>
</body>
</html>
