@extends('layouts.app')

@section('titulo', 'Administración')
@section('subtitulo', 'Editar usuario')

@push('estilos')
<style>
  .formulario{max-width:760px}
  .dos{display:grid;gap:0 16px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
  .campo textarea{display:block;width:100%;margin-top:4px;padding:9px 10px;background:var(--fondo);color:var(--texto);border:1px solid var(--borde);border-radius:var(--radio);font:inherit;resize:vertical}
  .campo textarea:focus-visible{outline:2px solid var(--acento);outline-offset:1px}
  .acciones{display:flex;gap:12px;margin-top:20px}
  .campo small{display:block;margin-top:4px;color:var(--suave);font-size:12px}
  .fijo{margin-top:14px;color:var(--suave);font-size:13px}
  .fijo b{display:block;color:var(--texto);font-weight:600;margin-top:2px}
</style>
@endpush

@section('contenido')
    @include('usuarios._pestanas')

    @php $principal = $usuario->es_principal; @endphp

    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="tarjeta formulario">
        @csrf
        @method('PUT')
        <h2>Editar ficha</h2>
        <p class="nota">
            @if ($principal)
                Administrador principal: no se puede desactivar ni cambiar de rol, y su cuenta no vence.
            @else
                El rol no se puede cambiar. Para impedir el acceso usa «Desactivar» en la ficha.
            @endif
        </p>

        <div class="dos">
            <label class="campo" for="name">Nombre completo *
                <input id="name" type="text" name="name" value="{{ old('name', $usuario->name) }}" maxlength="120" required autofocus>
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </label>

            @if ($usuario->estado === 'pendiente')
                <label class="campo" for="email">Correo *
                    <input id="email" type="email" name="email" value="{{ old('email', $usuario->email) }}" maxlength="150" required>
                    <small>Si lo cambias, se envía una invitación nueva a ese correo.</small>
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </label>
            @else
                <p class="fijo">Correo (no editable)<b>{{ $usuario->email }}</b></p>
            @endif
        </div>

        <div class="dos">
            <label class="campo" for="telefono">Teléfono
                <input id="telefono" type="text" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" maxlength="30">
                @error('telefono') <p class="error">{{ $message }}</p> @enderror
            </label>
            <label class="campo" for="institucion">Institución o cargo
                <input id="institucion" type="text" name="institucion" value="{{ old('institucion', $usuario->institucion) }}" maxlength="120">
                @error('institucion') <p class="error">{{ $message }}</p> @enderror
            </label>
        </div>

        <label class="campo" for="motivo_acceso">Motivo de acceso {{ $principal ? '' : '*' }}
            <textarea id="motivo_acceso" name="motivo_acceso" rows="3" maxlength="500" {{ $principal ? '' : 'required' }}>{{ old('motivo_acceso', $usuario->motivo_acceso) }}</textarea>
            @error('motivo_acceso') <p class="error">{{ $message }}</p> @enderror
        </label>

        @unless ($principal)
            <div class="dos">
                <label class="campo" for="vigente_desde">Vigente desde
                    <input id="vigente_desde" type="date" name="vigente_desde" value="{{ old('vigente_desde', $usuario->vigente_desde?->format('Y-m-d')) }}">
                    @error('vigente_desde') <p class="error">{{ $message }}</p> @enderror
                </label>
                <label class="campo" for="vigente_hasta">Vence el
                    <input id="vigente_hasta" type="date" name="vigente_hasta" value="{{ old('vigente_hasta', $usuario->vigente_hasta?->format('Y-m-d')) }}">
                    <small>Déjalo vacío si la cuenta no vence.</small>
                    @error('vigente_hasta') <p class="error">{{ $message }}</p> @enderror
                </label>
            </div>
        @endunless

        <label class="campo" for="notas_admin">Notas internas
            <textarea id="notas_admin" name="notas_admin" rows="2" maxlength="1000">{{ old('notas_admin', $usuario->notas_admin) }}</textarea>
            <small>Solo las ve el administrador.</small>
            @error('notas_admin') <p class="error">{{ $message }}</p> @enderror
        </label>

        <div class="acciones">
            <button type="submit" class="btn">Guardar cambios</button>
            <a href="{{ route('usuarios.show', $usuario) }}" class="btn sec">Cancelar</a>
        </div>
    </form>
@endsection
