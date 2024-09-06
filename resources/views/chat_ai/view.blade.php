@php 

use Illuminate\Support\Facades\Session;

$chat_messages = Session::get('chat_messages_'.$row->id);

/*
dd($chat_messages);
*/



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
                  <img class="avatar" src="/images/user/chatai.jpg" alt="...">
                    <div class="media-body">
                    <p>Ciao</p>

                  </div>
                    <div class="media-body">
                    <p>Sono il tuo assistente AI</p>
                  </div>
                    <div class="media-body">
                    <p>Come posso aiutarti?</p>
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
                <img class="avatar" src="/images/user/admin.jpeg" alt="...">
                <div class="media-body">
                    <p>{{ $chat_message['message'] }}</p>
                </div>
            </div>

            <div class="media media-chat">
                <img class="avatar" src="/images/user/chatai.jpg" alt="...">
                <div class="media-body">
                    <p>{{$chat_message['response']}}</p>

                </div>
            </div>
        @endforeach
            
    @endif
    </div>
    <div class="chat-footer">
        <div class="chat-input">
            <input type="text" id="publisher-input-{{$row->id}}" style="margin-right: 5px ;" class="form-control" placeholder="Chiedi qualcosa...">
            <div class="input-group-append">
                <button id="send-btn-{{$row->id}}" class="btn btn-primary" type="button">Invia</button>
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

function sendMessage{{$row->id}}(event) {
    event.preventDefault();

    console.log('clicked');

    var message = document.querySelector('#publisher-input-{{$row->id}}').value;
    var agent_id = '{{$row->id}}';
    console.log(message);
    if(message != ''){
        var html = '<div class="media media-chat media-chat-reverse"><img class="avatar" src="/images/user/admin.jpeg" alt="..."><div class="media-body"><p>'+message+'</p></div></div>';
        document.querySelector('.chat-body-{{$row->id}}').insertAdjacentHTML('beforeend', html);
        document.querySelector('#publisher-input-{{$row->id}}').value = '';
    }

    fetch('/admin/chat_ai/send_message_agent', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ 'message': message, 'agent_id' : agent_id}),
    })
    .then((response) => response.json())
    .then((data) => {
        console.log('Success:', data);
        //if data.text exists
        if(data.text){
            var html = '<div class="media media-chat"><img class="avatar" src="/images/user/chatai.jpg" alt="..."><div class="media-body"><p>'+data.text+'</p></div></div>';
            document.querySelector('.chat-body-{{$row->id}}').insertAdjacentHTML('beforeend', html);
        }

        if(data.message){
            var html = '<div class="media media-chat"><img class="avatar" src="/images/user/chatai.jpg" alt="..."><div class="media-body"><p>'+data.message+'</p></div></div>';
            document.querySelector('.chat-body-{{$row->id}}').insertAdjacentHTML('beforeend', html);
        }

        var objDiv = document.querySelector('.chat-body-{{$row->id}}');
        objDiv.scrollTop = objDiv.scrollHeight;
    })
    .catch((error) => {
        console.error('Error:', error);
    });

    var objDiv = document.querySelector('.chat-body-{{$row->id}}');
    objDiv.scrollTop = objDiv.scrollHeight;
}

var objDiv = document.querySelector('.chat-body-{{$row->id}}');
objDiv.scrollTop = objDiv.scrollHeight;

</script>
@endpush
