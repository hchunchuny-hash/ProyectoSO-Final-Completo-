@extends('layouts.app')

@section('titulo', 'Interbloqueos')
@section('subtitulo', 'Algoritmo del banquero, solicitud de recursos, detección de interbloqueos y grafo de asignación')

@push('estilos')
<style>
  .m4 .separado{margin-top:16px}
  .m4 .cuadricula{display:grid;gap:16px;grid-template-columns:minmax(0,2.2fr) minmax(0,1fr)}
  @media (max-width:900px){.m4 .cuadricula{grid-template-columns:1fr}}
  .m4 .cab{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:6px}
  .m4 .acciones{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end}
  .m4 .acciones.sep{margin-bottom:12px}
  .m4 .tabla input.cel,.m4 .campo.mini input{width:60px;padding:5px 6px;background:var(--fondo);color:var(--texto);border:1px solid var(--borde);border-radius:var(--radio);font:inherit;text-align:center}
  .m4 .tabla th.grupo,.m4 .tabla td.sep{border-left:2px solid var(--borde)}
  .m4 .tabla td.necesidad{color:var(--acento);font-family:var(--mono);text-align:center}
  .m4 .tabla td.necesidad.neg{color:var(--error)}
  .m4 .campo.mini{margin-top:0;font-size:12px}
  .m4 select{padding:7px 9px;background:var(--fondo);color:var(--texto);border:1px solid var(--borde);border-radius:var(--radio);font:inherit}
  .m4 .estado{font-size:14px;padding:3px 12px}
  .m4 .secuencia{margin:8px 0;font:600 14px var(--mono)}
  .m4 .vacio{color:var(--suave)}
  .m4 ul.lista{margin:6px 0;padding-left:20px}
  .m4 ul.lista li{margin:3px 0}
  .m4 li.si{color:var(--ok)}
  .m4 li.no{color:var(--error)}
  .m4 h3{margin:16px 0 4px;font:600 14px var(--mono)}
  .m4 h4{margin:10px 0 2px;font:600 13px var(--mono);color:var(--suave)}
  .m4 .sugerencia{margin:6px 0;padding:8px 12px;border:1px solid var(--acento);border-radius:var(--radio);font-size:13px}
  .m4 .leyenda{display:flex;flex-wrap:wrap;gap:16px;font-size:12px;color:var(--suave);margin:6px 0}
  .m4 .grafo{width:100%;height:auto;max-height:360px;background:var(--panel2);border:1px solid var(--borde);border-radius:var(--radio)}
  .m4 .grafo .arista{fill:none;stroke:var(--suave);stroke-width:1.6}
  .m4 .grafo .arista.solicitud{stroke-dasharray:6 4}
  .m4 .grafo .arista.ciclo{stroke:var(--error);stroke-width:2.4}
  .m4 .grafo .nodo-p{fill:var(--panel);stroke:var(--acento);stroke-width:2}
  .m4 .grafo .nodo-p.bloqueado{stroke:var(--error);stroke-width:3}
  .m4 .grafo .nodo-r{fill:var(--panel);stroke:var(--ok);stroke-width:2}
  .m4 .grafo text{fill:var(--texto);font:600 12px var(--mono);text-anchor:middle;dominant-baseline:central}
  .m4 .grafo text.cant{fill:var(--suave);font-size:11px;paint-order:stroke;stroke:var(--panel2);stroke-width:3px}
</style>
@endpush

@section('contenido')
<div class="m4">
    <div class="cuadricula">
        <section class="tarjeta">
            <div class="cab">
                <h2>Procesos y recursos</h2>
                <div class="acciones">
                    <button type="button" class="btn sec" id="btn-agregar">+ Proceso</button>
                    <button type="button" class="btn sec" id="btn-quitar">− Proceso</button>
                    <button type="button" class="btn sec" id="btn-agregar-rec">+ Recurso</button>
                    <button type="button" class="btn sec" id="btn-quitar-rec">− Recurso</button>
                </div>
            </div>
            <p class="nota">Escribe las matrices de Asignación y Máximo (hasta 10 procesos y 6 recursos). La Necesidad = Máximo − Asignación se calcula sola.</p>
            <div class="acciones sep">
                <button type="button" class="btn sec" data-ejemplo="seguro">Ejemplo seguro</button>
                <button type="button" class="btn sec" data-ejemplo="bloqueo">Ejemplo con interbloqueo</button>
                <button type="button" class="btn sec" data-ejemplo="clasico">Ejemplo clásico (5 procesos)</button>
            </div>
            <div class="caja-tabla">
                <table class="tabla">
                    <thead id="cabeza"></thead>
                    <tbody id="filas"></tbody>
                </table>
            </div>
            <div class="error" id="error" role="alert"></div>
        </section>

        <section class="tarjeta">
            <h2>Recursos disponibles</h2>
            <p class="nota">Instancias libres de cada recurso (vector Disponible).</p>
            <div class="caja-tabla">
                <table class="tabla">
                    <thead><tr><th>Recurso</th><th>Disponible</th></tr></thead>
                    <tbody id="disponibles"></tbody>
                </table>
            </div>
            <button type="button" class="btn bloque" id="btn-banquero">▶ Ejecutar algoritmo del banquero</button>
        </section>
    </div>

    <section class="tarjeta separado" id="res-banquero" hidden>
        <h2>Algoritmo del banquero · paso a paso</h2>
        <div>Estado del sistema: <span class="insignia estado" id="b-estado"></span></div>
        <div class="secuencia" id="b-secuencia"></div>
        <p class="nota" id="b-nota"></p>
        <div class="caja-tabla" id="b-pasos"></div>
    </section>

    <section class="tarjeta separado">
        <h2>Evaluar una solicitud de recursos</h2>
        <p class="nota">Un proceso pide recursos. Se concede solo si no excede su necesidad, hay recursos disponibles y el estado que resulta sigue siendo seguro.</p>
        <div class="acciones">
            <label class="campo mini" for="sol-proceso">Proceso
                <select id="sol-proceso"></select>
            </label>
            <div class="acciones" id="sol-vector"></div>
            <button type="button" class="btn" id="btn-solicitud">Evaluar solicitud</button>
        </div>
        <div id="res-solicitud" hidden>
            <div class="separado">Resultado: <span class="insignia estado" id="s-estado"></span></div>
            <p id="s-motivo"></p>
            <ul class="lista" id="s-verif"></ul>
            <div class="secuencia" id="s-secuencia"></div>
        </div>
    </section>

    <section class="tarjeta separado">
        <div class="cab">
            <h2>Detección de interbloqueos y grafo de asignación</h2>
            <button type="button" class="btn sec" id="btn-detectar">⚠ Detectar interbloqueo y dibujar grafo</button>
        </div>
        <p class="nota">Aristas R → P: el proceso tiene instancias del recurso. Aristas P → R: el proceso necesita más de lo que hay disponible y queda esperando. Con varias instancias por recurso, un ciclo indica un posible interbloqueo; la confirmación sale del análisis de matrices.</p>
        <p class="vacio" id="d-vacio">Presiona el botón para analizar las matrices actuales.</p>
        <div id="res-detectar" hidden>
            <div>Resultado: <span class="insignia estado" id="d-estado"></span></div>
            <p id="d-detalle"></p>
            <svg id="grafo" class="grafo" role="img" aria-label="Grafo de asignación de recursos"></svg>
            <div class="leyenda">
                <span>○ Proceso (borde rojo si está bloqueado)</span>
                <span>□ Recurso</span>
                <span>── Asignación (R → P)</span>
                <span>- - Solicitud pendiente (P → R)</span>
                <span style="color:var(--error)">Rojo: forma parte de un ciclo</span>
            </div>
            <div id="d-ciclos"></div>
            <div id="d-recuperacion"></div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const URL_BANQUERO = "{{ route('interbloqueos.banquero') }}";
    const URL_SOLICITUD = "{{ route('interbloqueos.solicitud') }}";
    const URL_DETECTAR = "{{ route('interbloqueos.detectar') }}";
    const TOKEN = document.querySelector('meta[name="csrf-token"]').content;
    const MAX_PROC = 10;
    const MAX_REC = 6;
    const SVG_NS = 'http://www.w3.org/2000/svg';

    const EJEMPLOS = {
        seguro: { asig: [[1, 0, 1], [2, 1, 0], [1, 1, 0]], max: [[3, 2, 2], [3, 1, 1], [2, 2, 1]], disp: [2, 1, 1] },
        bloqueo: { asig: [[1, 0, 0], [0, 1, 0], [0, 0, 1]], max: [[1, 1, 0], [1, 1, 0], [0, 0, 1]], disp: [0, 0, 0] },
        clasico: {
            asig: [[0, 1, 0], [2, 0, 0], [3, 0, 2], [2, 1, 1], [0, 0, 2]],
            max: [[7, 5, 3], [3, 2, 2], [9, 0, 2], [2, 2, 2], [4, 3, 3]],
            disp: [3, 3, 2]
        }
    };

    let R = 3; // cantidad de recursos que muestra la tabla

    const $ = (id) => document.getElementById(id);

    function el(tag, atributos, ...hijos) {
        const e = document.createElement(tag);
        Object.entries(atributos || {}).forEach(([k, v]) => {
            if (k === 'class') { e.className = v; } else { e.setAttribute(k, v); }
        });
        hijos.forEach((h) => e.append(h));
        return e;
    }

    function svgEl(tag, atributos, ...hijos) {
        const e = document.createElementNS(SVG_NS, tag);
        Object.entries(atributos || {}).forEach(([k, v]) => e.setAttribute(k, v));
        hijos.forEach((h) => e.append(h));
        return e;
    }

    const celda = (contenido, clase) => el('td', clase ? { class: clase } : {}, contenido);
    const vec = (v) => '(' + v.join(', ') + ')';
    const nombre = (i) => 'P' + (i + 1);
    const nombres = (lista, separador) => lista.map(nombre).join(separador);

    function mostrarError(texto) { $('error').textContent = texto; }

    function ponerEstado(nodo, texto, tipo) {
        nodo.textContent = texto;
        nodo.className = 'insignia estado ' + tipo;
    }

    function tabla(cabeceras, filas) {
        return el('table', { class: 'tabla' },
            el('thead', {}, el('tr', {}, ...cabeceras.map((c) => el('th', {}, c)))),
            el('tbody', {}, ...filas.map((f) => el('tr', {}, ...f.map((c) => el('td', {}, c)))))
        );
    }

    /* ---------- tabla de entrada ---------- */

    function entrada(valor) {
        const i = el('input', { type: 'number', min: 0, max: 99, class: 'cel', 'aria-label': 'cantidad' });
        i.value = valor;
        i.addEventListener('input', recalcular);
        return i;
    }

    function pintar(d) {
        const n = d.asig.length;
        R = d.disp.length;

        const cabeza = $('cabeza');
        cabeza.replaceChildren();
        const fila1 = el('tr', {}, el('th', { rowspan: 2 }, 'Proceso'));
        ['Asignación', 'Máximo', 'Necesidad'].forEach((t) => fila1.append(el('th', { colspan: R, class: 'grupo' }, t)));
        const fila2 = el('tr');
        for (let g = 0; g < 3; g++) {
            for (let j = 0; j < R; j++) { fila2.append(el('th', { class: j === 0 ? 'grupo' : '' }, 'R' + (j + 1))); }
        }
        cabeza.append(fila1, fila2);

        const filas = $('filas');
        filas.replaceChildren();
        for (let i = 0; i < n; i++) {
            const tr = el('tr', {}, el('td', {}, nombre(i)));
            for (let j = 0; j < R; j++) { tr.append(celda(entrada(d.asig[i][j]), j === 0 ? 'sep' : '')); }
            for (let j = 0; j < R; j++) { tr.append(celda(entrada(d.max[i][j]), j === 0 ? 'sep' : '')); }
            for (let j = 0; j < R; j++) { tr.append(celda('0', 'necesidad' + (j === 0 ? ' sep' : ''))); }
            filas.append(tr);
        }

        const disp = $('disponibles');
        disp.replaceChildren();
        d.disp.forEach((v, j) => disp.append(el('tr', {}, el('td', {}, 'R' + (j + 1)), celda(entrada(v)))));

        const sel = $('sol-proceso');
        sel.replaceChildren();
        for (let i = 0; i < n; i++) { sel.append(el('option', { value: i }, nombre(i))); }

        const vecSol = $('sol-vector');
        vecSol.replaceChildren();
        for (let j = 0; j < R; j++) {
            const inp = el('input', { type: 'number', min: 0, max: 99, class: 'cel', 'aria-label': 'Solicitud de R' + (j + 1) });
            inp.value = 0;
            vecSol.append(el('label', { class: 'campo mini' }, 'R' + (j + 1), inp));
        }

        recalcular();
        ocultarResultados();
    }

    function valoresFila(tr) {
        const vals = Array.from(tr.querySelectorAll('input'), (i) => (i.value === '' ? NaN : parseInt(i.value, 10)));
        return { asig: vals.slice(0, R), max: vals.slice(R, 2 * R) };
    }

    function recalcular() {
        $('filas').querySelectorAll('tr').forEach((tr) => {
            const v = valoresFila(tr);
            tr.querySelectorAll('td.necesidad').forEach((td, j) => {
                const n = v.max[j] - v.asig[j];
                td.textContent = Number.isNaN(n) ? '–' : n;
                td.classList.toggle('neg', n < 0);
            });
        });
    }

    function leer() {
        const asig = [];
        const max = [];
        $('filas').querySelectorAll('tr').forEach((tr) => {
            const v = valoresFila(tr);
            asig.push(v.asig);
            max.push(v.max);
        });
        const disp = Array.from($('disponibles').querySelectorAll('input'), (i) => (i.value === '' ? NaN : parseInt(i.value, 10)));
        return { asig, max, disp };
    }

    function limpio(d) {
        const cero = (x) => (Number.isNaN(x) ? 0 : x);
        return { asig: d.asig.map((f) => f.map(cero)), max: d.max.map((f) => f.map(cero)), disp: d.disp.map(cero) };
    }

    function ocultarResultados() {
        $('res-banquero').hidden = true;
        $('res-solicitud').hidden = true;
        $('res-detectar').hidden = true;
        $('d-vacio').hidden = false;
    }

    /* ---------- tamaño de las matrices ---------- */

    $('btn-agregar').addEventListener('click', () => {
        const d = limpio(leer());
        if (d.asig.length >= MAX_PROC) { mostrarError('El máximo es de ' + MAX_PROC + ' procesos.'); return; }
        d.asig.push(Array(R).fill(0));
        d.max.push(Array(R).fill(0));
        pintar(d);
        mostrarError('');
    });

    $('btn-quitar').addEventListener('click', () => {
        const d = limpio(leer());
        if (d.asig.length <= 1) { mostrarError('Debe quedar al menos un proceso.'); return; }
        d.asig.pop();
        d.max.pop();
        pintar(d);
        mostrarError('');
    });

    $('btn-agregar-rec').addEventListener('click', () => {
        const d = limpio(leer());
        if (R >= MAX_REC) { mostrarError('El máximo es de ' + MAX_REC + ' recursos.'); return; }
        d.asig.forEach((f) => f.push(0));
        d.max.forEach((f) => f.push(0));
        d.disp.push(0);
        pintar(d);
        mostrarError('');
    });

    $('btn-quitar-rec').addEventListener('click', () => {
        const d = limpio(leer());
        if (R <= 1) { mostrarError('Debe quedar al menos un recurso.'); return; }
        d.asig.forEach((f) => f.pop());
        d.max.forEach((f) => f.pop());
        d.disp.pop();
        pintar(d);
        mostrarError('');
    });

    document.querySelectorAll('[data-ejemplo]').forEach((b) => {
        b.addEventListener('click', () => {
            pintar(EJEMPLOS[b.dataset.ejemplo]);
            mostrarError('');
        });
    });

    /* ---------- comunicación con el servidor ---------- */

    const cuerpo = (d) => ({ asignacion: d.asig, maximo: d.max, disponible: d.disp });

    async function enviar(url, datos) {
        mostrarError('');
        const valores = [...datos.asignacion.flat(), ...datos.maximo.flat(), ...datos.disponible, ...(datos.solicitud || [])];
        if (valores.some(Number.isNaN)) {
            mostrarError('Completa todas las celdas con números enteros.');
            return null;
        }
        try {
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': TOKEN
                },
                body: JSON.stringify(datos)
            });
            const json = await r.json();
            if (!r.ok) {
                const primero = json.errors ? Object.values(json.errors)[0][0] : null;
                mostrarError(primero || json.message || 'No se pudo procesar la solicitud.');
                return null;
            }
            return json;
        } catch (e) {
            mostrarError('Error de conexión con el servidor.');
            return null;
        }
    }

    /* ---------- algoritmo del banquero ---------- */

    $('btn-banquero').addEventListener('click', async () => {
        const r = await enviar(URL_BANQUERO, cuerpo(leer()));
        if (!r) { return; }

        $('res-banquero').hidden = false;
        ponerEstado($('b-estado'), r.seguro ? 'SISTEMA SEGURO' : 'SISTEMA INSEGURO', r.seguro ? 'ok' : 'error');
        $('b-secuencia').textContent = r.seguro
            ? 'Secuencia segura: ' + nombres(r.secuencia, ' → ')
            : 'No existe una secuencia segura.';
        $('b-nota').textContent = r.seguro
            ? ''
            : 'No pueden terminar: ' + r.restantes.map((p) => nombre(p.proceso) + ' (necesita ' + vec(p.necesidad) + ')').join('; ')
              + '. Trabajo disponible al detenerse: ' + vec(r.trabajo_final) + '.';

        const caja = $('b-pasos');
        caja.replaceChildren();
        if (r.pasos.length === 0) {
            caja.append(el('p', { class: 'vacio' }, 'Ningún proceso puede terminar con los recursos disponibles.'));
            return;
        }
        caja.append(tabla(
            ['Paso', 'Proceso que termina', 'Necesidad', 'Trabajo antes', '¿Necesidad ≤ Trabajo?', 'Trabajo después (+ asignación)'],
            r.pasos.map((p, k) => [k + 1, nombre(p.proceso), vec(p.necesidad), vec(p.antes), '✓ Sí', vec(p.trabajo)])
        ));
    });

    /* ---------- solicitud de recursos ---------- */

    $('btn-solicitud').addEventListener('click', async () => {
        const solicitud = Array.from($('sol-vector').querySelectorAll('input'), (i) => (i.value === '' ? NaN : parseInt(i.value, 10)));
        const proceso = parseInt($('sol-proceso').value, 10);
        const r = await enviar(URL_SOLICITUD, { ...cuerpo(leer()), proceso, solicitud });
        if (!r) { return; }

        $('res-solicitud').hidden = false;
        ponerEstado($('s-estado'), r.concedida ? 'SOLICITUD CONCEDIDA' : 'SOLICITUD DENEGADA', r.concedida ? 'ok' : 'error');
        $('s-motivo').textContent = r.motivo;

        const lista = $('s-verif');
        lista.replaceChildren();
        r.verificaciones.forEach((v) => {
            lista.append(el('li', { class: v.cumple ? 'si' : 'no' }, (v.cumple ? '✓ ' : '✗ ') + v.texto + ': ' + v.detalle));
        });

        $('s-secuencia').textContent = r.concedida
            ? 'Secuencia segura tras conceder: ' + nombres(r.secuencia, ' → ') + '  ·  Disponible tras conceder: ' + vec(r.estado.disponible)
            : '';
    });

    /* ---------- detección, grafo y recuperación ---------- */

    $('btn-detectar').addEventListener('click', async () => {
        const r = await enviar(URL_DETECTAR, cuerpo(leer()));
        if (!r) { return; }

        $('d-vacio').hidden = true;
        $('res-detectar').hidden = false;
        ponerEstado($('d-estado'), r.interbloqueo ? 'INTERBLOQUEO DETECTADO' : 'NO HAY INTERBLOQUEOS', r.interbloqueo ? 'error' : 'ok');
        $('d-detalle').textContent = r.interbloqueo
            ? 'Procesos bloqueados: ' + nombres(r.bloqueados, ', ') + '.'
            : 'Todos los procesos pueden terminar con los recursos disponibles.';

        dibujarGrafo(r.grafo);
        pintarCiclos(r);
        pintarRecuperacion(r);
    });

    function pintarCiclos(r) {
        const cont = $('d-ciclos');
        cont.replaceChildren();
        const ciclos = r.grafo.ciclos;

        if (ciclos.length === 0) {
            cont.append(el('p', {}, 'El grafo no tiene ciclos.'));
            return;
        }
        cont.append(el('h3', {}, 'Ciclos encontrados (' + ciclos.length + ')'));
        const ul = el('ul', { class: 'lista' });
        ciclos.forEach((c) => ul.append(el('li', {}, c.concat(c[0]).join(' → '))));
        cont.append(ul);
        if (!r.interbloqueo) {
            cont.append(el('p', { class: 'nota' }, 'Hay ciclo, pero el análisis de matrices indica que no hay interbloqueo: con varias instancias por recurso, algún proceso puede terminar y liberarlas.'));
        }
    }

    function pintarRecuperacion(r) {
        const cont = $('d-recuperacion');
        cont.replaceChildren();
        if (!r.interbloqueo) { return; }

        cont.append(el('h3', {}, 'Estrategias de recuperación'));

        cont.append(el('h4', {}, 'Terminación de procesos'));
        const mejorT = r.recuperacion.terminacion.find((t) => t.resuelve);
        cont.append(el('div', { class: 'sugerencia' }, mejorT
            ? 'Sugerencia: terminar ' + nombre(mejorT.proceso) + ' libera ' + vec(mejorT.libera) + ' y resuelve el interbloqueo con el menor costo.'
            : 'Ningún proceso por sí solo resuelve el interbloqueo; habría que terminar varios.'));
        const ulT = el('ul', { class: 'lista' });
        r.recuperacion.terminacion.forEach((t) => {
            ulT.append(el('li', { class: t.resuelve ? 'si' : 'no' },
                'Terminar ' + nombre(t.proceso) + ' (libera ' + vec(t.libera) + '): '
                + (t.resuelve ? '✓ resuelve el interbloqueo' : '✗ no basta, siguen bloqueados ' + nombres(t.bloqueados_restantes, ', '))));
        });
        cont.append(ulT);

        cont.append(el('h4', {}, 'Apropiación de recursos'));
        const mejorA = r.recuperacion.apropiacion.find((a) => a.resuelve);
        cont.append(el('div', { class: 'sugerencia' }, mejorA
            ? 'Sugerencia: quitarle ' + mejorA.cantidad + ' de R' + (mejorA.recurso + 1) + ' a ' + nombre(mejorA.proceso) + ' resuelve el interbloqueo; ese proceso debe volver a pedirlo después.'
            : 'Ninguna apropiación simple resuelve el interbloqueo.'));
        const ulA = el('ul', { class: 'lista' });
        r.recuperacion.apropiacion.forEach((a) => {
            ulA.append(el('li', { class: a.resuelve ? 'si' : 'no' },
                'Quitar ' + a.cantidad + ' de R' + (a.recurso + 1) + ' a ' + nombre(a.proceso) + ': '
                + (a.resuelve ? '✓ resuelve el interbloqueo' : '✗ no basta, siguen bloqueados ' + nombres(a.bloqueados_restantes, ', '))));
        });
        cont.append(ulA);
    }

    /* ---------- dibujo del grafo (SVG) ---------- */

    function recortar(nodo, control, radio) {
        const dx = control.x - nodo.x;
        const dy = control.y - nodo.y;
        const largo = Math.hypot(dx, dy) || 1;
        return { x: nodo.x + dx / largo * radio, y: nodo.y + dy / largo * radio };
    }

    function dibujarGrafo(g) {
        const svg = $('grafo');
        svg.replaceChildren();

        const procesos = g.nodos.filter((n) => n.tipo === 'proceso');
        const recursos = g.nodos.filter((n) => n.tipo === 'recurso');
        const ancho = Math.max(560, 90 * Math.max(procesos.length, recursos.length));
        const alto = 320;
        svg.setAttribute('viewBox', '0 0 ' + ancho + ' ' + alto);

        const pos = {};
        procesos.forEach((n, i) => { pos[n.id] = { x: ancho * (i + 0.5) / procesos.length, y: 70 }; });
        recursos.forEach((n, i) => { pos[n.id] = { x: ancho * (i + 0.5) / recursos.length, y: 250 }; });

        const defs = svgEl('defs');
        [['f-gris', 'var(--suave)'], ['f-roja', 'var(--error)']].forEach(([id, color]) => {
            const m = svgEl('marker', { id, viewBox: '0 0 10 10', refX: 9, refY: 5, markerWidth: 7, markerHeight: 7, orient: 'auto' });
            m.append(svgEl('path', { d: 'M0,0 L10,5 L0,10 z', style: 'fill:' + color }));
            defs.append(m);
        });
        svg.append(defs);

        g.aristas.forEach((a) => {
            const A = pos[a.de];
            const B = pos[a.a];
            const dx = B.x - A.x;
            const dy = B.y - A.y;
            const largo = Math.hypot(dx, dy) || 1;
            const C = { x: (A.x + B.x) / 2 - dy / largo * 35, y: (A.y + B.y) / 2 + dx / largo * 35 };
            const S = recortar(A, C, 24);
            const E = recortar(B, C, 24);

            svg.append(svgEl('path', {
                d: 'M' + S.x + ',' + S.y + ' Q' + C.x + ',' + C.y + ' ' + E.x + ',' + E.y,
                class: 'arista ' + a.tipo + (a.ciclo ? ' ciclo' : ''),
                'marker-end': 'url(#' + (a.ciclo ? 'f-roja' : 'f-gris') + ')'
            }));
            svg.append(svgEl('text', {
                x: 0.25 * S.x + 0.5 * C.x + 0.25 * E.x,
                y: 0.25 * S.y + 0.5 * C.y + 0.25 * E.y,
                class: 'cant'
            }, String(a.cantidad)));
        });

        procesos.forEach((n) => {
            const p = pos[n.id];
            svg.append(svgEl('circle', { cx: p.x, cy: p.y, r: 20, class: 'nodo-p' + (n.bloqueado ? ' bloqueado' : '') }));
            svg.append(svgEl('text', { x: p.x, y: p.y }, n.id));
        });
        recursos.forEach((n) => {
            const p = pos[n.id];
            svg.append(svgEl('rect', { x: p.x - 20, y: p.y - 17, width: 40, height: 34, rx: 3, class: 'nodo-r' }));
            svg.append(svgEl('text', { x: p.x, y: p.y }, n.id));
        });
    }

    pintar(EJEMPLOS.seguro);
})();
</script>
@endpush
