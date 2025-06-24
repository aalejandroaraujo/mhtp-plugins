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
        var TB = window.Typebot || window.typebot;

        if (!TB || typeof TB.initStandard !== 'function') {
            console.error('Typebot library not loaded');
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

        // Listen for the end chat button to trigger storing the conversation
        var endBtn = document.getElementById('end-chat-btn');
        if (endBtn) {
            endBtn.addEventListener('click', async function () {
                if (!window.Typebot || typeof window.Typebot.sendCommand !== 'function') {
                    console.error('Typebot.sendCommand is unavailable.');
                    return;
                }
                try {
                    await window.Typebot.sendCommand({ command: 'store-conversation' });
                } catch (error) {
                    console.error('Failed to send store-conversation command to Typebot:', error);
                }
            });
        } else {
            console.error('End chat button with ID end-chat-btn not found.');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
