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
</style>
@endpush

@section('contenido')
    @include('usuarios._pestanas')

    @if (session('ok'))
        <p class="mensaje-ok">{{ session('ok') }}</p>
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
                        <th>Institución o cargo</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($usuarios as $u)
                    @php $e = $u->estadoEfectivo(); @endphp
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
                        <td>{{ $u->institucion ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No hay usuarios con ese filtro.</td></tr>
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
