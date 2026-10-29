<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Models\Bitacora;
use App\Models\Invitacion;
use App\Models\User;
use App\Services\Invitaciones;
use App\Services\RegistroBitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        $consulta = User::query()->orderByDesc('es_principal')->orderBy('name');
        $hoy = now('America/Guatemala')->toDateString();

        $texto = trim((string) $request->query('q', ''));
        if ($texto !== '') {
            $consulta->where(function ($w) use ($texto) {
                $w->where('name', 'like', "%{$texto}%")
                  ->orWhere('email', 'like', "%{$texto}%")
                  ->orWhere('institucion', 'like', "%{$texto}%");
            });
        }

        $rol = $request->query('rol');
        if (in_array($rol, ['admin', 'observador'], true)) {
            $consulta->where('role', $rol);
        }

        $estado = $request->query('estado');
        if ($estado === 'vencido') {
            $consulta->where('estado', 'activo')
                ->where('es_principal', false)
                ->whereNotNull('vigente_hasta')
                ->whereDate('vigente_hasta', '<', $hoy);
        } elseif ($estado === 'activo') {
            $consulta->where('estado', 'activo')->where(function ($w) use ($hoy) {
                $w->where('es_principal', true)
                  ->orWhereNull('vigente_hasta')
                  ->orWhereDate('vigente_hasta', '>=', $hoy);
            });
        } elseif (in_array($estado, ['inactivo', 'pendiente'], true)) {
            $consulta->where('estado', $estado);
        }

        $usuarios = $consulta->simplePaginate(15)->withQueryString();

        // Último acceso exitoso de cada usuario de la página
        $ultimos = Acceso::whereIn('user_id', $usuarios->pluck('id'))
            ->where('exito', true)
            ->selectRaw('user_id, MAX(created_at) as ultimo')
            ->groupBy('user_id')
            ->pluck('ultimo', 'user_id');

        return view('usuarios.index', compact('usuarios', 'texto', 'rol', 'estado', 'ultimos'));
    }

    public function create()
    {
        return view('usuarios.crear');
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $hoy = now('America/Guatemala')->toDateString();

        $datos = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:150', 'unique:users,email'],
            'telefono'      => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'institucion'   => ['nullable', 'string', 'max:120'],
            'motivo_acceso' => ['required', 'string', 'max:500'],
            'vigente_desde' => ['nullable', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde', 'after_or_equal:' . $hoy],
            'notas_admin'   => ['nullable', 'string', 'max:1000'],
        ], [
            'email.unique'                  => 'Ya existe una cuenta con ese correo.',
            'telefono.regex'                => 'El teléfono solo puede llevar números, espacios, +, - y paréntesis.',
            'vigente_hasta.after_or_equal'  => 'La fecha de vencimiento no puede ser anterior a hoy ni al inicio de la vigencia.',
        ]);

        $usuario = new User($datos);
        // Contraseña aleatoria que nadie conoce: la persona elegirá la suya con la invitación.
        $usuario->password = Str::random(64);
        // Estos campos los fija el código, nunca el formulario.
        $usuario->role = 'observador';
        $usuario->estado = 'pendiente';
        $usuario->es_principal = false;
        $usuario->creado_por = $request->user()->id;
        $usuario->save();

        RegistroBitacora::registrar(
            'Usuario creado',
            $usuario->email,
            RegistroBitacora::EXITO,
            'Rol: observador; estado: pendiente'
        );

        $enviado = Invitaciones::enviar($usuario);
        RegistroBitacora::registrar(
            'Invitación enviada',
            $usuario->email,
            $enviado ? RegistroBitacora::EXITO : RegistroBitacora::ERROR
        );

        if ($enviado) {
            return redirect()->route('usuarios.index')->with('ok', $this->mensajeEnvio($usuario, 'Cuenta creada.'));
        }

        return redirect()->route('usuarios.index')
            ->with('aviso', 'Cuenta creada, pero no se pudo enviar el correo. Usa «Reenviar invitación».');
    }

    public function show(User $usuario)
    {
        $accesos = Acceso::where('user_id', $usuario->id)->orderByDesc('id')->limit(20)->get();

        $ultimoAcceso = Acceso::where('user_id', $usuario->id)
            ->where('exito', true)
            ->orderByDesc('id')
            ->first()?->created_at;

        $acciones = Bitacora::where(function ($w) use ($usuario) {
                $w->where('user_id', $usuario->id)->orWhere('objetivo', $usuario->email);
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $creador = $usuario->creado_por ? User::find($usuario->creado_por) : null;
        $invitacion = Invitacion::where('user_id', $usuario->id)->orderByDesc('id')->first();

        return view('usuarios.ficha', compact('usuario', 'accesos', 'ultimoAcceso', 'acciones', 'creador', 'invitacion'));
    }

    public function edit(User $usuario)
    {
        return view('usuarios.editar', compact('usuario'));
    }

    public function update(Request $request, User $usuario)
    {
        $hoy = now('America/Guatemala')->toDateString();
        $principal = $usuario->esPrincipal();
        $correoEditable = $usuario->estado === 'pendiente';

        if ($correoEditable) {
            $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        }

        $reglas = [
            'name'          => ['required', 'string', 'max:120'],
            'telefono'      => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'institucion'   => ['nullable', 'string', 'max:120'],
            'motivo_acceso' => [$principal ? 'nullable' : 'required', 'string', 'max:500'],
            'notas_admin'   => ['nullable', 'string', 'max:1000'],
        ];

        if ($correoEditable) {
            $reglas['email'] = ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario->id)];
        }

        // El administrador principal no tiene vigencia: nunca vence.
        if (! $principal) {
            $reglas['vigente_desde'] = ['nullable', 'date'];
            $reglas['vigente_hasta'] = [
                'nullable',
                'date',
                'after_or_equal:vigente_desde',
                function (string $atributo, mixed $valor, \Closure $falla) use ($usuario, $hoy) {
                    $actual = $usuario->vigente_hasta?->toDateString();
                    if ((string) $valor < $hoy && (string) $valor !== $actual) {
                        $falla('La fecha de vencimiento no puede ser anterior a hoy.');
                    }
                },
            ];
        }

        $datos = $request->validate($reglas, [
            'email.unique'                 => 'Ya existe una cuenta con ese correo.',
            'telefono.regex'               => 'El teléfono solo puede llevar números, espacios, +, - y paréntesis.',
            'vigente_hasta.after_or_equal' => 'El vencimiento no puede ser anterior al inicio de la vigencia.',
        ]);

        $usuario->fill($datos);
        $campos = array_keys($usuario->getDirty());

        if (empty($campos)) {
            return redirect()->route('usuarios.show', $usuario)->with('aviso', 'No hubo cambios que guardar.');
        }

        $usuario->save();

        RegistroBitacora::registrar('Usuario editado', $usuario->email, RegistroBitacora::EXITO, 'Campos: ' . implode(', ', $campos));

        $mensaje = 'Datos actualizados.';

        // Si se corrigió el correo de una cuenta pendiente, la invitación anterior ya no sirve.
        if ($correoEditable && in_array('email', $campos, true)) {
            $enviado = Invitaciones::enviar($usuario);
            RegistroBitacora::registrar(
                'Invitación enviada (correo corregido)',
                $usuario->email,
                $enviado ? RegistroBitacora::EXITO : RegistroBitacora::ERROR
            );
            $mensaje .= $enviado
                ? ' Se envió una invitación nueva al correo corregido.'
                : ' No se pudo enviar la invitación; usa «Reenviar invitación».';
        }

        return redirect()->route('usuarios.show', $usuario)->with('ok', $mensaje);
    }

    public function desactivar(Request $request, User $usuario)
    {
        $motivo = null;

        if ($usuario->esPrincipal()) {
            $motivo = 'El administrador principal no se puede desactivar.';
        } elseif ($usuario->id === $request->user()->id) {
            $motivo = 'No puedes desactivar tu propia cuenta.';
        } elseif ($usuario->estado === 'inactivo') {
            $motivo = 'La cuenta ya está inactiva.';
        } elseif ($usuario->role === 'admin' && ! $this->quedaOtroAdminActivo($usuario)) {
            $motivo = 'Debe quedar al menos un administrador activo.';
        }

        if ($motivo !== null) {
            RegistroBitacora::registrar('Desactivación denegada', $usuario->email, RegistroBitacora::DENEGADO, $motivo);
            return back()->with('aviso', $motivo);
        }

        $usuario->estado = 'inactivo';
        $usuario->save();

        // Anula invitaciones sin usar y cierra las sesiones abiertas de esa cuenta.
        Invitacion::where('user_id', $usuario->id)
            ->whereNull('usada_at')
            ->update(['expira_at' => now()->toDateTimeString()]);
        DB::table('sessions')->where('user_id', $usuario->id)->delete();

        RegistroBitacora::registrar('Usuario desactivado', $usuario->email);

        return back()->with('ok', "Cuenta de {$usuario->email} desactivada. Sus sesiones abiertas se cerraron.");
    }

    public function reactivar(Request $request, User $usuario)
    {
        if ($usuario->estado !== 'inactivo') {
            return back()->with('aviso', 'Solo se pueden reactivar cuentas inactivas.');
        }

        // ¿Nunca llegó a usar su invitación? Entonces vuelve a Pendiente.
        $nuncaActivada = Invitacion::where('user_id', $usuario->id)->exists()
            && ! Invitacion::where('user_id', $usuario->id)->whereNotNull('usada_at')->exists();

        if ($nuncaActivada) {
            $usuario->estado = 'pendiente';
            $usuario->save();
            $enviado = Invitaciones::enviar($usuario);

            RegistroBitacora::registrar(
                'Usuario reactivado (pendiente de invitación)',
                $usuario->email,
                $enviado ? RegistroBitacora::EXITO : RegistroBitacora::ERROR
            );

            return back()->with(
                $enviado ? 'ok' : 'aviso',
                $enviado
                    ? "Cuenta reactivada como pendiente. Se envió una invitación nueva a {$usuario->email}."
                    : 'Cuenta reactivada como pendiente, pero no se pudo enviar el correo. Usa «Reenviar invitación».'
            );
        }

        $usuario->estado = 'activo';
        $usuario->save();

        RegistroBitacora::registrar('Usuario reactivado', $usuario->email);

        $mensaje = "Cuenta de {$usuario->email} reactivada.";
        if ($usuario->estaVencido()) {
            $mensaje .= ' Atención: su vigencia ya terminó; edita la fecha de vencimiento para que pueda entrar.';
        }

        return back()->with('ok', $mensaje);
    }

    public function reenviar(Request $request, User $usuario)
    {
        if ($usuario->estado !== 'pendiente') {
            return redirect()->route('usuarios.index')
                ->with('aviso', 'Solo se puede reenviar la invitación a cuentas pendientes.');
        }

        $enviado = Invitaciones::enviar($usuario);
        RegistroBitacora::registrar(
            'Invitación reenviada',
            $usuario->email,
            $enviado ? RegistroBitacora::EXITO : RegistroBitacora::ERROR
        );

        if ($enviado) {
            return redirect()->route('usuarios.index')->with('ok', $this->mensajeEnvio($usuario, 'Invitación reenviada.'));
        }

        return redirect()->route('usuarios.index')
            ->with('aviso', 'No se pudo enviar el correo. Revisa la configuración de correo e inténtalo de nuevo.');
    }

    private function quedaOtroAdminActivo(User $usuario): bool
    {
        return User::where('role', 'admin')
            ->where('estado', 'activo')
            ->where('id', '!=', $usuario->id)
            ->exists();
    }

    private function mensajeEnvio(User $usuario, string $inicio): string
    {
        $mensaje = "{$inicio} Se envió la invitación a {$usuario->email}; vence en " . Invitaciones::HORAS_VIGENCIA . ' horas.';

        if (config('mail.default') === 'log') {
            $mensaje .= ' (Modo desarrollo: el correo quedó en storage/logs/laravel.log.)';
        }

        return $mensaje;
    }
}
