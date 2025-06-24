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
     * Initialise the Typebot widget and attach the end chat handler.
     */
    function init() {
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

    function waitForTypebotWidget() {
        return new Promise(function (resolve) {
            if (window.TypebotWidget) {
                resolve();
                return;
            }
            var timer = setInterval(function () {
                if (window.TypebotWidget) {
                    clearInterval(timer);
                    resolve();
                }
            }, 50);
        });
    }

    function start() {
        waitForTypebotWidget().then(function () {
            window.TypebotWidget.ready(function () {
                init();

                var endBtn = document.getElementById('mhtp-end-session');
                if (endBtn) {
                    endBtn.addEventListener('click', async function () {
                        console.log('¡click finalizar! enviando store-conversation');
                        try {
                            await TypebotWidget.sendCommand({ command: 'store-conversation' });
                        } catch (e) {
                            console.error('Typebot sendCommand falló:', e);
                        }
                    });
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
