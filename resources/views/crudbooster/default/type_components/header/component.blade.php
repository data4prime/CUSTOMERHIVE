<div id='header{{$index}}' data-collapsed="{{ ($form['collapsed']===false)?'false':'true' }}" class='header-title form-divider'>
    <h4>
        <strong><i class='{{$form['icon']?:"bi bi-check-square"}}'></i> {{$form['label']}}</strong>
        <span class='float-end icon'><i class='bi bi-dash-square'></i></span>
    </h4>
</div>