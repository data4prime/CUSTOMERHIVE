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
            max-height: calc(100vh - 16px);
            border: 1px solid var(--ch-border-strong);
            background-color: var(--ch-surface);
            position: fixed; /* Cambiato a fixed per posizione iniziale */
            top: 100px;
            right: 0; /* Posizione iniziale a destra */
            display: none; /* Nascondi la chat all'inizio */
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            z-index: 1000; /* Assicurati che la finestra di chat sia sopra altri elementi */
        }

        {{-- Handle di ridimensionamento (jQuery UI resizable, vedi admin_template_plugins).
             jquery-ui.css non e' caricato nelle pagine admin: qui solo gli stili degli handle --}}
        #chatWindow .ui-resizable-handle {
            position: absolute;
            display: block;
            z-index: 10;
            font-size: 0.1px;
        }
        #chatWindow .ui-resizable-n  { top: -4px;    left: 8px;  right: 8px;  height: 8px; cursor: n-resize; }
        #chatWindow .ui-resizable-s  { bottom: -4px; left: 8px;  right: 8px;  height: 8px; cursor: s-resize; }
        #chatWindow .ui-resizable-e  { right: -4px;  top: 8px;   bottom: 8px; width: 8px;  cursor: e-resize; }
        #chatWindow .ui-resizable-w  { left: -4px;   top: 8px;   bottom: 8px; width: 8px;  cursor: w-resize; }
        #chatWindow .ui-resizable-ne { top: -4px;    right: -4px;  width: 14px; height: 14px; cursor: ne-resize; }
        #chatWindow .ui-resizable-nw { top: -4px;    left: -4px;   width: 14px; height: 14px; cursor: nw-resize; }
        #chatWindow .ui-resizable-sw { bottom: -4px; left: -4px;   width: 14px; height: 14px; cursor: sw-resize; }
        #chatWindow .ui-resizable-se {
            bottom: 0; right: 0; width: 14px; height: 14px; cursor: se-resize;
            background: linear-gradient(135deg, transparent 50%, var(--ch-border-strong) 50%, var(--ch-border-strong) 60%, transparent 60%, transparent 75%, var(--ch-border-strong) 75%, var(--ch-border-strong) 85%, transparent 85%);
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
            /*padding: 10px;*/
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
           /* padding: 10px; */
            border: 1px solid var(--ch-border-strong);
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
    background-color: var(--ch-bg);
    color: var(--ch-text-muted);
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
.media-body {
width: inherit;
    min-width: 0;
    position: relative;
    padding: 6px 8px;
    /*margin: 4px 0;*/
    background-color: var(--ch-bg);
    border-radius: 3px;
    font-weight: 100;
    /*color:var(--ch-text-muted);*/
    color: var(--ch-text);
    white-space: normal;
    /*display: block;*/
    margin-bottom: 10px;
}

{{-- Messaggio dell'utente: stesso aspetto sia se reso dal server sia se aggiunto via JS --}}
.media-chat-reverse .media-body {
    background-color: var(--ch-blue);
    color: var(--ch-surface);
}

.media-chat .media-body p {
    position: relative;
    /*padding: 6px 8px;*/
    /*margin: 4px 0;*/
    background-color: var(--ch-bg);
    border-radius: 3px;
    font-weight: 100;
    /*color:var(--ch-text-muted);*/
    color: var(--ch-text);
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

.media-chat-reverse {

    display: flex;
    justify-content: flex-end;
    align-items: flex-start;
    /*margin: 10px 0;*/


    clear: right;
    /*background-color: var(--ch-blue);*/
    color: var(--ch-surface);
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




<div class="card chat-window" id="chatWindow">
    <div class="card-header chat-header d-flex justify-content-between align-items-center">
        Chat AI
        <div id="x-close-chatai">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    </div>

    <div class="card-body chat-body">
        <div class="d-flex mb-3 media-chat">
            <div class="me-2">{!! \App\Helpers\ChatAIHelper::aiAvatarHtml(36) !!}</div>
            <div>
                <div class="media-body">{!! __('crudbooster.hello') !!}</div>
                <div class="media-body">{!! __('crudbooster.i_am_your_chatai') !!}</div>
                <div class="media-body">{!! __('crudbooster.how_can_i_help_you') !!}</div>
            </div>
        </div>

        @if ($chat_messages)
            @foreach ($chat_messages as $chat_message)
                <!-- Messaggio dell'utente -->
                <div class="d-flex mb-3 justify-content-end media-chat media-chat-reverse">
                    <div>
                        <div class="media-body">{{ $chat_message['message'] }}</div>
                    </div>
                    <div class="ms-2">{!! \App\Helpers\ChatAIHelper::userAvatarHtml(36) !!}</div>
                </div>

                <!-- Risposta AI -->
                <div class="d-flex mb-3 media-chat">
                    <div class="me-2">{!! \App\Helpers\ChatAIHelper::aiAvatarHtml(36) !!}</div>
                    <div class="media-body">{!! $chat_message['response'] !!}</div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="card-footer chat-footer">
        <div class="input-group">
            <input type="text" id="publisher-input" class="form-control" placeholder="{{ trans('crudbooster.chatai_placeholder') }}">
            <button id="send-btn" class="btn btn-primary" type="button">{{ trans('crudbooster.send') }}</button>
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
    var body = document.querySelector('#chatWindow .chat-body');
    if (body) {
        body.scrollTop = body.scrollHeight;
    }
}

function chatAIEscape(text) {
    var el = document.createElement('div');
    el.textContent = text;
    return el.innerHTML;
}

// Stesso markup del rendering lato server (vedi sopra), cosi' i messaggi
// hanno lo stesso aspetto prima e dopo il ricaricamento della pagina.
function chatAIAppendUser(textHtml) {
    var html = '<div class="d-flex mb-3 justify-content-end media-chat media-chat-reverse"><div><div class="media-body">' + textHtml + '</div></div><div class="ms-2">' + CHATAI_AVATAR_USER + '</div></div>';
    document.querySelector('#chatWindow .chat-body').insertAdjacentHTML('beforeend', html);
}

function chatAIAppendAI(textHtml) {
    var html = '<div class="d-flex mb-3 media-chat"><div class="me-2">' + CHATAI_AVATAR_AI + '</div><div class="media-body">' + textHtml + '</div></div>';
    document.querySelector('#chatWindow .chat-body').insertAdjacentHTML('beforeend', html);
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

// La finestra parte nascosta (display: none): qui lo scroll non ha effetto,
// viene rifatto ogni volta che la si apre (vedi toggleChatWindow()).
document.addEventListener("DOMContentLoaded", chatAIScrollToBottom);


</script>
