@extends('layouts.app')

@section('titulo', 'Administración')
@section('subtitulo', 'Ficha de usuario')

@push('estilos')
<style>
  .encabezado{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
  .encabezado h2{margin:0;font:600 16px var(--mono)}
  .separado{margin-top:16px}
  .ficha{display:grid;gap:14px 24px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));margin:0}
  .ficha dt{color:var(--suave);font-size:12px;font-family:var(--mono)}
  .ficha dd{margin:2px 0 0;font-size:14px;overflow-wrap:anywhere}
  .ficha .ancho{grid-column:1/-1}
  .mensaje-ok{margin:0 0 16px;padding:10px 14px;border:1px solid var(--ok);border-radius:var(--radio);color:var(--ok);font-size:13px}
  .mensaje-aviso{margin:0 0 16px;padding:10px 14px;border:1px solid var(--acento);border-radius:var(--radio);color:var(--acento);font-size:13px}
  .btn.chico{padding:4px 10px;font-size:12px}
  .btn.peligro{background:transparent;color:var(--error);border:1px solid var(--error)}
  .acc{display:flex;flex-wrap:wrap;gap:8px}
  .acc form{margin:0}
</style>
@endpush

@section('contenido')
    @include('usuarios._pestanas')

    @if (session('ok'))
        <p class="mensaje-ok">{{ session('ok') }}</p>
    @endif
    @if (session('aviso'))
        <p class="mensaje-aviso">{{ session('aviso') }}</p>
    @endif

    @php
        $e = $usuario->estadoEfectivo();
        $colores = ['activo' => 'ok', 'pendiente' => 'aviso', 'inactivo' => 'error', 'vencido' => 'error'];
        $zona = 'America/Guatemala';
    @endphp

    <div class="encabezado">
        <h2>{{ $usuario->name }}
            @if ($usuario->es_principal)
                <span class="insignia aviso">PRINCIPAL</span>
            @endif
            <span class="insignia {{ $colores[$e] ?? '' }}">{{ strtoupper($e) }}</span>
        </h2>
        <div class="acc">
            <a href="{{ route('usuarios.index') }}" class="btn sec chico">← Volver al listado</a>
            <a href="{{ route('usuarios.edit', $usuario) }}" class="btn chico">Editar</a>

            @if ($usuario->estado === 'pendiente')
                <form method="POST" action="{{ route('usuarios.reenviar', $usuario) }}">
                    @csrf
                    <button type="submit" class="btn sec chico">Reenviar invitación</button>
                </form>
            @endif

            @if (! $usuario->es_principal && $usuario->id !== auth()->id())
                @if ($usuario->estado === 'inactivo')
                    <form method="POST" action="{{ route('usuarios.reactivar', $usuario) }}">
                        @csrf
                        <button type="submit" class="btn sec chico">Reactivar</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('usuarios.desactivar', $usuario) }}"
                          onsubmit="return confirm('¿Desactivar esta cuenta? Se cerrarán sus sesiones abiertas.')">
                        @csrf
                        <button type="submit" class="btn peligro chico">Desactivar</button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <section class="tarjeta">
        <h2>Datos de la cuenta</h2>
        <dl class="ficha">
            <div><dt>Correo</dt><dd>{{ $usuario->email }}</dd></div>
            <div><dt>Rol</dt><dd>{{ $usuario->role === 'admin' ? 'Administrador' : 'Observador' }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ $usuario->telefono ?: '—' }}</dd></div>
            <div><dt>Institución o cargo</dt><dd>{{ $usuario->institucion ?: '—' }}</dd></div>
            <div><dt>Vigente desde</dt><dd>{{ $usuario->vigente_desde ? $usuario->vigente_desde->format('d/m/Y') : '—' }}</dd></div>
            <div><dt>Vence el</dt><dd>{{ $usuario->vigente_hasta ? $usuario->vigente_hasta->format('d/m/Y') : 'Sin vencimiento' }}</dd></div>
            <div><dt>Creada por</dt><dd>{{ $creador ? $creador->name . ' (' . $creador->email . ')' : '—' }}</dd></div>
            <div><dt>Fecha de creación</dt><dd>{{ $usuario->created_at ? $usuario->created_at->timezone($zona)->format('d/m/Y H:i') : '—' }}</dd></div>
            <div><dt>Último acceso</dt><dd>{{ $ultimoAcceso ? $ultimoAcceso->timezone($zona)->format('d/m/Y H:i') : 'Nunca' }}</dd></div>
            <div>
                <dt>Invitación</dt>
                <dd>
                    @if (! $invitacion)
                        No aplica
                    @elseif ($invitacion->usada_at)
                        Aceptada el {{ $invitacion->usada_at->timezone($zona)->format('d/m/Y H:i') }}
                    @elseif ($invitacion->expira_at->isPast())
                        Vencida el {{ $invitacion->expira_at->timezone($zona)->format('d/m/Y H:i') }}
                    @else
                        Vigente hasta {{ $invitacion->expira_at->timezone($zona)->format('d/m/Y H:i') }}
                    @endif
                </dd>
            </div>
            <div class="ancho"><dt>Motivo de acceso</dt><dd>{{ $usuario->motivo_acceso ?: '—' }}</dd></div>
            <div class="ancho"><dt>Notas internas</dt><dd>{{ $usuario->notas_admin ?: '—' }}</dd></div>
        </dl>
    </section>

    <section class="tarjeta separado">
        <h2>Historial de accesos</h2>
        <p class="nota">Últimos 20 intentos de inicio de sesión de esta cuenta</p>
        <div class="caja-tabla">
            <table class="tabla">
                <thead><tr><th>Fecha y hora</th><th>Resultado</th><th>Motivo</th><th>IP</th><th>Navegador</th></tr></thead>
                <tbody>
                @forelse ($accesos as $a)
                    <tr>
                        <td class="num">{{ $a->created_at->timezone($zona)->format('d/m/Y H:i:s') }}</td>
                        <td><span class="insignia {{ $a->exito ? 'ok' : 'error' }}">{{ $a->exito ? 'ÉXITO' : 'FALLIDO' }}</span></td>
                        <td>{{ $a->motivo ?? '—' }}</td>
                        <td>{{ $a->ip ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($a->navegador ?? '—', 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Esta cuenta todavía no tiene accesos registrados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="tarjeta separado">
        <h2>Historial de acciones</h2>
        <p class="nota">Bitácora: acciones hechas por esta cuenta o sobre ella (últimas 30)</p>
        <div class="caja-tabla">
            <table class="tabla">
                <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Objetivo</th><th>Resultado</th></tr></thead>
                <tbody>
                @forelse ($acciones as $r)
                    <tr>
                        <td class="num">{{ $r->created_at->timezone($zona)->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $r->usuario }}</td>
                        <td>{{ $r->accion }}</td>
                        <td>{{ $r->objetivo ?? '—' }}</td>
                        <td><span class="insignia {{ ['exito' => 'ok', 'error' => 'error', 'denegado' => 'aviso'][$r->resultado] ?? '' }}">{{ strtoupper($r->resultado) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Sin acciones registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
