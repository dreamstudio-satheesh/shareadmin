<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Laravel Reverb Test</title>
    @vite('resources/js/app.js')
    <script type="module">
        window.Echo.channel('test-channel')
            .listen('TestEvent', (e) => {
                console.log('Broadcasted message:', e.message);
            });



        // window.Echo.channel('test-channel')
        //     .listen('TestEvent', (e) => {
        //         console.log('Broadcast received:', e.message);
        //         const log = document.getElementById('log');
        //         log.innerHTML += `<p>${new Date().toLocaleTimeString()}: ${e.message}</p>`;
        //     });
    </script>
</head>

<body>
    <h1>Listening for Broadcasts...</h1>
    <div id="log" style="margin-top: 20px; font-family: monospace;"></div>
</body>

</html>
