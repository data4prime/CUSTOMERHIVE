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
            max-width: calc(100vw - 16px);
            border: 1px solid var(--ch-border-strong);
            background-color: var(--ch-surface);
            position: fixed; /* Cambiato a fixed per posizione iniziale */
            top: 100px;
            right: 0; /* Posizione iniziale a destra */
            display: none; /* Nascondi la chat all'inizio */
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            z-index: 1000; /* Assicurati che la finestra di chat sia sopra altri elementi */
        }

        .chat-header {
            /*background-color: var(--ch-accent);
            color: white;*/
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
            border-top: 1px solid var(--ch-border-strong);
        }

        .chat-input {
            width: 100%;
            display: flex;

        }

        .chat-input input {
            padding: 10px;
            border: 1px solid var(--ch-border-strong);
            border-radius: 5px;
        }

.media-chat {
    padding-right: 64px;
    margin-bottom: 0;
}

.media {
    padding: 16px 12px;
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
    background-color: var(--ch-bg);
    color: var(--ch-text-muted);
    text-transform: uppercase;
}

.media-chat .media-body {
    -webkit-box-flex: initial;
    flex: initial;
    /*display: table;*/
}

.media-body {
    min-width: 0;
}

.media-chat .media-body p {
    position: relative;
    padding: 6px 8px;
    margin: 4px 0;
    background-color: var(--ch-bg);
    border-radius: 3px;
    font-weight: 100;
    /*color:var(--ch-text-muted);*/
    color: var(--ch-text);    
    white-space: normal;
}

.media>* {
    margin: 0 8px;
}

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
    color: var(--ch-text);
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

.media-chat.media-chat-reverse  p {
    /*float: right;*/
    clear: right;
    background-color: var(--ch-blue);
    color: var(--ch-surface);
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
    color: var(--ch-text);
    border: 2px solid var(--ch-border-strong);
    border-radius: 50%;
    background-color: var(--ch-bg);
    cursor: pointer;
    transition: all 0.3s ease;
}*/

    </style>




<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        Chat AI
    <div id="x-close-chatai" ><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>

    </div>
    <div class="chat-body">




        <div class="media media-chat">
                  <span class="d-inline-block align-top">{!! \App\Helpers\ChatAIHelper::aiAvatarHtml(36) !!}</span>
                  <div class="media-body">
                    <p>{!! __('crudbooster.hello') !!}</p>
                    <p>{!! __('crudbooster.i_am_your_chatai') !!}</p>
                    <p>{!! __('crudbooster.how_can_i_help_you') !!}</p>
                  </div>
                </div>
    @if ($chat_messages)
        @foreach ($chat_messages as $chat_message)
            <div class="media media-chat media-chat-reverse">
                <span class="d-inline-block align-top">{!! \App\Helpers\ChatAIHelper::userAvatarHtml(36) !!}</span>
                <div class="media-body">
                    <p>{{ $chat_message['message'] }}</p>
                </div>
            </div>

            <div class="media media-chat">
                <span class="d-inline-block align-top">{!! \App\Helpers\ChatAIHelper::aiAvatarHtml(36) !!}</span>
                <div class="media-body">
                    <p>{!! $chat_message['response'] !!}</p>

                </div>
            </div>
        @endforeach
            
    @endif
    </div>
    <div class="chat-footer">
        <div class="chat-input">
            <input type="text" id="publisher-input" class="form-control me-2" placeholder="{{ trans('crudbooster.chatai_placeholder') }}">
            <div class="input-group-append">
                <button id="send-btn" class="btn btn-primary" type="button">{!! __('crudbooster.send') !!}</button>
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

var CHATAI_ERROR_TEXT = {!! json_encode(trans('crudbooster.chatai_error_generic')) !!};
var CHATAI_AVATAR_USER = {!! json_encode(\App\Helpers\ChatAIHelper::userAvatarHtml(36)) !!};
var CHATAI_AVATAR_AI = {!! json_encode(\App\Helpers\ChatAIHelper::aiAvatarHtml(36)) !!};

function chatAIScrollToBottom() {
    var body = document.querySelector('.chat-body');
    body.scrollTop = body.scrollHeight;
}

function chatAIEscape(text) {
    var el = document.createElement('div');
    el.textContent = text;
    return el.innerHTML;
}

// Stesso markup del rendering lato server (sopra): i messaggi hanno lo stesso
// aspetto prima e dopo il ricaricamento della pagina.
function chatAIAppendUser(textHtml) {
    var html = '<div class="media media-chat media-chat-reverse"><span class="d-inline-block align-top">' + CHATAI_AVATAR_USER + '</span><div class="media-body"><p>' + textHtml + '</p></div></div>';
    document.querySelector('.chat-body').insertAdjacentHTML('beforeend', html);
}

function chatAIAppendAI(textHtml) {
    var html = '<div class="media media-chat"><span class="d-inline-block align-top">' + CHATAI_AVATAR_AI + '</span><div class="media-body"><p>' + textHtml + '</p></div></div>';
    document.querySelector('.chat-body').insertAdjacentHTML('beforeend', html);
}

function sendMessage(event) {
    event.preventDefault();

    var input = document.querySelector('#publisher-input');
    var message = input.value;

    if (message.trim() === '') {
        return;
    }

    chatAIAppendUser(chatAIEscape(message));
    input.value = '';
    chatAIScrollToBottom();

    fetch('/admin/chat_ai/send_message', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ 'message': message }),
    })
    .then((response) => response.json())
    .then((data) => {
        if (data.text) {
            chatAIAppendAI(data.text);
        }

        if (data.message) {
            chatAIAppendAI(data.message);
        }

        if (!data.text && !data.message) {
            chatAIAppendAI(chatAIEscape(CHATAI_ERROR_TEXT));
        }

        chatAIScrollToBottom();
    })
    .catch((error) => {
        console.error('Error:', error);
        chatAIAppendAI(chatAIEscape(CHATAI_ERROR_TEXT));
        chatAIScrollToBottom();
    });
}

var objDiv = document.querySelector('.chat-body');
objDiv.scrollTop = objDiv.scrollHeight;


</script>
