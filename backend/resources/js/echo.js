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


// window.Echo = new Echo({
//     broadcaster: 'reverb',
//     key: import.meta.env.VITE_REVERB_APP_KEY,
//     wsHost: import.meta.env.VITE_REVERB_HOST,
//     wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
//     wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws'],
// });
// enabledTransports: ['ws', 'wss'],