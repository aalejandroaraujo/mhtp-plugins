(function () {

    function attachEndHandler() {
        var btn = document.getElementById('mhtp-end-session');
        if (!btn) return;
        btn.addEventListener('click', async function () {
            try {
                await window.TypebotWidget.sendCommand({ command: 'store-conversation' });
            } catch (e) {
                console.error('Typebot command failed:', e);
            }
        });
    }

    function waitForWidget(cb) {
        const t0 = Date.now();
        (function poll() {
            if (window.TypebotWidget) return cb();
            if (Date.now() - t0 < 5000) return setTimeout(poll, 100);
            console.error('TypebotWidget never loaded');
        })();
    }

    waitForWidget(function () {
        attachEndHandler();
    });
})();
