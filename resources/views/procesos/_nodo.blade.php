@php
    $proceso = $arbol['por_pid'][$pid] ?? null;
    $hijos = $arbol['hijos'][$pid] ?? [];
@endphp
@if ($proceso && $nivel <= 8)
    <div style="margin-top: 0.3rem;">
        @if (count($hijos) > 0)
            <details>
                <summary style="cursor: pointer; font-family: var(--mono);">
                    <strong>PID {{ $proceso['pid'] }}</strong> - {{ $proceso['comando'] }}
                    <small>({{ count($hijos) }} hijos, User: {{ $proceso['usuario'] }})</small>
                </summary>
                <div style="border-left: 2px solid var(--borde); padding-left: 0.8rem; margin-top: 0.2rem;">
                    @foreach ($hijos as $hijoPid)
                        @include('procesos._nodo', ['pid' => $hijoPid, 'arbol' => $arbol, 'nivel' => $nivel + 1])
                    @endforeach
                </div>
            </details>
        @else
            <div style="font-family: var(--mono); font-size: 0.9rem; color: var(--suave);">
                ├─ PID {{ $proceso['pid'] }} - {{ $proceso['comando'] }} <small>({{ $proceso['usuario'] }})</small>
            </div>
        @endif
    </div>
@endif
