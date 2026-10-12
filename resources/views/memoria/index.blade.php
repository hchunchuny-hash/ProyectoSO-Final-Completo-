@extends('layouts.app')

@section('titulo', 'CPU y Memoria')
@section('subtitulo', 'Uso real de CPU, RAM, swap, carga y procesos, leído de /proc')

@push('estilos')
<style>
  .separado{margin-top:16px}
  .controles{display:flex;flex-wrap:wrap;gap:18px;align-items:center}
  .controles label{display:flex;align-items:center;gap:6px;color:var(--suave);font-size:13px}
  .controles select{padding:5px 8px;background:var(--fondo);color:var(--texto);border:1px solid var(--borde);border-radius:var(--radio);font:inherit}
  .estado{margin-left:auto;color:var(--suave);font:12px var(--mono)}
  .uso span.alto{background:var(--error)}
  .detalle{margin:8px 0 0;color:var(--suave);font-size:12px}
  .grafica{width:100%;height:auto;display:block;margin-top:6px}
  .grafica text{fill:var(--suave);font:10px var(--mono)}
  .desglose{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px}
  .desglose div{border:1px solid var(--borde);border-radius:var(--radio);padding:8px 10px;background:var(--panel2)}
  .desglose small{display:block;color:var(--suave);font-size:12px}
  .desglose strong{font:700 18px var(--mono)}
</style>
@endpush

@section('contenido')
    <section class="tarjeta">
        <h2>Actualización en vivo</h2>
        <p class="nota">Cada actualización pide una lectura nueva de /proc al servidor (AJAX con fetch).</p>
        <div class="controles">
            <label for="auto"><input type="checkbox" id="auto" checked> Actualizar automáticamente</label>
            <label for="intervalo">Cada
                <select id="intervalo">
                    <option value="2000" selected>2 segundos</option>
                    <option value="3000">3 segundos</option>
                    <option value="5000">5 segundos</option>
                </select>
            </label>
            <button type="button" class="btn sec" id="ahora">Actualizar ahora</button>
            <span class="estado" id="estado" role="status">Esperando datos…</span>
        </div>
    </section>

    <div class="rejilla separado">
        <section class="tarjeta">
            <h2>Uso de CPU</h2>
            <p class="nota">Todos los núcleos · /proc/stat</p>
            <div class="dato"><span id="cpu-uso">—</span><small>%</small></div>
            <div class="uso" role="img" aria-label="Barra de uso de CPU"><span id="cpu-barra" style="width:0%"></span></div>
        </section>

        <section class="tarjeta">
            <h2>Memoria RAM</h2>
            <p class="nota">Usada = total − disponible · /proc/meminfo</p>
            <div class="dato"><span id="ram-uso">—</span><small>%</small></div>
            <div class="uso" role="img" aria-label="Barra de uso de memoria RAM"><span id="ram-barra" style="width:0%"></span></div>
            <p class="detalle" id="ram-detalle">—</p>
        </section>

        <section class="tarjeta">
            <h2>Swap</h2>
            <p class="nota">Memoria en disco · /proc/meminfo</p>
            <div class="dato"><span id="swap-uso">—</span><small>%</small></div>
            <div class="uso" role="img" aria-label="Barra de uso de swap"><span id="swap-barra" style="width:0%"></span></div>
            <p class="detalle" id="swap-detalle">—</p>
        </section>

        <section class="tarjeta">
            <h2>Carga del sistema</h2>
            <p class="nota">Promedio de 1 minuto · /proc/loadavg</p>
            <div class="dato"><span id="carga-1">—</span></div>
            <p class="detalle" id="carga-detalle">5 min: — · 15 min: —</p>
            <p class="detalle" id="carga-procesos">—</p>
        </section>

        <section class="tarjeta">
            <h2>Tiempo encendido</h2>
            <p class="nota">Desde el arranque · /proc/uptime</p>
            <div class="dato" style="font-size:22px"><span id="encendido">—</span></div>
        </section>
    </div>

    <div class="rejilla separado" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr))">
        <section class="tarjeta">
            <h2>Gráfica de uso de CPU</h2>
            <p class="nota">Últimas 60 lecturas. Se reinicia al recargar la página.</p>
            <svg id="grafica-cpu" class="grafica" viewBox="0 0 600 160" role="img" aria-label="Gráfica del uso de CPU"></svg>
        </section>

        <section class="tarjeta">
            <h2>Gráfica de uso de memoria RAM</h2>
            <p class="nota">Últimas 60 lecturas. Se reinicia al recargar la página.</p>
            <svg id="grafica-ram" class="grafica" viewBox="0 0 600 160" role="img" aria-label="Gráfica del uso de memoria RAM"></svg>
        </section>
    </div>

    <div class="rejilla separado" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr))">
        <section class="tarjeta">
            <h2>En qué gasta el tiempo la CPU</h2>
            <p class="nota">Reparto del tiempo entre dos lecturas · /proc/stat</p>
            <div class="desglose">
                <div><small>Usuario</small><strong id="d-usuario">—</strong></div>
                <div><small>Sistema (kernel)</small><strong id="d-sistema">—</strong></div>
                <div><small>Espera de E/S</small><strong id="d-es">—</strong></div>
                <div><small>Robado</small><strong id="d-robado">—</strong></div>
                <div><small>Ocioso</small><strong id="d-ocioso">—</strong></div>
            </div>
        </section>

        <section class="tarjeta">
            <h2>Uso por núcleo</h2>
            <p class="nota">Cada procesador lógico que ve el kernel</p>
            <div class="caja-tabla">
                <table class="tabla">
                    <thead><tr><th>Núcleo</th><th>Uso</th><th>Barra</th></tr></thead>
                    <tbody id="nucleos-cuerpo">
                        <tr><td colspan="3">Esperando datos…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="rejilla separado" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr))">
        <section class="tarjeta">
            <h2>Top 5 procesos por CPU</h2>
            <p class="nota">Porcentaje de un núcleo · /proc/[pid]/stat</p>
            <div class="caja-tabla">
                <table class="tabla">
                    <thead><tr><th>PID</th><th>Comando</th><th>Usuario</th><th>CPU</th></tr></thead>
                    <tbody id="top-cpu-cuerpo">
                        <tr><td colspan="4">Esperando datos…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="tarjeta">
            <h2>Top 5 procesos por memoria</h2>
            <p class="nota">Memoria residente (RSS) · /proc/[pid]/stat y status</p>
            <div class="caja-tabla">
                <table class="tabla">
                    <thead><tr><th>PID</th><th>Comando</th><th>Usuario</th><th>Memoria</th><th>% RAM</th></tr></thead>
                    <tbody id="top-mem-cuerpo">
                        <tr><td colspan="5">Esperando datos…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="rejilla separado" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr))">
        <section class="tarjeta">
            <h2>Detalle de memoria</h2>
            <p class="nota">Valores de /proc/meminfo</p>
            <div class="caja-tabla">
                <table class="tabla">
                    <tbody>
                        <tr><th>RAM total</th><td class="num" id="m-total">—</td></tr>
                        <tr><th>RAM usada</th><td class="num" id="m-usada">—</td></tr>
                        <tr><th>RAM libre</th><td class="num" id="m-libre">—</td></tr>
                        <tr><th>En caché (búferes y archivos)</th><td class="num" id="m-cache">—</td></tr>
                        <tr><th>RAM disponible</th><td class="num" id="m-disponible">—</td></tr>
                        <tr><th>Swap total</th><td class="num" id="m-swap-total">—</td></tr>
                        <tr><th>Swap usada</th><td class="num" id="m-swap-usada">—</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="tarjeta">
            <h2>Información del procesador</h2>
            <p class="nota">/proc/cpuinfo y datos del kernel</p>
            <div class="caja-tabla">
                <table class="tabla">
                    <tbody>
                        <tr><th>Modelo</th><td>{{ $info['modelo'] }}</td></tr>
                        <tr><th>Núcleos lógicos</th><td class="num">{{ $info['nucleos'] }}</td></tr>
                        <tr><th>Arquitectura</th><td>{{ $info['arquitectura'] }}</td></tr>
                        <tr><th>Kernel</th><td>{{ $info['kernel'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
