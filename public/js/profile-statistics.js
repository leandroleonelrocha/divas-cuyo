(() => {
    const script = document.querySelector('script[data-event-url]');
    if (!script) return;
    // Bootstrap separately so tracking never embeds a cached visitor/session token.
    let token;
    const getToken = () => token ??= fetch(script.dataset.sessionUrl, {
        credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' }
    }).then(response => {
        if (!response.ok) throw new Error('Tracking unavailable');
        return response.json();
    }).then(data => data.token);
    const send = event => getToken().then(csrf => fetch(script.dataset.eventUrl, {
        method: 'POST', credentials: 'same-origin', keepalive: true,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ event })
    })).catch(() => {}); // Never interfere with public navigation or WhatsApp.
    let visited = false;
    const visit = () => {
        if (!visited && document.visibilityState === 'visible') {
            visited = true;
            send('visit');
        }
    };
    document.addEventListener('visibilitychange', visit);
    visit();
    document.addEventListener('click', event => {
        if (event.target.closest('[data-stat-whatsapp]')) send('whatsapp');
    });
})();
