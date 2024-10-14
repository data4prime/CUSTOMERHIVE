		<script type="importmap">
			{
				"imports": {
					"ckeditor5": "{{asset('ckeditor5/ckeditor5.js')}}",
					"ckeditor5/": "{{asset('ckeditor5')}}"
				}
			}
		</script>
        <script type="module">
            import {
                ClassicEditor,
                Essentials,
                Paragraph,
                Bold,
                Italic,
                Font
            } from 'ckeditor5';

            ClassicEditor
.create( document.querySelector( '#editor' ) )
	.then( editor => {
		window.editor = editor;
	} )
	.catch( error => {
		console.error( 'There was a problem initializing the editor.', error );
	} );
               /* .create( document.querySelector( '#editor' ), {
                    plugins: [ Essentials, Paragraph, Bold, Italic, Font ],
                    toolbar:  [
    "accessibilityHelp",
    "menuBar:accessibilityHelp",
    "selectAll",
    "menuBar:selectAll",
    "undo",
    "menuBar:undo",
    "redo",
    "menuBar:redo",
    "bold",
    "menuBar:bold",
    "italic",
    "menuBar:italic",
    "fontFamily",
    "menuBar:fontFamily",
    "fontSize",
    "menuBar:fontSize",
    "fontColor",
    "menuBar:fontColor",
    "fontBackgroundColor",
    "menuBar:fontBackgroundColor"
]
                } )
                .then( editor => {          
                    console.log(Array.from( editor.ui.componentFactory.names() )),
                    window.editor = editor;
                } )
                .catch( error => {
                    console.error( error );
                } );*/
        </script>
        <!-- A friendly reminder to run on a server, remove this during the integration. -->
        <script>
		        window.onload = function() {
		            if ( window.location.protocol === "file:" ) {
		                alert( "This sample requires an HTTP server. Please serve this file with a web server." );
		            }
		        };
		</script>
<div class='mb-3 row' id='mb-3 row-{{$name}}' style="{{@$form['style']}}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}</label>

    <div class="{{$col_width?:'col-sm-10'}}">
        <!--<textarea id='textarea_{{$name}}' id="{{$name}}" {{$required}} {{$readonly}} {{$disabled}} name="{{$form['name']}}" class='form-control'
                  rows='5'>{{ $value }}</textarea>-->

            <div id="editor">
                <p>Hello from CKEditor 5!</p>
            </div>
        <div class="text-danger">{{ $errors->first($name) }}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>
    </div>
</div>
