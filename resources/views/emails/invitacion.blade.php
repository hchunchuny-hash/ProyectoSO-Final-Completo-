<!DOCTYPE html>
<html lang="es">
<body style="font-family:Arial,sans-serif;color:#222;line-height:1.5">
    <h2>Invitación a SysMonitor</h2>
    <p>Hola {{ $usuario->name }},</p>
    <p>El administrador te creó una cuenta de observador (solo lectura) en SysMonitor. Para activarla, define tu propia contraseña con este enlace:</p>
    <p><a href="{{ $enlace }}">Definir mi contraseña</a></p>
    <p>Si el enlace no abre, copia esta dirección en tu navegador:<br>{{ $enlace }}</p>
    <p>El enlace sirve una sola vez y vence el {{ $expira->timezone('America/Guatemala')->format('d/m/Y H:i') }} (hora de Guatemala).</p>
    <p>Si no esperabas este mensaje, ignóralo: mientras no uses el enlace, la cuenta no se activa.</p>
</body>
</html>
