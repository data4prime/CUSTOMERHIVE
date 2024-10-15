@php 

use Illuminate\Support\Facades\Session;

$chat_messages = Session::get('chat_messages');



@endphp 

    <style>


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
            /*background-color: #007bff;
            color: white;*/
            padding: 10px;
            cursor: move; /* Cursore per il trascinamento */
        }

        .chat-body {
            height: calc(100% - 110px);
            overflow-y: auto;
            /*padding: 10px;*/
        }

        .chat-footer {
            padding: 10px;
            border-top: 1px solid #ccc;
        }

        .chat-input {
            width: 100%;
            display: flex;

        }

        .chat-input input {
           /* padding: 10px; */
            border: 1px solid #ccc;
            border-radius: 5px;
        }

.media-chat {
    /*padding-right: 64px;*/
    /*margin-bottom: 0;*/
}

.media {
    /*padding: 16px 12px;*/
    -webkit-transition: background-color .2s linear;
    transition: background-color .2s linear;
}
.avatar {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 36px;
    line-height: 36px;
    text-align: center;
    border-radius: 100%;
    background-color: #f5f6f7;
    color: #8b95a5;
    text-transform: uppercase;
}

.media-chat .media-body {
    -webkit-box-flex: initial;
    flex: initial;
    display: table;
}

.media-body {
    width: inherit;
    min-width: 0;
}
.media-chat-reverse > .media-body{
    clear: right;
    background-color: #48b0f7;
    color: #fff;
}

.media-body {
width: inherit;
    min-width: 0;
    position: relative;
    padding: 6px 8px;
    /*margin: 4px 0;*/
    background-color: #f5f6f7;
    border-radius: 3px;
    font-weight: 100;
    /*color:#9b9b9b;*/
    color: #000;
    white-space: normal;
    /*display: block;*/
    margin-bottom: 10px;
}

.media-chat .media-body p {
    position: relative;
    /*padding: 6px 8px;*/
    /*margin: 4px 0;*/
    background-color: #f5f6f7;
    border-radius: 3px;
    font-weight: 100;
    /*color:#9b9b9b;*/
    color: #000;
    white-space: normal;
    display: block;
    margin-bottom: 10px;
}

/*
.media>* {
    margin: 0 8px;
}
*/

.media-chat .media-body p.meta {
    background-color: transparent !important;
    padding: 0;
    opacity: .8;
}

.card-header:first-child {
    border-radius: calc(.25rem - 1px) calc(.25rem - 1px) 0 0;
}


.chat-header {
    display: -webkit-box;
    display: flex;
    -webkit-box-pack: justify;
    justify-content: space-between;
    -webkit-box-align: center;
    align-items: center;
    padding: 15px 20px;
    /*background-color: transparent;*/
    border-bottom: 1px solid rgba(77,82,89,0.07);
    /*text black color*/
    color: #4d5259;
}

.chat-header .chat-title {
    padding: 0;
    border: none;
}

h4.chat-title {
    font-size: 17px;
}

.chat-header>*:last-child {
    margin-right: 0;
}

.chat-header>* {
    margin-left: 8px;
    margin-right: 8px;
}

.media-chat-reverse {

    display: flex;
    justify-content: flex-end;
    align-items: flex-start;
    /*margin: 10px 0;*/


    clear: right;
    /*background-color: #48b0f7;*/
    color: #fff;
}

.media-chat.media-chat-reverse  p {
    /*float: right;*/
    clear: right;
    background-color: #48b0f7;
    color: #fff;
}

/*
#x-close-chatai {
    position: absolute;
    right: 10px;
    top: 10px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
    color: #333;
    border: 2px solid #ccc;
    border-radius: 50%;
    background-color: #f9f9f9;
    cursor: pointer;
    transition: all 0.3s ease;
}*/

    </style>




<div class="card chat-window" id="chatWindow">
    <div class="card-header chat-header">
        Chat AI
    <div id="x-close-chatai" ><button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button></div>

    </div>
    <div class="card-body chat-body">




        <div class="media media-chat">
                  <img class="avatar" src="/images/user/chatai.jpg" alt="...">
                    <div class="media-body">
                    Ciao

                  </div>
                    <div class="media-body">
                    Sono il tuo assistente AI
                  </div>
                    <div class="media-body">
                    Come posso aiutarti?
                  </div>
                </div>
    @if ($chat_messages)
        @foreach ($chat_messages as $chat_message)
            <div class="media media-chat media-chat-reverse">
                <img class="avatar" src="/images/user/admin.jpeg" alt="...">
                <div class="media-body">
                    @php echo  $chat_message['message'] @endphp
                </div>
            </div>

            <div class="media media-chat">
                <img class="avatar" src="/images/user/chatai.jpg" alt="...">
                <div class="media-body">
                    @php echo $chat_message['response'] @endphp

                </div>
            </div>
        @endforeach
            
    @endif
    </div>
    <div class="card-footer chat-footer">
        <div class="chat-input">
            <input type="text" id="publisher-input"  class="form-control" placeholder="Chiedi qualcosa...">
            <div class="input-group-append">
                <button id="send-btn" class="btn btn-primary" type="button">Invia</button>
            </div>
        </div>
    </div>
</div>



<script>

document.getElementById('send-btn').addEventListener('click', function(event) {
    sendMessage(event);
});

document.getElementById('publisher-input').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        sendMessage(event);
    }
});

function sendMessage(event) {
    event.preventDefault();

    console.log('clicked');

    var message = document.querySelector('#publisher-input').value;
    console.log(message);
    if(message != ''){
        var html = '<div class="media media-chat media-chat-reverse"><img class="avatar" src="/images/user/admin.jpeg" alt="..."><div class="media-body"><p>'+message+'</p></div></div>';
        document.querySelector('.chat-body').insertAdjacentHTML('beforeend', html);
        document.querySelector('#publisher-input').value = '';
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
        //if data.text exists
        if(data.text){
            var html = '<div class="media media-chat"><img class="avatar" src="/images/user/chatai.jpg" alt="..."><div class="media-body"><p>'+data.text+'</p></div></div>';
            document.querySelector('.chat-body').insertAdjacentHTML('beforeend', html);
        }

        if(data.message){
            var html = '<div class="media media-chat"><img class="avatar" src="/images/user/chatai.jpg" alt="..."><div class="media-body"><p>'+data.message+'</p></div></div>';
            document.querySelector('.chat-body').insertAdjacentHTML('beforeend', html);
        }

        var objDiv = document.querySelector('.chat-body');
        objDiv.scrollTop = objDiv.scrollHeight;
    })
    .catch((error) => {
        console.error('Error:', error);
    });

    var objDiv = document.querySelector('.chat-body');
    objDiv.scrollTop = objDiv.scrollHeight;
}



//on document ready
document.addEventListener("DOMContentLoaded", function(event) {
    var objDiv = document.querySelector('.chat-body');
    objDiv.scrollTop = objDiv.scrollHeight;
});


</script>
