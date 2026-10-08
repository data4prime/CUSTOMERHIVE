{{-- Pannello artistico del nuovo look (ui2) per le pagine di autenticazione: griglia di esagoni, solo decorativo --}}
@if (config('crudbooster.UI_V2'))
<div class="ch-auth-art" aria-hidden="true">
    <svg viewBox="0 0 900 640" preserveAspectRatio="xMidYMid slice" fill="none" stroke="#fff" stroke-opacity=".12">
        @php $hr = 64; $hw = $hr * sqrt(3); @endphp
        @for ($row = 0; $row < 9; $row++)
            @for ($c = 0; $c < 9; $c++)
                @php
                    $cx = $c * $hw + ($row % 2 ? $hw / 2 : 0);
                    $cy = $row * $hr * 1.5;
                    $pts = [];
                    for ($i = 0; $i < 6; $i++) {
                        $a = deg2rad(60 * $i - 30);
                        $pts[] = round($cx + ($hr - 4) * cos($a), 1) . ',' . round($cy + ($hr - 4) * sin($a), 1);
                    }
                @endphp
                <polygon points="{{ implode(' ', $pts) }}"/>
            @endfor
        @endfor
    </svg>
</div>
@endif
