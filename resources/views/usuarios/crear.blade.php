@extends('layouts.app')

@section('titulo', 'Administración')
@section('subtitulo', 'Crear un usuario observador')

@push('estilos')
<style>
  .formulario{max-width:760px}
  .dos{display:grid;gap:0 16px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
  .campo textarea{display:block;width:100%;margin-top:4px;padding:9px 10px;background:var(--fondo);color:var(--texto);border:1px solid var(--borde);border-radius:var(--radio);font:inherit;resize:vertical}
  .campo textarea:focus-visible{outline:2px solid var(--acento);outline-offset:1px}
  .acciones{display:flex;gap:12px;margin-top:20px}
  .campo small{display:block;margin-top:4px;color:var(--suave);font-size:12px}
</style>
@endpush

@section('contenido')
    @include('usuarios._pestanas')

    <form method="POST" action="{{ route('usuarios.store') }}" class="tarjeta formulario">
        @csrf
        <h2>Ficha del observador</h2>
        <p class="nota">La cuenta se crea siempre como Observador (solo lectura) y queda Pendiente hasta que la persona defina su propia contraseña con el enlace de invitación. El administrador nunca conoce esa contraseña.</p>

        <div class="dos">
            <label class="campo" for="name">Nombre completo *
                <input id="name" type="text" name="name" value="{{ old('name') }}" maxlength="120" required autofocus>
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </label>
            <label class="campo" for="email">Correo *
                <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" required>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </label>
        </div>

        <div class="dos">
            <label class="campo" for="telefono">Teléfono
                <input id="telefono" type="text" name="telefono" value="{{ old('telefono') }}" maxlength="30">
                @error('telefono') <p class="error">{{ $message }}</p> @enderror
            </label>
            <label class="campo" for="institucion">Institución o cargo
                <input id="institucion" type="text" name="institucion" value="{{ old('institucion') }}" maxlength="120">
                @error('institucion') <p class="error">{{ $message }}</p> @enderror
            </label>
        </div>

        <label class="campo" for="motivo_acceso">Motivo de acceso *
            <textarea id="motivo_acceso" name="motivo_acceso" rows="3" maxlength="500" required>{{ old('motivo_acceso') }}</textarea>
            <small>Por qué se le da esta cuenta.</small>
            @error('motivo_acceso') <p class="error">{{ $message }}</p> @enderror
        </label>

        <div class="dos">
            <label class="campo" for="vigente_desde">Vigente desde
                <input id="vigente_desde" type="date" name="vigente_desde" value="{{ old('vigente_desde') }}">
                @error('vigente_desde') <p class="error">{{ $message }}</p> @enderror
            </label>
            <label class="campo" for="vigente_hasta">Vence el
                <input id="vigente_hasta" type="date" name="vigente_hasta" value="{{ old('vigente_hasta') }}">
                <small>Opcional. Si lo dejas vacío, la cuenta no vence.</small>
                @error('vigente_hasta') <p class="error">{{ $message }}</p> @enderror
            </label>
        </div>

        <label class="campo" for="notas_admin">Notas internas
            <textarea id="notas_admin" name="notas_admin" rows="2" maxlength="1000">{{ old('notas_admin') }}</textarea>
            <small>Solo las ve el administrador.</small>
            @error('notas_admin') <p class="error">{{ $message }}</p> @enderror
        </label>

        <div class="acciones">
            <button type="submit" class="btn">Crear usuario</button>
            <a href="{{ route('usuarios.index') }}" class="btn sec">Cancelar</a>
        </div>
    </form>
@endsection
