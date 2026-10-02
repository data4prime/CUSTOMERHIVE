@php
    $mgSteps = [
        1 => ['route' => 'ModulsControllerGetStep1'],
        2 => ['route' => 'ModulsControllerGetStep2'],
        3 => ['route' => 'ModulsControllerGetStep3'],
        4 => ['route' => 'ModulsControllerGetStep4'],
        5 => ['route' => 'ModulsControllerGetStep5'],
    ];
@endphp
<style>
  .mg-stepper { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
  .mg-stepper .mg-st { flex: 1 1 140px; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: .5rem; padding: .5rem .75rem; display: flex; align-items: center; gap: .6rem; color: inherit; text-decoration: none; }
  .mg-stepper .mg-st:hover { border-color: var(--ch-blue); color: inherit; text-decoration: none; }
  .mg-stepper .mg-st .mg-n { width: 26px; height: 26px; flex: 0 0 26px; border-radius: 50%; background: var(--ch-bg); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: .85rem; }
  .mg-stepper .mg-st.active { border-color: var(--ch-accent); box-shadow: 0 0 0 2px rgba(13, 110, 253, .15); }
  .mg-stepper .mg-st.active .mg-n { background: var(--ch-accent); color: var(--ch-surface); }
  .mg-stepper .mg-st.done .mg-n { background: var(--ch-success); color: var(--ch-surface); }
  #mg-wizard.mg-loading { opacity: .55; pointer-events: none; cursor: progress; transition: opacity .15s; }
  .mg-stepper .mg-st small { display: block; color: var(--ch-text-secondary); font-size: .72rem; line-height: 1; }
</style>
<div class="mg-stepper">
    @foreach ($mgSteps as $n => $s)
        <a class="mg-st @if($active_tab == $n) active @elseif($active_tab > $n) done @endif"
           href="@if(isset($id)){{ Route($s['route']).'/'.$id }}@else#@endif">
            <div class="mg-n">@if($active_tab > $n)<i class="bi bi-check-lg"></i>@else{{ $n }}@endif</div>
            <div>{{ trans('crudbooster.mg_nav_'.$n) }}<small>{{ trans('crudbooster.mg_nav_'.$n.'_sub') }}</small></div>
        </a>
    @endforeach
</div>
