// import './bootstrap';

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;


window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'pnxogxkyluuvpe7i4mhn',      
    wsHost: '138.199.213.241',        
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,                  
    enabledTransports: ['ws'],
});

