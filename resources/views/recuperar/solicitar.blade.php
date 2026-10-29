<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar contraseña · SysMonitor</title>
<link rel="stylesheet" href="{{ asset('css/sysmonitor.css') }}">
<link rel="stylesheet" href="{{ asset('css/componentes.css') }}">
<style>
  .acceso{flex:1;display:grid;place-items:center;padding:24px}
  .caja{width:100%;max-width:400px}
  .caja .marca{padding:0 0 18px;font-size:20px}
  .enlace{display:block;margin-top:14px;text-align:center;font-size:13px;color:var(--acento)}
</style>
</head>
<body>
<main class="acceso">
  <div class="caja">
    <div class="marca">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true"><rect x="3" y="3" width="18" height="18"/><path d="M6 13h3l2-5 3 9 2-4h2"/></svg>
      SysMonitor<em>Grupo 4</em>
    </div>
    <form method="POST" action="{{ route('recuperar.enviar') }}" class="tarjeta">
      @csrf
      <h2>Recuperar contraseña</h2>
      <p class="nota">Escribe el correo de tu cuenta. Si está registrada y activa, te enviamos un código de 6 dígitos.</p>
      <label class="campo" for="email">Correo
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
      </label>
      @error('email')
        <p class="error">{{ $message }}</p>
      @enderror
      <button type="submit" class="btn bloque">Enviar código</button>
      <a class="enlace" href="{{ route('recuperar.codigo') }}">Ya tengo un código</a>
      <a class="enlace" href="{{ route('login') }}">← Volver al inicio de sesión</a>
    </form>
  </div>
</main>
</body>
</html>
