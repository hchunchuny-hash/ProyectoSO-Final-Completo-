<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RegistroBitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        return view('usuarios.index', compact('usuarios', 'texto', 'rol', 'estado'));
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

        return redirect()->route('usuarios.index')
            ->with('ok', "Cuenta creada para {$usuario->email}. Queda pendiente hasta que defina su contraseña.");
    }
}
