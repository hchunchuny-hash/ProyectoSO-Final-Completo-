@extends('layouts.app')

@section('titulo', 'Planificación de CPU')
@section('subtitulo', 'Simulador de algoritmos con diagrama de Gantt (no lee el sistema real)')

@push('estilos')
<style>
  .gantt{display:flex;height:46px;border:1px solid var(--borde);border-radius:var(--radio);overflow:hidden;margin-top:8px}
  .gantt div{display:grid;place-items:center;color:#fff;font:600 13px var(--mono);border-right:2px solid var(--panel);min-width:0;overflow:hidden}
  .gantt .ocioso{background:repeating-linear-gradient(45deg,var(--panel2),var(--panel2) 6px,var(--borde) 6px,var(--borde) 12px);color:var(--suave)}
  .marcas{position:relative;height:22px;font:11px var(--mono);color:var(--suave);margin-top:4px}
  .marcas span{position:absolute;transform:translateX(-50%)}
  .marcas span.primero{transform:none}
  .marcas span.ultimo{transform:translateX(-100%)}
  .acciones{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-top:14px}
  .acciones .campo{margin-top:0;min-width:170px}
  .tabla input{width:80px;padding:6px 8px;background:var(--fondo);color:var(--texto);border:1px solid var(--borde);border-radius:var(--radio);font:inherit}
  .resaltar td{background:var(--panel2)}
  .mejor{color:var(--ok);font-weight:700}
  .check{display:flex;align-items:center;gap:6px;color:var(--suave);font-size:13px;padding-bottom:8px}
  .separado{margin-top:16px}
</style>
@endpush

@php
    $filas  = old('procesos', $procesosForm);
    $algSel = old('algoritmo', $algoritmo);
    $qSel   = old('quantum', $quantum);
    $color  = fn (string $pid) => 'hsl(' . (((int) substr($pid, 1)) * 67 % 360) . ', 55%, 42%)';
@endphp

@section('contenido')
    @if ($errors->any())
        <div class="tarjeta separado">
            <p class="error" style="margin:0">Revisa los datos: {{ $errors->first() }}</p>
        </div>
    @endif

    @if ($guardada)
        <div class="tarjeta separado">
            Simulación #{{ $guardada->id }} guardada el {{ $guardada->created_at->timezone('America/Guatemala')->format('d/m/Y H:i') }}.
        </div>
    @endif

    <form method="POST" action="{{ route('planificacion.simular') }}" class="separado">
        @csrf
        <div class="rejilla" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr))">
            <section class="tarjeta">
                <h2>Procesos a simular</h2>
                <p class="nota">Llegada, ráfaga de CPU y prioridad (menor número = mayor prioridad). Máximo 20 procesos.</p>
                <div class="caja-tabla">
                    <table class="tabla">
                        <thead>
                            <tr><th>Proceso</th><th>Llegada</th><th>Ráfaga</th><th>Prioridad</th><th></th></tr>
                        </thead>
                        <tbody id="filas">
                            @foreach ($filas as $i => $p)
                                <tr>
                                    <td class="nombre">P{{ $i + 1 }}</td>
                                    <td><input type="number" name="procesos[{{ $i }}][llegada]" value="{{ $p['llegada'] }}" min="0" max="100" required aria-label="Llegada"></td>
                                    <td><input type="number" name="procesos[{{ $i }}][rafaga]" value="{{ $p['rafaga'] }}" min="1" max="100" required aria-label="Ráfaga"></td>
                                    <td><input type="number" name="procesos[{{ $i }}][prioridad]" value="{{ $p['prioridad'] }}" min="0" max="20" required aria-label="Prioridad"></td>
                                    <td><button type="button" class="btn sec quitar">Quitar</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="acciones">
                    <button type="button" class="btn sec" id="agregar">Agregar proceso</button>
                    <button type="button" class="btn sec" id="aleatorios">Generar aleatorios</button>
                </div>
            </section>

            <section class="tarjeta">
                <h2>Configuración</h2>
                <p class="nota">Elige el algoritmo y ejecuta la simulación.</p>
                <label class="campo" for="algoritmo">Algoritmo
                    <select id="algoritmo" name="algoritmo">
                        @foreach ($algoritmos as $clave => $nombre)
                            <option value="{{ $clave }}" @selected($algSel === $clave)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="campo" for="quantum">Quantum (solo Round Robin)
                    <input id="quantum" type="number" name="quantum" value="{{ $qSel }}" min="1" max="20">
                </label>
                <div class="acciones">
                    <label class="check"><input type="checkbox" name="guardar" value="1" @checked(old('guardar'))> Guardar esta simulación</label>
                </div>
                <div class="acciones">
                    <button type="submit" name="modo" value="simular" class="btn">Simular</button>
                    <button type="submit" name="modo" value="comparar" class="btn sec">Comparar los 5</button>
                </div>
            </section>
        </div>
    </form>

    @if ($resultado)
        @php
            $total  = max(1, $resultado['tiempo_total']);
            $marcas = collect($resultado['segmentos'])->pluck('inicio')->push($resultado['tiempo_total'])->unique()->values();
        @endphp

        <section class="tarjeta separado">
            <h2>Diagrama de Gantt · {{ $algoritmos[$algoritmo] ?? $algoritmo }}</h2>
            <p class="nota">Cada bloque muestra qué proceso usa la CPU y durante cuánto tiempo. Las franjas grises son CPU ociosa.</p>
            <div class="gantt" role="img" aria-label="Diagrama de Gantt">
                @foreach ($resultado['segmentos'] as $s)
                    @php $ancho = ($s['fin'] - $s['inicio']) / $total * 100; @endphp
                    @if ($s['pid'] === null)
                        <div class="ocioso" style="width: {{ $ancho }}%" title="CPU ociosa {{ $s['inicio'] }}-{{ $s['fin'] }}">—</div>
                    @else
                        <div style="width: {{ $ancho }}%; background: {{ $color($s['pid']) }}" title="{{ $s['pid'] }}: {{ $s['inicio'] }}-{{ $s['fin'] }}">{{ $s['pid'] }}</div>
                    @endif
                @endforeach
            </div>
            <div class="marcas">
                @foreach ($marcas as $m)
                    <span class="{{ $loop->first ? 'primero' : ($loop->last ? 'ultimo' : '') }}" style="left: {{ $m / $total * 100 }}%">{{ $m }}</span>
                @endforeach
            </div>
        </section>

        <section class="tarjeta separado">
            <h2>Tiempos por proceso</h2>
            <p class="nota">Retorno = fin − llegada. Espera = retorno − ráfaga.</p>
            <div class="caja-tabla">
                <table class="tabla">
                    <thead>
                        <tr><th>Proceso</th><th>Llegada</th><th>Ráfaga</th><th>Prioridad</th><th>Fin</th><th>Retorno</th><th>Espera</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($resultado['filas'] as $f)
                            <tr>
                                <td>{{ $f['pid'] }}</td>
                                <td class="num">{{ $f['llegada'] }}</td>
                                <td class="num">{{ $f['rafaga'] }}</td>
                                <td class="num">{{ $f['prioridad'] }}</td>
                                <td class="num">{{ $f['fin'] }}</td>
                                <td class="num">{{ $f['retorno'] }}</td>
                                <td class="num">{{ $f['espera'] }}</td>
                            </tr>
                        @endforeach
                        <tr class="resaltar">
                            <td colspan="5"><strong>Promedio</strong></td>
                            <td class="num"><strong>{{ number_format($resultado['prom_retorno'], 2) }}</strong></td>
                            <td class="num"><strong>{{ number_format($resultado['prom_espera'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($comparacion)
        @php $mejor = collect($comparacion)->min('espera'); @endphp
        <section class="tarjeta separado">
            <h2>Comparación de algoritmos</h2>
            <p class="nota">Mismos procesos con los cinco algoritmos. En verde, el menor tiempo de espera promedio.</p>
            <div class="caja-tabla">
                <table class="tabla">
                    <thead>
                        <tr><th>Algoritmo</th><th>Retorno promedio</th><th>Espera promedio</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($comparacion as $clave => $c)
                            <tr class="{{ $clave === $algoritmo ? 'resaltar' : '' }}">
                                <td>{{ $c['nombre'] }}</td>
                                <td class="num">{{ number_format($c['retorno'], 2) }}</td>
                                <td class="num {{ $c['espera'] == $mejor ? 'mejor' : '' }}">{{ number_format($c['espera'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="tarjeta separado">
        <h2>Simulaciones guardadas</h2>
        <p class="nota">Las últimas 10 simulaciones guardadas en SQLite.</p>
        <div class="caja-tabla">
            <table class="tabla">
                <thead>
                    <tr><th>#</th><th>Fecha</th><th>Algoritmo</th><th>Procesos</th><th>Espera prom.</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($historial as $h)
                        <tr>
                            <td class="num">{{ $h->id }}</td>
                            <td>{{ $h->created_at->timezone('America/Guatemala')->format('d/m/Y H:i') }}</td>
                            <td><span class="insignia">{{ $algoritmos[$h->algoritmo] ?? $h->algoritmo }}</span></td>
                            <td class="num">{{ count($h->procesos) }}</td>
                            <td class="num">{{ number_format($h->prom_espera, 2) }}</td>
                            <td><a class="btn sec" href="{{ route('planificacion.ver', $h) }}">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Todavía no hay simulaciones guardadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    const cuerpo = document.getElementById('filas');
    const MAX = 20;

    function renumerar() {
        Array.from(cuerpo.rows).forEach(function (fila, i) {
            fila.querySelector('.nombre').textContent = 'P' + (i + 1);
            fila.querySelectorAll('input').forEach(function (inp) {
                inp.name = inp.name.replace(/procesos\[\d+\]/, 'procesos[' + i + ']');
            });
        });
    }

    function nuevaFila(llegada, rafaga, prioridad) {
        if (cuerpo.rows.length >= MAX) { return; }
        const i = cuerpo.rows.length;
        const tr = document.createElement('tr');

        const tdNombre = document.createElement('td');
        tdNombre.className = 'nombre';
        tr.appendChild(tdNombre);

        [['llegada', llegada, 0, 100], ['rafaga', rafaga, 1, 100], ['prioridad', prioridad, 0, 20]].forEach(function (c) {
            const td = document.createElement('td');
            const inp = document.createElement('input');
            inp.type = 'number';
            inp.name = 'procesos[' + i + '][' + c[0] + ']';
            inp.value = c[1];
            inp.min = c[2];
            inp.max = c[3];
            inp.required = true;
            inp.setAttribute('aria-label', c[0]);
            td.appendChild(inp);
            tr.appendChild(td);
        });

        const tdQuitar = document.createElement('td');
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn sec quitar';
        btn.textContent = 'Quitar';
        tdQuitar.appendChild(btn);
        tr.appendChild(tdQuitar);

        cuerpo.appendChild(tr);
        renumerar();
    }

    document.getElementById('agregar').addEventListener('click', function () {
        nuevaFila(0, 3, 1);
    });

    document.getElementById('aleatorios').addEventListener('click', function () {
        const n = 4 + Math.floor(Math.random() * 3);
        cuerpo.innerHTML = '';
        for (let k = 0; k < n; k++) {
            nuevaFila(Math.floor(Math.random() * 8), 1 + Math.floor(Math.random() * 9), 1 + Math.floor(Math.random() * 5));
        }
    });

    cuerpo.addEventListener('click', function (e) {
        if (e.target.classList.contains('quitar') && cuerpo.rows.length > 1) {
            e.target.closest('tr').remove();
            renumerar();
        }
    });
})();
</script>
@endpush
