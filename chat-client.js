/*
 * Simple chat client that stores last 50 messages in sessionStorage
 * and syncs them with a server REST endpoint.
 */
(function(){
  const chat = document.getElementById('chat');
  const form = document.getElementById('chat-form');
  const input = document.getElementById('chat-input');

  // Basic styles for the messages
  const style = document.createElement('style');
  style.textContent = `.msg{margin:5px 0;font-family:sans-serif} .msg.user{text-align:right} .msg.assistant{text-align:left} .msg .text{display:inline-block;padding:4px 8px;border-radius:4px;background:#eef} .msg.assistant .text{background:#e2e8f0}`;
  document.head.appendChild(style);

  // Resolve sessionId from URL or create a new one
  let params = new URLSearchParams(location.search);
  let sessionId = params.get('sessionId');
  if(!sessionId){
    sessionId = uuidv4();
    params.set('sessionId', sessionId);
    history.replaceState(null, '', location.pathname + '?' + params.toString());
  }

  const STORAGE_KEY = 'chat_' + sessionId;
  let messages = loadHistory();

  // Render cached messages
  messages.forEach(m => renderMessage(m.side, m.text, m.timestamp));

  // Fetch new messages from server
  fetch('/chat/' + sessionId)
    .then(r => r.ok ? r.json() : [])
    .then(diffMerge)
    .catch(() => {});

  form.addEventListener('submit', e => {
    e.preventDefault();
    const text = input.value.trim();
    if(text){
      sendMessage(text);
      input.value = '';
    }
  });

  function uuidv4(){
    return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g,c=>
      (c^crypto.getRandomValues(new Uint8Array(1))[0]&15>>c/4).toString(16)
    );
  }

  function renderMessage(side, text, timestamp){
    const wrap = document.createElement('div');
    wrap.className = 'msg ' + side;
    const span = document.createElement('span');
    span.className = 'text';
    span.textContent = text;
    const time = document.createElement('span');
    time.className = 'time';
    time.textContent = new Date(timestamp).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    wrap.appendChild(span);
    wrap.appendChild(time);
    chat.appendChild(wrap);
    chat.scrollTop = chat.scrollHeight;
  }

  function loadHistory(){
    try{
      const raw = sessionStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    }catch(e){
      return [];
    }
  }

  function saveToSession(){
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(messages.slice(-50)));
  }

  function diffMerge(serverMessages){
    serverMessages.forEach(sm => {
      const exists = messages.find(m => m.timestamp === sm.timestamp && m.text === sm.text && m.side === sm.side);
      if(!exists){
        messages.push(sm);
        renderMessage(sm.side, sm.text, sm.timestamp);
      }
    });
    messages = messages.slice(-50);
    saveToSession();
  }

  function syncMessage(msg){
    fetch('/chat/' + sessionId + '/messages', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(msg)
    }).catch(() => {});
  }

  function sendMessage(text){
    const msg = {side:'user', text:text, timestamp:Date.now()};
    messages.push(msg); messages = messages.slice(-50);
    renderMessage('user', text, msg.timestamp);
    saveToSession();
    syncMessage(msg);
  }

  window.receiveMessage = function(data){
    const msg = {side:'assistant', text:data.text, timestamp:data.timestamp || Date.now()};
    messages.push(msg); messages = messages.slice(-50);
    renderMessage('assistant', msg.text, msg.timestamp);
    saveToSession();
    syncMessage(msg);
  };

  window.clearSession = function(){
    sessionStorage.removeItem(STORAGE_KEY);
  };
})();

