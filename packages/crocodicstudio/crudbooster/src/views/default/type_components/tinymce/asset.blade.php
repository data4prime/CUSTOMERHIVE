@push('bottom')

    <script>
    tinymce.init({
        selector: 'textarea#myeditorinstance{{$name}}', // Replace this CSS selector to match the placeholder element for TinyMCE
        plugins: 'code table lists',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table'
    });
    </script>

@endpush