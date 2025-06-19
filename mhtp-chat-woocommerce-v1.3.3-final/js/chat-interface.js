jQuery(document).ready(function($) {
    // DOM elements
    const chatMessages = $('#mhtp-chat-messages');
    const chatInput = $('#mhtp-chat-input');
    const sendButton = $('#mhtp-send-button');
    const endSessionButton = $('#mhtp-end-session');
    const sessionTimerElement = $('#mhtp-session-timer');
    const endSessionModal = $('#mhtp-end-session-modal');
    const confirmEndSessionButton = $('#mhtp-confirm-end-session');
    const cancelEndSessionButton = $('#mhtp-cancel-end-session');
    const saveConversationCheckbox = $('#mhtp-save-conversation');
    const downloadConversationButton = $('#mhtp-download-conversation');
    const chatMain = $('.mhtp-chat-main');
    const sessionOverlay = $('#mhtp-session-overlay');

    // Chat history and loading bubble
    const HISTORY_KEY = 'chatHistory';
    let chatHistory = [];
    let loadingBubble = null;
    let loadingTimer = null;
    
    // Session variables
    let sessionActive = false; // Start as false until session is confirmed
    let sessionDuration = 45 * 60; // 45 minutes in seconds
    let sessionTimer;
    let sessionEndTime;
    let conversationMessages = []; // Array to store all messages for saving
    let sessionStarted = false; // Track if session has been started with the server
    const isTypebotOnly = chatMessages.length === 0 && chatInput.length === 0 && chatMain.find('iframe').length > 0;

    // Insert loading bubble and load history
    if (chatMessages.length) {
        loadingBubble = $('<div id="loading-bubble" class="msg loading">Conectando con tu especialista…</div>');
        chatMessages.prepend(loadingBubble);
        loadingTimer = setTimeout(hideLoadingBubble, 1000 + Math.random() * 1000);

        chatHistory = loadHistory();
        chatHistory.forEach(function(msg) {
            const ts = new Date(msg.ts);
            const time = (ts.getHours().toString().padStart(2, '0')) + ':' + (ts.getMinutes().toString().padStart(2, '0'));
            renderMessage(msg.side, msg.text, time);
        });
    }

    // Persist session information in sessionStorage
    function saveSessionState(expertId, sessionId, endTime) {
        const data = { expertId, sessionId, endTime };
        sessionStorage.setItem('mhtp_active_session', JSON.stringify(data));
    }

    function loadSessionState() {
        try {
            const raw = sessionStorage.getItem('mhtp_active_session');
            if (!raw) return null;
            const data = JSON.parse(raw);
            if (data.endTime && Date.now() < data.endTime) {
                return data;
            }
        } catch (e) {}
        return null;
    }

    function clearSessionState() {
        sessionStorage.removeItem('mhtp_active_session');
    }

    // Chat history helpers
    function loadHistory() {
        try {
            const raw = sessionStorage.getItem(HISTORY_KEY);
            const data = JSON.parse(raw);
            return Array.isArray(data) ? data : [];
        } catch (e) {
            return [];
        }
    }

    function saveHistory() {
        sessionStorage.setItem(HISTORY_KEY, JSON.stringify(chatHistory.slice(-50)));
    }

    function updateHistory(side, text) {
        chatHistory.push({ side, text, ts: Date.now() });
        chatHistory = chatHistory.slice(-50);
        saveHistory();
    }

    function hideLoadingBubble() {
        if (loadingBubble) {
            loadingBubble.remove();
            loadingBubble = null;
        }
        if (loadingTimer) {
            clearTimeout(loadingTimer);
            loadingTimer = null;
        }
    }

    function renderMessage(side, text, timestamp) {
        const messageElement = $('<div class="mhtp-message"></div>');
        messageElement.addClass(side === 'user' ? 'mhtp-message-user' : 'mhtp-message-expert');

        const contentElement = $('<div class="mhtp-message-content"></div>');
        contentElement.html('<p>' + text.replace(/\n/g, '<br>') + '</p>');

        const timeElement = $('<div class="mhtp-message-time"></div>');
        timeElement.text(timestamp);

        messageElement.append(contentElement);
        messageElement.append(timeElement);

        chatMessages.append(messageElement);
        if (side === 'expert') hideLoadingBubble();
        scrollToBottom();
    }
    
    // Initialize chat
    function initChat() {
        // Get expert ID and session ID
        const expertId = getExpertId();
        const sessionId = getSessionId();

        if (!expertId || !sessionId) {
            console.error('Missing expert ID or session ID');
            addSystemMessage('Error: No se pudo iniciar la sesión. Falta información del experto o sesión.');
            return;
        }
        const saved = loadSessionState();
        if (saved && saved.expertId === expertId) {
            // Restore existing session without hitting the server
            sessionActive = true;
            sessionEndTime = new Date(saved.endTime);
            $('.mhtp-session-info .mhtp-session-detail:first-child .mhtp-session-value').text(saved.sessionId);
            setupEventListeners();
            startSessionTimer();
            chatInput.focus();
        } else {
            // Start session with server via AJAX
            startSessionWithServer(expertId, sessionId);
        }
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
    
    // Start session with server via AJAX
    function startSessionWithServer(expertId, sessionId) {
        // Show loading message
        addSystemMessage('Iniciando sesión, por favor espera...');
        
        // Make AJAX call to start session
        $.ajax({
            url: mhtpChat.ajaxurl,
            type: 'POST',
            data: {
                action: 'mhtp_start_chat_session',
                nonce: mhtpChat.nonce,
                expert_id: expertId,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Session started successfully
                    sessionStarted = true;
                    sessionActive = true;
                    
                    // Remove loading message
                    $('.mhtp-message-system').remove();
                    
                    // Set session end time
                    sessionEndTime = new Date();
                    sessionEndTime.setMinutes(sessionEndTime.getMinutes() + 45);
                    saveSessionState(expertId, sessionId, sessionEndTime.getTime());
                    
                    // Start session timer
                    startSessionTimer();
                    
                    // Focus on input
                    chatInput.focus();
                    
                    // Set up event listeners
                    setupEventListeners();
                    
                    // Store initial welcome message
                    const welcomeMessage = $('.mhtp-message-expert').first().find('.mhtp-message-content p').text();
                    storeMessage(welcomeMessage, 'expert', getCurrentTime());
                    updateHistory('expert', welcomeMessage);
                    hideLoadingBubble();
                    
                    // Add success message
                    addSystemMessage('Sesión iniciada correctamente. ¡Bienvenido!');
                } else {
                    // Session failed to start
                    handleSessionError(response.data ? response.data.message : 'Error desconocido');
                }
            },
            error: function(xhr, status, error) {
                // AJAX error
                handleSessionError('Error de conexión: ' + error);
            }
        });
    }
    
    // Handle session error
    function handleSessionError(errorMessage) {
        // Show error message
        addSystemMessage('Error: ' + errorMessage);
        
        // Disable chat input
        chatInput.prop('disabled', true);
        sendButton.prop('disabled', true);
        
        // Change end session button to return button
        endSessionButton.text('Volver al inicio');
        endSessionButton.removeClass('mhtp-end-session-button').addClass('mhtp-return-button');
        endSessionButton.off('click').on('click', function() {
            window.location.href = window.location.pathname;
        });
    }
    
    // Set up event listeners
    function setupEventListeners() {
        // Send message on button click
        sendButton.on('click', sendMessage);
        
        // Send message on Enter key (but allow Shift+Enter for new line)
        chatInput.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // End session button - show confirmation modal
        endSessionButton.on('click', showEndSessionConfirmation);
        
        // Confirm end session
        confirmEndSessionButton.on('click', function() {
            endSessionModal.hide();
            endSession();
        });
        
        // Cancel end session
        cancelEndSessionButton.on('click', function() {
            endSessionModal.hide();
        });
        
        // Download conversation
        if (downloadConversationButton.length) {
            downloadConversationButton.on('click', function(e) {
                if (isTypebotOnly) {
                    e.preventDefault();
                    alert('La descarga de la conversación aún no está disponible.');
                    return;
                }
                if (conversationMessages.length > 0) {
                    generatePDF();
                }
            });
        }
    }
    
    // Show end session confirmation modal
    function showEndSessionConfirmation() {
        endSessionModal.css('display', 'block');
    }
    
    // Send message function
    function sendMessage() {
        const message = chatInput.val().trim();

        if (message && sessionActive) {
            // Add message to chat immediately
            addMessage(message, 'user');
            storeMessage(message, 'user', getCurrentTime());
            updateHistory('user', message);
            chatInput.val('');

            fetch(mhtpChatConfig.rest_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': mhtpChatConfig.nonce
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.text) {
                        addMessage(data.text, 'expert');
                        storeMessage(data.text, 'expert', getCurrentTime());
                        updateHistory('expert', data.text);
                        hideLoadingBubble();
                    } else if (data.error) {
                        addSystemMessage('Error: ' + data.error);
                    }
                })
                .catch(err =>
                    addSystemMessage('Error de conexión: ' + err.message)
                );
        }
    }
    
    // Store message for saving
    function storeMessage(message, sender, time) {
        conversationMessages.push({
            message: message,
            sender: sender,
            time: time
        });
    }
    
    // Add message to chat
    function addMessage(message, sender) {
        const messageElement = $('<div class="mhtp-message"></div>');
        messageElement.addClass(sender === 'user' ? 'mhtp-message-user' : 'mhtp-message-expert');
        
        const contentElement = $('<div class="mhtp-message-content"></div>');
        contentElement.html('<p>' + message.replace(/\n/g, '<br>') + '</p>');
        
        const timeElement = $('<div class="mhtp-message-time"></div>');
        timeElement.text(getCurrentTime());
        
        messageElement.append(contentElement);
        messageElement.append(timeElement);
        
        chatMessages.append(messageElement);
        
        // Scroll to bottom
        scrollToBottom();
    }
    
    // Add system message
    function addSystemMessage(message) {
        const messageElement = $('<div class="mhtp-message mhtp-message-system"></div>');
        const contentElement = $('<div class="mhtp-message-content mhtp-system-content"></div>');
        contentElement.html('<p>' + message + '</p>');
        
        messageElement.append(contentElement);
        chatMessages.append(messageElement);
        
        // Store system message for saving
        storeMessage(message, 'system', getCurrentTime());
        
        // Scroll to bottom
        scrollToBottom();
    }
    
    // Simulate typing indicator with improved visuals
    function simulateTyping() {
        // Get expert avatar URL
        const expertAvatarUrl = $('.mhtp-expert-avatar img').attr('src');
        const expertName = $('.mhtp-expert-name').text();
        
        // Create typing indicator with avatar
        const typingElement = $('<div class="mhtp-message mhtp-message-expert mhtp-typing-indicator"></div>');
        
        // Add avatar if available
        if (expertAvatarUrl) {
            const avatarElement = $('<div class="mhtp-typing-avatar"></div>');
            avatarElement.html('<img src="' + expertAvatarUrl + '" alt="' + expertName + '">');
            typingElement.append(avatarElement);
        }
        
        // Add typing animation
        const contentElement = $('<div class="mhtp-message-content mhtp-typing-content"></div>');
        contentElement.html('<div class="mhtp-typing-bubbles"><div class="mhtp-typing-bubble"></div><div class="mhtp-typing-bubble"></div><div class="mhtp-typing-bubble"></div></div>');
        
        typingElement.append(contentElement);
        chatMessages.append(typingElement);
        
        // Scroll to bottom
        scrollToBottom();
    }
    
    // Get current time in HH:MM format
    function getCurrentTime() {
        const now = new Date();
        let hours = now.getHours();
        let minutes = now.getMinutes();
        
        hours = hours < 10 ? '0' + hours : hours;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        
        return hours + ':' + minutes;
    }
    
    // Scroll chat to bottom
    function scrollToBottom() {
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
    
    // Format time as MM:SS
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        
        return (minutes < 10 ? '0' : '') + minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;
    }
    
    // Start session timer
    function startSessionTimer() {
        function update() {
            const diff = Math.round((sessionEndTime - new Date()) / 1000);
            const timeLeft = diff > 0 ? diff : 0;

            // Update timer display
            sessionTimerElement.text(formatTime(timeLeft));

            // Add warning class when less than 5 minutes remain
            if (timeLeft <= 5 * 60) {
                sessionTimerElement.addClass('warning');
            }
            
            // Check for warnings
            if (timeLeft === 10 * 60) { // 10 minutes left
                addSystemMessage('Quedan 10 minutos para finalizar la sesión.');
            } else if (timeLeft === 5 * 60) { // 5 minutes left
                addSystemMessage('Quedan 5 minutos para finalizar la sesión.');
            } else if (timeLeft <= 0) {
                // End session when time is up
                clearInterval(sessionTimer);
                endSession();
            }
        }

        update();
        sessionTimer = setInterval(update, 1000);
    }
    
    // Generate PDF of conversation
    function generatePDF() {
        // Check if jsPDF is loaded
        if (typeof jspdf === 'undefined') {
            // Load jsPDF dynamically
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            script.onload = function() {
                generatePDFContent();
            };
            document.head.appendChild(script);
        } else {
            generatePDFContent();
        }
    }
    
    // Generate PDF content
    function generatePDFContent() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Get expert and session info
        const expertName = $('.mhtp-expert-name').text();
        const expertSpecialty = $('.mhtp-expert-specialty').text();
        const sessionId = $('.mhtp-session-info .mhtp-session-detail:first-child .mhtp-session-value').text();
        const sessionDate = new Date().toLocaleDateString();
        
        // Set title
        doc.setFontSize(18);
        doc.text('Resumen de Consulta con Experto', 105, 20, { align: 'center' });
        
        // Set session info
        doc.setFontSize(12);
        doc.text(`Experto: ${expertName} - ${expertSpecialty}`, 20, 40);
        doc.text(`ID de Sesión: ${sessionId}`, 20, 50);
        doc.text(`Fecha: ${sessionDate}`, 20, 60);
        
        // Add conversation
        doc.setFontSize(11);
        let yPos = 80;
        
        conversationMessages.forEach(function(msg) {
            const sender = msg.sender === 'user' ? 'Tú' : (msg.sender === 'expert' ? expertName : 'Sistema');
            const prefix = msg.sender === 'user' ? '→ ' : (msg.sender === 'expert' ? '← ' : '! ');
            
            // Check if we need a new page
            if (yPos > 270) {
                doc.addPage();
                yPos = 20;
            }
            
            doc.setFont(undefined, msg.sender === 'system' ? 'bold' : 'normal');
            doc.text(`${prefix}${sender} (${msg.time}): ${msg.message}`, 20, yPos, {
                maxWidth: 170
            });
            
            // Calculate height of text to position next message
            const textHeight = doc.getTextDimensions(`${prefix}${sender} (${msg.time}): ${msg.message}`, {
                maxWidth: 170
            }).h;
            
            yPos += textHeight + 5;
        });
        
        // Save PDF
        doc.save(`Chat_con_${expertName.replace(/\s+/g, '_')}_${sessionId}.pdf`);
    }
    
    // End session
    function endSession() {
        if (!sessionActive) return;

        sessionActive = false;
        clearInterval(sessionTimer);
        clearSessionState();

        // If using the Typebot iframe only, grey it out
        if (chatMain.find('iframe').length) {
            chatMain.addClass('disabled');
            sessionOverlay.show();
        }

        // Disable input
        chatInput.prop('disabled', true);
        sendButton.prop('disabled', true);
        
        // Change end session button
        endSessionButton.text('Volver al inicio');
        endSessionButton.removeClass('mhtp-end-session-button').addClass('mhtp-return-button');
        endSessionButton.off('click').on('click', function() {
            window.location.href = window.location.pathname;
        });
        
        // Update session status
        $('.mhtp-session-active').text('Finalizada').removeClass('mhtp-session-active').addClass('mhtp-session-ended');
        
        // Add system message
        addSystemMessage('La sesión ha finalizado. Gracias por utilizar nuestro servicio de chat con expertos.');
        
        // Enable download button if conversation saving is checked
        if (saveConversationCheckbox.is(':checked')) {
            downloadConversationButton.prop('disabled', false);
        }
    }
    
    // Check if we're on the full chat interface (Botpress) page
    if (chatMessages.length > 0 && chatInput.length > 0) {
        initChat();
    } else if (sessionTimerElement.length > 0) {
        // Fallback for Typebot embed
        const saved = loadSessionState();
        if (saved) {
            sessionActive = true;
            sessionEndTime = new Date(saved.endTime);
            $('.mhtp-session-info .mhtp-session-detail:first-child .mhtp-session-value').text(saved.sessionId);
        } else {
            sessionActive = true;
            sessionEndTime = new Date();
            sessionEndTime.setMinutes(sessionEndTime.getMinutes() + 45);
            saveSessionState(getExpertId(), getSessionId(), sessionEndTime.getTime());
        }
        setupEventListeners();
        startSessionTimer();
    }
    
    // Add CSS for typing indicator and conversation saving
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            /* Improved typing indicator */
            .mhtp-typing-indicator {
                display: flex;
                align-items: flex-start;
                margin-bottom: 15px;
            }
            
            .mhtp-typing-avatar {
                margin-right: 10px;
                flex-shrink: 0;
            }
            
            .mhtp-typing-avatar img {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                border: 2px solid #3a5998;
            }
            
            .mhtp-typing-content {
                background-color: #e9eef5;
                border-radius: 18px;
                padding: 10px 15px;
                display: flex;
                align-items: center;
                min-width: 60px;
                min-height: 24px;
            }
            
            .mhtp-typing-bubbles {
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .mhtp-typing-bubble {
                background-color: #3a5998;
                border-radius: 50%;
                height: 8px;
                width: 8px;
                margin: 0 2px;
                animation: mhtp-typing-bubble-animation 1.2s infinite ease-in-out;
            }
            
            .mhtp-typing-bubble:nth-child(1) {
                animation-delay: 0s;
            }
            
            .mhtp-typing-bubble:nth-child(2) {
                animation-delay: 0.2s;
            }
            
            .mhtp-typing-bubble:nth-child(3) {
                animation-delay: 0.4s;
            }
            
            @keyframes mhtp-typing-bubble-animation {
                0%, 60%, 100% {
                    transform: translateY(0);
                }
                30% {
                    transform: translateY(-6px);
                }
            }
            
            /* System message styles */
            .mhtp-message-system {
                margin: 10px 0;
                text-align: center;
            }
            
            .mhtp-system-content {
                display: inline-block;
                background-color: #f8f9fa;
                border-radius: 18px;
                padding: 8px 15px;
                font-style: italic;
                color: #666;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            }
            
            /* Conversation saving styles */
            .mhtp-conversation-options {
                margin-top: 15px;
                background-color: #ffffff;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            .mhtp-conversation-option {
                margin-bottom: 10px;
            }
            
            .mhtp-conversation-option label {
                display: flex;
                align-items: center;
                font-size: 0.9rem;
                color: #333;
                cursor: pointer;
            }
            
            .mhtp-conversation-option input[type="checkbox"] {
                margin-right: 8px;
            }
            
            .mhtp-download-button {
                width: 100%;
                padding: 8px;
                background-color: #3a5998;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.9rem;
                transition: background-color 0.2s;
            }
            
            .mhtp-download-button:hover {
                background-color: #2d4373;
            }
            
            .mhtp-download-button:disabled {
                background-color: #cccccc;
                cursor: not-allowed;
            }
        `)
        .appendTo('head');
});
