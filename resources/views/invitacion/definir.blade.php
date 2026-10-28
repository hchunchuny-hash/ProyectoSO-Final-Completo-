<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Definir contraseña · SysMonitor</title>
<link rel="stylesheet" href="{{ asset('css/sysmonitor.css') }}">
<link rel="stylesheet" href="{{ asset('css/componentes.css') }}">
<style>
  .acceso{flex:1;display:grid;place-items:center;padding:24px}
  .caja{width:100%;max-width:420px}
  .caja .marca{padding:0 0 18px;font-size:20px}
  .reglas{margin:12px 0 0;padding-left:18px;color:var(--suave);font-size:12px}
  .dato-fijo{margin-top:14px;color:var(--suave);font-size:13px}
  .dato-fijo b{display:block;color:var(--texto);font-weight:600;margin-top:2px}
</style>
</head>
<body>
<main class="acceso">
  <div class="caja">
    <div class="marca">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true"><rect x="3" y="3" width="18" height="18"/><path d="M6 13h3l2-5 3 9 2-4h2"/></svg>
      SysMonitor<em>Grupo 4</em>
    </div>
    <form method="POST" action="{{ route('invitacion.aceptar', $token) }}" class="tarjeta">
      @csrf
      <h2>Define tu contraseña</h2>
      <p class="nota">Activa tu cuenta de observador (solo lectura)</p>

      <p class="dato-fijo">Nombre<b>{{ $usuario->name }}</b></p>
      <p class="dato-fijo">Correo<b>{{ $usuario->email }}</b></p>

      <label class="campo" for="password">Nueva contraseña
        <input id="password" type="password" name="password" required autofocus autocomplete="new-password">
      </label>
      <label class="campo" for="password_confirmation">Repite la contraseña
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
      </label>
      @error('password')
        <p class="error">{{ $message }}</p>
      @enderror

      <ul class="reglas">
        <li>Mínimo 10 caracteres</li>
        <li>Mayúsculas, minúsculas y al menos un número</li>
        <li>Distinta de tu correo</li>
      </ul>

      <button type="submit" class="btn bloque">Activar mi cuenta</button>
    </form>
  </div>
</main>
</body>
</html>
