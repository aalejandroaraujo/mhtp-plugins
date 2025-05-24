/**
 * JavaScript to track actual session duration and send it to the server
 */
jQuery(document).ready(function($) {
    // Only run on chat interface page
    if ($('#mhtp-chat-messages').length === 0) {
        return;
    }
    
    // Variables to track session duration
    let sessionStartTime = new Date();
    let sessionDurationMinutes = 0;
    let sessionDurationSeconds = 0;
    
    // Function to update session duration in user meta when session ends
    function updateSessionDuration() {
        // Calculate actual session duration in minutes and seconds
        const endTime = new Date();
        const durationMs = endTime - sessionStartTime;
        
        // Calculate total seconds
        const totalSeconds = Math.floor(durationMs / 1000);
        
        // Split into minutes and seconds
        sessionDurationMinutes = Math.floor(totalSeconds / 60);
        sessionDurationSeconds = totalSeconds % 60;
        
        // Get expert ID and session ID
        const expertId = getExpertId();
        const sessionId = getSessionId();
        
        if (!expertId) {
            console.error('Missing expert ID, cannot update session duration');
            return;
        }
        
        // Send duration to server
        $.ajax({
            url: mhtpChat.ajaxurl,
            type: 'POST',
            data: {
                action: 'mhtp_update_session_duration',
                nonce: mhtpChat.nonce,
                expert_id: expertId,
                session_id: sessionId,
                duration_minutes: sessionDurationMinutes,
                duration_seconds: sessionDurationSeconds
            },
            success: function(response) {
                console.log('Session duration updated:', sessionDurationMinutes, 'minutes,', sessionDurationSeconds, 'seconds');
            },
            error: function(xhr, status, error) {
                console.error('Error updating session duration:', error);
            }
        });
    }
    
    // Get expert ID from the page
    function getExpertId() {
        // Try to get from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('expert_id')) {
            return urlParams.get('expert_id');
        }
        
        // If not in URL, try to extract from the page
        const expertLink = $('.mhtp-expert-info').closest('a');
        if (expertLink.length && expertLink.attr('href')) {
            const hrefParams = new URLSearchParams(expertLink.attr('href').split('?')[1]);
            if (hrefParams.has('expert_id')) {
                return hrefParams.get('expert_id');
            }
        }
        
        // Try to get from expert name element
        const expertName = $('.mhtp-expert-name').text().trim();
        if (expertName) {
            // This is a fallback that at least provides the expert name
            return 'name:' + expertName;
        }
        
        return null;
    }
    
    // Get session ID from the page
    function getSessionId() {
        const sessionIdElement = $('.mhtp-session-info .mhtp-session-detail:first-child .mhtp-session-value');
        if (sessionIdElement.length) {
            return sessionIdElement.text().trim();
        }
        return null;
    }
    
    // Hook into the end session button
    const endSessionButton = $('#mhtp-end-session');
    const confirmEndSessionButton = $('#mhtp-confirm-end-session');
    
    if (confirmEndSessionButton.length) {
        // Store original click handler
        const originalClickHandler = confirmEndSessionButton.prop('onclick');
        
        // Add our handler
        confirmEndSessionButton.on('click', function() {
            updateSessionDuration();
            
            // Call original handler if it exists
            if (originalClickHandler) {
                originalClickHandler.call(this);
            }
        });
    }
    
    // Also hook into the session timer end (when it reaches zero)
    // This is a backup in case the user doesn't click the end button
    const sessionTimerElement = $('#mhtp-session-timer');
    if (sessionTimerElement.length) {
        // Check timer every second
        setInterval(function() {
            const timerText = sessionTimerElement.text();
            if (timerText === '00:00') {
                updateSessionDuration();
            }
        }, 1000);
    }
    
    // Also update duration on page unload as a last resort
    $(window).on('beforeunload', function() {
        updateSessionDuration();
    });
});
