@php 

use Illuminate\Support\Facades\Session;

$chat_messages = Session::get('chat_messages');



@endphp 




<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        Chat AI
    <div id="x-close-chatai" ><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button></div>

    </div>
    <div class="chat-body">




        <div class="media media-chat">
                  <img class="avatar" src="/images/user/chatai.jpg" alt="...">
                  <div class="media-body">
                    <p>Ciao</p>
                    <p>Sono il tuo assistente AI</p>
                    <p>Come posso aiutarti?</p>
                  </div>
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
            <input type="text" id="publisher-input" style="margin-right: 5px ;" class="form-control" placeholder="Chiedi qualcosa...">
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
