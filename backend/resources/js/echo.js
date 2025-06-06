import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws'],
});


// window.Echo = new Echo({
//     broadcaster: "reverb",
//     key: import.meta.env.VITE_REVERB_APP_KEY || "pnxogxkyluuvpe7i4mhn",
//     wsHost: import.meta.env.VITE_REVERB_HOST || "localhost",
//     wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
//     wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 443,
//     forceTLS: (import.meta.env.VITE_REVERB_SCHEME || "http") === "https",
//     enabledTransports: ["ws"],
// });
