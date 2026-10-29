<!DOCTYPE html>
<html lang="es">
<body style="font-family:Arial,sans-serif;color:#222;line-height:1.5">
    <h2>Recuperación de contraseña</h2>
    <p>Hola {{ $usuario->name }},</p>
    <p>Usa este código para elegir una contraseña nueva en SysMonitor:</p>
    <p style="font-size:32px;font-weight:bold;letter-spacing:8px;margin:16px 0">{{ $codigo }}</p>
    <p>El código vence en {{ $minutos }} minutos y sirve una sola vez. Tienes 5 intentos para escribirlo.</p>
    <p>Si tú no lo pediste, ignora este mensaje: tu contraseña no cambia mientras nadie use el código. No lo compartas con nadie.</p>
</body>
</html>
