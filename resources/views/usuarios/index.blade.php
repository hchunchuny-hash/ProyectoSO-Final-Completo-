@extends('layouts.app')

@section('titulo', 'Administración')
@section('subtitulo', 'Usuarios que pueden entrar a SysMonitor')

@push('estilos')
<style>
  .encabezado{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
  .encabezado h2{margin:0;font:600 16px var(--mono)}
  .filtros{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin-bottom:16px}
  .filtros .campo{margin-top:0;min-width:180px}
  .paginas{display:flex;justify-content:space-between;margin-top:14px}
  .mensaje-ok{margin:0 0 16px;padding:10px 14px;border:1px solid var(--ok);border-radius:var(--radio);color:var(--ok);font-size:13px}
  .mensaje-aviso{margin:0 0 16px;padding:10px 14px;border:1px solid var(--acento);border-radius:var(--radio);color:var(--acento);font-size:13px}
  .btn.chico{padding:4px 10px;font-size:12px}
  .btn.peligro{background:transparent;color:var(--error);border:1px solid var(--error)}
  .acc{display:flex;flex-wrap:wrap;gap:6px}
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

    <div class="encabezado">
        <h2>Usuarios</h2>
        <a href="{{ route('usuarios.create') }}" class="btn">+ Crear usuario</a>
    </div>

    <form method="GET" action="{{ route('usuarios.index') }}" class="filtros">
        <label class="campo" for="q">Buscar
            <input id="q" type="text" name="q" value="{{ $texto }}" placeholder="nombre, correo o institución">
        </label>
        <label class="campo" for="rol">Rol
            <select id="rol" name="rol">
                <option value="">Todos</option>
                <option value="admin" @selected($rol === 'admin')>Administrador</option>
                <option value="observador" @selected($rol === 'observador')>Observador</option>
            </select>
        </label>
        <label class="campo" for="estado">Estado
            <select id="estado" name="estado">
                <option value="">Todos</option>
                @foreach (['activo' => 'Activo', 'pendiente' => 'Pendiente', 'inactivo' => 'Inactivo', 'vencido' => 'Vencido'] as $valor => $texto_estado)
                    <option value="{{ $valor }}" @selected($estado === $valor)>{{ $texto_estado }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="btn">Filtrar</button>
        <a href="{{ route('usuarios.index') }}" class="btn sec">Limpiar</a>
    </form>

    @php $colores = ['activo' => 'ok', 'pendiente' => 'aviso', 'inactivo' => 'error', 'vencido' => 'error']; @endphp

    <section class="tarjeta">
        <div class="caja-tabla">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Vigencia hasta</th>
                        <th>Último acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($usuarios as $u)
                    @php
                        $e = $u->estadoEfectivo();
                        $ua = $ultimos[$u->id] ?? null;
                    @endphp
                    <tr>
                        <td>
                            {{ $u->name }}
                            @if ($u->es_principal)
                                <span class="insignia aviso">PRINCIPAL</span>
                            @endif
                        </td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->role === 'admin' ? 'Administrador' : 'Observador' }}</td>
                        <td><span class="insignia {{ $colores[$e] ?? '' }}">{{ strtoupper($e) }}</span></td>
                        <td>{{ $u->vigente_hasta ? $u->vigente_hasta->format('d/m/Y') : 'Sin vencimiento' }}</td>
                        <td>{{ $ua ? \Illuminate\Support\Carbon::parse($ua, 'UTC')->timezone('America/Guatemala')->format('d/m/Y H:i') : 'Nunca' }}</td>
                        <td>
                            <div class="acc">
                                <a href="{{ route('usuarios.show', $u) }}" class="btn sec chico">Ver</a>
                                <a href="{{ route('usuarios.edit', $u) }}" class="btn sec chico">Editar</a>

                                @if ($u->estado === 'pendiente')
                                    <form method="POST" action="{{ route('usuarios.reenviar', $u) }}">
                                        @csrf
                                        <button type="submit" class="btn sec chico">Reenviar invitación</button>
                                    </form>
                                @endif

                                @if (! $u->es_principal && $u->id !== auth()->id())
                                    @if ($u->estado === 'inactivo')
                                        <form method="POST" action="{{ route('usuarios.reactivar', $u) }}">
                                            @csrf
                                            <button type="submit" class="btn sec chico">Reactivar</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('usuarios.desactivar', $u) }}"
                                              onsubmit="return confirm('¿Desactivar esta cuenta? Se cerrarán sus sesiones abiertas.')">
                                            @csrf
                                            <button type="submit" class="btn peligro chico">Desactivar</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No hay usuarios con ese filtro.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginas">
            @if ($usuarios->previousPageUrl())
                <a class="btn sec" href="{{ $usuarios->previousPageUrl() }}">← Anteriores</a>
            @else
                <span></span>
            @endif
            @if ($usuarios->nextPageUrl())
                <a class="btn sec" href="{{ $usuarios->nextPageUrl() }}">Siguientes →</a>
            @endif
        </div>
    </section>
@endsection
