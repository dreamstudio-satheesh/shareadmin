<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tick Broadcast Test</title>
    @vite('resources/js/app.js')
    <script type="module">
        window.Echo.channel('ticks')
            .listen('.TickUpdate', (e) => {
                console.log('✅ Tick received:', e.tick);
                const div = document.createElement('div');
                div.innerText = `${e.tick.symbol} - ${e.tick.lp} @ ${new Date().toLocaleTimeString()}`;
                document.getElementById('log').prepend(div);
            });
    </script>
</head>
<body>
    <h1>📡 Listening for Ticks...</h1>
    <div id="log" style="margin-top: 20px; font-family: monospace;"></div>
</body>
</html>
