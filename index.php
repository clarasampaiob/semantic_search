<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title> Help Desk Chat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .chat-container {
    max-width: 850px;
    height: 100vh;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    padding: 20px;
  }
  
  .response-area {
    flex-grow: 1;
    overflow-y: auto;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid transparent;
    border-radius: 8px;
    background-color: transparent;
  }
  
  .input-area {
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px gray;
  }
  
  .suggestion-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
  }
  
  .prompt-btn {
    flex: 1;
  }
  
  .input-group {
    margin-top: 5px;
  }

  #userInput{
    background-color: #404045;
    color: white;
  }
        
  .message-bubble {
    padding: 10px 15px;
    border-radius: 18px;
    margin-bottom: 10px;
    max-width: 80%;
    word-wrap: break-word;
  }
  
  .user-message {
    background-color: #72d0e7ff;
    align-self: flex-end;
    margin-left: auto;
    color: black;
  }
  
  .bot-message {
    background-color: #e2e3e5;
    align-self: flex-start;
    margin-right: auto;
    color: black;
  }
</style>
</head>
<body>
  <div class="chat-container">
    <div class="response-area" id="responseArea">
      <div class="d-flex flex-column">
        <div class="message-bubble bot-message">
            Hello! How can I help you today?
        </div>
      </div>
    </div>
    <div class="input-area">
      <div class="suggestion-buttons">
        <button id="aboutTeslaBtn" class="btn btn-outline-primary prompt-btn">
            About Tesla
        </button>
        <button id="teslaBatteryBtn" class="btn btn-outline-primary prompt-btn">
            Tesla Battery
        </button>
      </div>
      <div class="input-group">
        <textarea id="userInput" class="form-control" rows="3" placeholder="Type your message here..."></textarea>
        <button id="sendBtn" class="btn btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const userInput = document.getElementById('userInput');
  const aboutTeslaBtn = document.getElementById('aboutTeslaBtn');
  const teslaBatteryBtn = document.getElementById('teslaBatteryBtn');
  const sendBtn = document.getElementById('sendBtn');
  const responseArea = document.getElementById('responseArea');
  const messageContainer = responseArea.querySelector('.d-flex');
  let loadingMessageElement = null; // Para controlar a msg de carregamento
  
  aboutTeslaBtn.addEventListener('click', function() {
      userInput.value = "I would like to learn a little about Tesla. Could you help me?";
  });
  
  teslaBatteryBtn.addEventListener('click', function() {
      userInput.value = "How long does a Tesla battery last before it needs to be replaced?";
  });
  
  sendBtn.addEventListener('click', function() {
      const message = userInput.value.trim();
      if(message) {
        addMessage(message, 'user');
        userInput.value = '';
        sendMessageToServer(message);
      }
  });
  
  userInput.addEventListener('keydown', function(e) {
      if(e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendBtn.click();
      }
  });
      
  function addMessage(text, sender) {
      const messageContainer = responseArea.querySelector('.d-flex');
      const bubble = document.createElement('div');
      bubble.className = `message-bubble ${sender}-message`;
      bubble.textContent = text;
      messageContainer.appendChild(bubble);
      responseArea.scrollTop = responseArea.scrollHeight;
      return bubble;
  }

  async function sendMessageToServer(message) {
    try {
      loadingMessageElement = addMessage('Thinking ...', 'bot');
      const response = await fetch('conversations/completions', {
        method: 'POST',
        headers: {'Content-Type': 'application/json',},
        body: JSON.stringify({
            helpdeskId: '123',
            projectName: 'Tesla Support',
            messages: [{
                "role": "USER",
                "content": message
            }]
        })
      });
      if (!response.ok) throw new Error("We had a problem. Please, try again later.");
      const data = await response.json();
      // Remove a mensagem de carregamento
      if (loadingMessageElement) {
        loadingMessageElement.remove();
      }
      addMessage(data.messages[1].content, 'bot');
    } catch (error) {
      // Remove a mensagem de carregamento se existir
      if (loadingMessageElement && loadingMessageElement.parentNode) {
        loadingMessageElement.remove();
      }
      addMessage(`${error.message}`, 'bot');
      console.error('Erro na requisição:', error);
    }
  }
});
</script>
</body>
</html>
