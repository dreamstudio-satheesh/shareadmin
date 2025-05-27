<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Live Ticks</title>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '859fcbbc5d187aa8436e',
            cluster: 'ap2',
            forceTLS: true,
        });

         Echo.channel('ticks')
            .listen('.TickUpdated', (e) => {
                const el = document.getElementById('tick-feed');
                const item = document.createElement('li');
                item.innerText = `Token ${e.token} → ₹${e.data.ltp} at ${e.data.time}`;
                el.prepend(item);
            });
    </script>


</head>

<body>
    <h2>Live Ticks</h2>
    <ul id="tick-feed"></ul>


</body>

</html>
