
    <style>
        body {
            padding-top: 56px; /* Altezza della navbar */
        }

        .chat-window {
            width: 350px;
            min-width: 200px;
            height: 400px;
            min-height: 200px;
            border: 1px solid #ccc;
            background-color: white;
            position: fixed; /* Cambiato a fixed per posizione iniziale */
            top: 100px;
            right: 0; /* Posizione iniziale a destra */
            display: none; /* Nascondi la chat all'inizio */
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            z-index: 1000; /* Assicurati che la finestra di chat sia sopra altri elementi */
        }

        .chat-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            cursor: move; /* Cursore per il trascinamento */
        }

        .chat-body {
            height: calc(100% - 110px);
            overflow-y: auto;
            padding: 10px;
        }

        .chat-footer {
            padding: 10px;
            border-top: 1px solid #ccc;
        }
    </style>




<div class="chat-window" id="chatWindow">
    <div class="chat-header">Chat AI</div>
    <div class="chat-body">
        <!-- Contenuto della chat -->
        <p>Benvenuto nella chat!</p>
    </div>
    <div class="chat-footer">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Scrivi un messaggio...">
            <div class="input-group-append">
                <button id="send-btn" class="btn btn-primary" type="button">Invia</button>
            </div>
        </div>
    </div>
</div>



<script>



document.getElementById('send-btn').addEventListener('click', function() {
    event.preventDefault();

    console.log('clicked');

    /*var message = document.querySelector('.publisher-input').value;
    console.log(message);
    if(message != ''){
        var html = '<div class="media media-chat media-chat-reverse"><div class="media-body"><p>'+message+'</p></div></div>';
        document.querySelector('.publisher').insertAdjacentHTML('beforebegin', html);
        document.querySelector('.publisher-input').value = '';

    }


    fetch('/admin/chat_ai/send_message', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ 'message': message }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log('Success:', data);
        var html = '<div class="media media-chat"><img class="avatar" src="/images/user/admin.jpeg" alt="..."><div class="media-body"><p>'+data.text+'</p></div></div>';
        
        document.querySelector('.publisher').insertAdjacentHTML('beforebegin', html);

        var objDiv = document.getElementById("chat-content");
        objDiv.scrollTop = objDiv.scrollHeight;
      })
      .catch((error) => {
        console.error('Error:', error);
      });


    var objDiv = document.getElementById("chat-content");
    objDiv.scrollTop = objDiv.scrollHeight;*/


});


</script>
