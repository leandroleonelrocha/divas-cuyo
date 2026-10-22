// A restored history snapshot must revalidate acceptance with the server.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});
