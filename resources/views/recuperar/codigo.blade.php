<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Escribir código · SysMonitor</title>
<link rel="stylesheet" href="{{ asset('css/sysmonitor.css') }}">
<link rel="stylesheet" href="{{ asset('css/componentes.css') }}">
<style>
  .acceso{flex:1;display:grid;place-items:center;padding:24px}
  .caja{width:100%;max-width:420px}
  .caja .marca{padding:0 0 18px;font-size:20px}
  .enlace{display:block;margin-top:14px;text-align:center;font-size:13px;color:var(--acento)}
  input.codigo{letter-spacing:.5em;font:600 22px var(--mono);text-align:center}
  .reglas{margin:12px 0 0;padding-left:18px;color:var(--suave);font-size:12px}
  .mensaje-ok{margin:12px 0 0;padding:10px 12px;border:1px solid var(--ok);border-radius:var(--radio);color:var(--ok);font-size:13px}
</style>
</head>
<body>
<main class="acceso">
  <div class="caja">
    <div class="marca">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true"><rect x="3" y="3" width="18" height="18"/><path d="M6 13h3l2-5 3 9 2-4h2"/></svg>
      SysMonitor<em>Grupo 4</em>
    </div>
    <form method="POST" action="{{ route('recuperar.restablecer') }}" class="tarjeta">
      @csrf
      <h2>Escribe tu código</h2>
      <p class="nota">Revisa tu correo, escribe el código y elige tu contraseña nueva.</p>

      @if (session('status'))
        <p class="mensaje-ok">{{ session('status') }}</p>
      @endif

      <label class="campo" for="email">Correo
        <input id="email" type="email" name="email" value="{{ $correo }}" required>
      </label>
      @error('email')
        <p class="error">{{ $message }}</p>
      @enderror

      <label class="campo" for="codigo">Código de 6 dígitos
        <input id="codigo" class="codigo" type="text" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
      </label>
      @error('codigo')
        <p class="error">{{ $message }}</p>
      @enderror

      <label class="campo" for="password">Contraseña nueva
        <input id="password" type="password" name="password" required autocomplete="new-password">
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
        <li>Distinta de tu correo y de tu contraseña anterior</li>
      </ul>

      <button type="submit" class="btn bloque">Cambiar contraseña</button>
      <a class="enlace" href="{{ route('recuperar.solicitar') }}">Pedir un código nuevo</a>
      <a class="enlace" href="{{ route('login') }}">← Volver al inicio de sesión</a>
    </form>
  </div>
</main>
</body>
</html>
