<!DOCTYPE html>
<html lang="es">
<body style="font-family:Arial,sans-serif;color:#222;line-height:1.5">
    <h2>Tu contraseña fue cambiada</h2>
    <p>Hola {{ $usuario->name }},</p>
    <p>La contraseña de tu cuenta en SysMonitor se cambió el {{ $cuando }} (hora de Guatemala){{ $ip ? ' desde la dirección ' . $ip : '' }}.</p>
    <p>Por seguridad, se cerraron todas tus sesiones abiertas.</p>
    <p><b>Si no fuiste tú</b>, avisa de inmediato al administrador del sistema.</p>
</body>
</html>
