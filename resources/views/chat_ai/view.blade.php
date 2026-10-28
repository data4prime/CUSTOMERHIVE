@php 

use Illuminate\Support\Facades\Session;

$chat_messages = Session::get('chat_messages_'.$row->id);

/*
dd($chat_messages);
*/

App\Helpers\ChatAIHelper::getToken($row->id);



@endphp 

@extends('crudbooster::admin_template',['target_layout' => isset($row->target_layout) ? $row->target_layout : null ])

@if(isset($row->target_layout) && $row->target_layout == 2)
<!-- fill content settings -->
@section('content')


@endsection
@else
<!-- default -->
@section('content')

<div class="chat-window-view" id="chatWindowView">

    <div class="chat-body-{{$row->id}}">




        <div class="media media-chat">
                  <span class="d-inline-block align-top me-2">{!! \App\Helpers\ChatAIHelper::aiAvatarHtml(36) !!}</span>
                    <div class="media-body">
                    <p>{!! __('crudbooster.hello') !!}</p>

                  </div>
                    <div class="media-body">
                    <p>{!! __('crudbooster.i_am_your_chatai') !!}</p>
                  </div>
                    <div class="media-body">
                    <p>{!! __('crudbooster.how_can_i_help_you') !!}</p>
                  </div>
                  <!--<div class="media-body">
                    <p>Ciao</p>
                    <p>Sono il tuo assistente AI</p>
                    <p>Come posso aiutarti?</p>
                  </div>-->
                </div>
    @if ($chat_messages)
        @foreach ($chat_messages as $chat_message)
            <div class="media media-chat media-chat-reverse">
                <span class="d-inline-block align-top me-2">{!! \App\Helpers\ChatAIHelper::userAvatarHtml(36) !!}</span>
                <div class="media-body">
                    {{ $chat_message['message'] }}
                </div>
            </div>

            <div class="media media-chat">
                <span class="d-inline-block align-top me-2">{!! \App\Helpers\ChatAIHelper::aiAvatarHtml(36) !!}</span>
                <div class="media-body">
                    @php echo $chat_message['response'] @endphp

                </div>
            </div>
        @endforeach
            
    @endif
    </div>
    <div class="chat-footer">
        <div class="chat-input">
            <input type="text" id="publisher-input-{{$row->id}}" class="form-control me-2" placeholder="{{ trans('crudbooster.chatai_placeholder') }}">
            <div class="input-group-append">
                <button id="send-btn-{{$row->id}}" class="btn btn-primary" type="button">{!! __('crudbooster.send') !!}</button>
            </div>
        </div>
    </div>
</div>

@endsection
@endif

@push('bottom')
<script type="text/javascript">

document.getElementById('send-btn-{{$row->id}}').addEventListener('click', function(event) {
    sendMessage{{$row->id}}(event);
});

document.getElementById('publisher-input-{{$row->id}}').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        sendMessage{{$row->id}}(event);
    }
});

var CHATAI_AVATAR_USER_{{$row->id}} = {!! json_encode(\App\Helpers\ChatAIHelper::userAvatarHtml(36)) !!};
var CHATAI_AVATAR_AI_{{$row->id}} = {!! json_encode(\App\Helpers\ChatAIHelper::aiAvatarHtml(36)) !!};
var CHATAI_ERROR_HTML_{{$row->id}} = {!! json_encode(
    e(trans('crudbooster.chatai_error_check_title'))
    . '<br><ul><li>' . e(trans('crudbooster.chatai_error_check_workflow')) . '</li>'
    . '<li>' . e(trans('crudbooster.chatai_error_check_url')) . '</li>'
    . '<li>' . e(trans('crudbooster.chatai_error_check_passphrase')) . '</li></ul>'
) !!};

function chatAIEscape{{$row->id}}(text) {
    var el = document.createElement('div');
    el.textContent = text;
    return el.innerHTML;
}

// Stesso markup del rendering lato server (sopra): i messaggi hanno lo stesso
// aspetto prima e dopo il ricaricamento della pagina.
function chatAIAppendUser{{$row->id}}(textHtml) {
    var html = '<div class="media media-chat media-chat-reverse"><span class="d-inline-block align-top me-2">' + CHATAI_AVATAR_USER_{{$row->id}} + '</span><div class="media-body">' + textHtml + '</div></div>';
    document.querySelector('.chat-body-{{$row->id}}').insertAdjacentHTML('beforeend', html);
}

function chatAIAppendAI{{$row->id}}(textHtml) {
    var html = '<div class="media media-chat"><span class="d-inline-block align-top me-2">' + CHATAI_AVATAR_AI_{{$row->id}} + '</span><div class="media-body">' + textHtml + '</div></div>';
    document.querySelector('.chat-body-{{$row->id}}').insertAdjacentHTML('beforeend', html);
}

function sendMessage{{$row->id}}(event) {
    event.preventDefault();

    var input = document.querySelector('#publisher-input-{{$row->id}}');
    var message = input.value;
    var agent_id = '{{$row->id}}';

    if (message.trim() === '') {
        return;
    }

    chatAIAppendUser{{$row->id}}(chatAIEscape{{$row->id}}(message));
    input.value = '';

    fetch('/admin/chat_ai/send_message_agent', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ 'message': message, 'agent_id' : agent_id}),
    })
    .then((response) => response.json())
    .then((data) => {
        if (data.text) {
            chatAIAppendAI{{$row->id}}(data.text);
        }

        if (data.message) {
            chatAIAppendAI{{$row->id}}(data.message);
        }

        if (!data.text && !data.message) {
            chatAIAppendAI{{$row->id}}(CHATAI_ERROR_HTML_{{$row->id}});
        }

        var objDiv = document.querySelector('.chat-body-{{$row->id}}');
        objDiv.scrollTop = objDiv.scrollHeight;
    })
    .catch((error) => {
        console.error('Error:', error);
        chatAIAppendAI{{$row->id}}(CHATAI_ERROR_HTML_{{$row->id}});
        var objDiv = document.querySelector('.chat-body-{{$row->id}}');
        objDiv.scrollTop = objDiv.scrollHeight;
    });

    var objDiv = document.querySelector('.chat-body-{{$row->id}}');
    objDiv.scrollTop = objDiv.scrollHeight;
}

var objDiv = document.querySelector('.chat-body-{{$row->id}}');
objDiv.scrollTop = objDiv.scrollHeight;

</script>
@endpush

@push('head')
    <style>


        .chat-window-view {
            /*width: 350px;
            min-width: 200px;*/
            /*height: 400px;
            min-height: 200px;*/
            border: 1px solid var(--ch-border-strong);
            background-color: var(--ch-surface);
            /*position: fixed;
            top: 100px;
            right: 0;
            display: none;*/
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        .chat-header {
            /*background-color: var(--ch-accent);
            color: white;*/
            padding: 10px;
            cursor: move; /* Cursore per il trascinamento */
        }

        .chat-body-{{$row->id}} {
            height: calc(100vh - 120px - 51px - 42px);
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
    display: table;
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

@endpush