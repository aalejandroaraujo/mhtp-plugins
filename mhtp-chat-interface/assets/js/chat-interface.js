(function(){
  const STORAGE_KEY = 'chatHistory';
  let history = [];
  let loadingBubble = null;
  let loadingTimer = null;

  document.addEventListener('DOMContentLoaded', () => {
    const chat = document.getElementById('chat');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    if(!chat || !form || !input) return;

    // Insert loading bubble
    loadingBubble = document.createElement('div');
    loadingBubble.id = 'loading-bubble';
    loadingBubble.className = 'msg loading';
    loadingBubble.textContent = 'Conectando con tu especialista…';
    chat.appendChild(loadingBubble);

    // Load history from sessionStorage
    history = loadHistory();
    history.forEach(renderMessage);

    // Hide loading bubble after 1-2s if no assistant message arrives
    loadingTimer = setTimeout(hideLoadingBubble, 1000 + Math.random()*1000);

    form.addEventListener('submit', e => {
      e.preventDefault();
      const text = input.value.trim();
      if(text){
        input.value = '';
        sendMessage(text);
      }
    });
  });

  function loadHistory(){
    try{
      const raw = sessionStorage.getItem(STORAGE_KEY);
      const data = JSON.parse(raw);
      return Array.isArray(data) ? data : [];
    }catch(e){
      return [];
    }
  }

  function saveHistory(){
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history.slice(-50)));
  }

  function updateHistory(side, text){
    history.push({ side, text, ts: Date.now() });
    history = history.slice(-50);
    saveHistory();
  }

  function hideLoadingBubble(){
    if(loadingBubble){
      loadingBubble.style.display = 'none';
      loadingBubble = null;
    }
    if(loadingTimer){
      clearTimeout(loadingTimer);
      loadingTimer = null;
    }
  }

  function renderMessage(msg){
    const div = document.createElement('div');
    div.className = 'msg ' + msg.side;
    div.textContent = msg.text;
    document.getElementById('chat').appendChild(div);
    document.getElementById('chat').scrollTop = document.getElementById('chat').scrollHeight;
    if(msg.side === 'assistant') hideLoadingBubble();
  }

  async function sendMessage(text){
    updateHistory('user', text);
    renderMessage({ side: 'user', text });

    try{
      const res = await fetch('/wp-json/mhtp-chat/send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
      });
      if(res.ok){
        const data = await res.json();
        if(data && data.reply){
          receiveMessage(data.reply);
        }
      }
    }catch(err){
      console.error(err);
    }
  }

  function receiveMessage(text){
    updateHistory('assistant', text);
    renderMessage({ side: 'assistant', text });
  }

  // Expose send/receive if needed
  window.chatInterface = { sendMessage, receiveMessage };
})();
