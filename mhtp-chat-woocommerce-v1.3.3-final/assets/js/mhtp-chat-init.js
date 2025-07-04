(function () {
    /**
     * Helper to read query string parameters
     */
    function getParam(name) {
        return new URLSearchParams(window.location.search).get(name);
    }

    /**
     * Determine the current Expert ID based on localized data or query string.
     */
    function getExpertId() {
        if (window.mhtpChatData && parseInt(window.mhtpChatData.ExpertId, 10)) {
            return parseInt(window.mhtpChatData.ExpertId, 10);
        }
        var fromUrl = getParam('ExpertId');
        if (fromUrl) {
            return parseInt(fromUrl, 10);
        }
        console.warn('ExpertId missing. Falling back to default 392');
        return 392;
    }

    /**
     * Get the current user identifier (email) if available.
     */
    function getUserId() {
        if (window.mhtpChatData && window.mhtpChatData.UserId) {
            return window.mhtpChatData.UserId;
        }
        var fromUrl = getParam('UserId');
        if (fromUrl) {
            return fromUrl;
        }
        return '';
    }

    /**
     * Initialise the Typebot widget.
     */
    function initChat() {
        var expertId = getExpertId();
        var userId = getUserId();

        // Support both "Typebot" and "typebot" globals just in case.
        var TB = window.TypebotWidget;

        if (!TB || typeof TB.initStandard !== 'function') {
            console.error('TypebotWidget not loaded');
            return;
        }

        try {
            TB.initStandard({
                variables: { ExpertId: expertId, UserId: userId }
            });

            // Ensure Typebot is accessible globally for later commands
            window.Typebot = TB;
        } catch (e) {
            console.error('Typebot initialization failed', e);
            return;
        }

    }

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
        initChat();
        attachEndHandler();
    });
})();
