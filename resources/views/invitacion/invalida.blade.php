<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invitación no válida · SysMonitor</title>
<link rel="stylesheet" href="{{ asset('css/sysmonitor.css') }}">
<link rel="stylesheet" href="{{ asset('css/componentes.css') }}">
<style>
  .acceso{flex:1;display:grid;place-items:center;padding:24px}
  .caja{width:100%;max-width:420px}
  .caja .marca{padding:0 0 18px;font-size:20px}
</style>
</head>
<body>
<main class="acceso">
  <div class="caja">
    <div class="marca">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true"><rect x="3" y="3" width="18" height="18"/><path d="M6 13h3l2-5 3 9 2-4h2"/></svg>
      SysMonitor<em>Grupo 4</em>
    </div>
    <section class="tarjeta">
      <h2>Invitación no válida</h2>
      <p class="nota">Este enlace ya se usó, venció o fue reemplazado por uno más reciente.</p>
      <p>Pide al administrador que te envíe una invitación nueva.</p>
      <a href="{{ route('login') }}" class="btn bloque" style="text-align:center">Ir al inicio de sesión</a>
    </section>
  </div>
</main>
</body>
</html>
