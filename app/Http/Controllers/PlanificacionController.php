<?php

namespace App\Http\Controllers;

use App\Models\Simulacion;
use App\Services\PlanificadorCPU;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanificacionController extends Controller
{
    public function index()
    {
        return view('planificacion.index', $this->datosBase());
    }

    public function simular(Request $request)
    {
        $datos = $request->validate([
            'algoritmo'            => ['required', Rule::in(array_keys(PlanificadorCPU::ALGORITMOS))],
            'quantum'              => ['nullable', 'integer', 'between:1,20'],
            'modo'                 => ['required', Rule::in(['simular', 'comparar'])],
            'procesos'             => ['required', 'array', 'min:1', 'max:20'],
            'procesos.*.llegada'   => ['required', 'integer', 'between:0,100'],
            'procesos.*.rafaga'    => ['required', 'integer', 'between:1,100'],
            'procesos.*.prioridad' => ['required', 'integer', 'between:0,20'],
        ]);

        $quantum = (int) ($datos['quantum'] ?? 2);
        $procesos = array_values($datos['procesos']);

        $resultado = PlanificadorCPU::simular($datos['algoritmo'], $procesos, $quantum);

        $comparacion = $datos['modo'] === 'comparar'
            ? PlanificadorCPU::compararTodos($procesos, $quantum)
            : null;

        $guardada = null;
        if ($request->boolean('guardar')) {
            $guardada = Simulacion::create([
                'user_id'      => $request->user()->id,
                'algoritmo'    => $datos['algoritmo'],
                'quantum'      => $datos['algoritmo'] === 'rr' ? $quantum : null,
                'procesos'     => $procesos,
                'resultado'    => $resultado,
                'prom_espera'  => $resultado['prom_espera'],
                'prom_retorno' => $resultado['prom_retorno'],
            ]);
        }

        return view('planificacion.index', array_merge($this->datosBase(), [
            'procesosForm' => $procesos,
            'algoritmo'    => $datos['algoritmo'],
            'quantum'      => $quantum,
            'resultado'    => $resultado,
            'comparacion'  => $comparacion,
            'guardada'     => $guardada,
        ]));
    }

    public function ver(Simulacion $simulacion)
    {
        return view('planificacion.index', array_merge($this->datosBase(), [
            'procesosForm' => $simulacion->procesos,
            'algoritmo'    => $simulacion->algoritmo,
            'quantum'      => $simulacion->quantum ?? 2,
            'resultado'    => $simulacion->resultado,
            'guardada'     => $simulacion,
        ]));
    }

    private function datosBase(): array
    {
        return [
            'algoritmos'   => PlanificadorCPU::ALGORITMOS,
            'historial'    => Simulacion::latest('id')->limit(10)->get(),
            'procesosForm' => [
                ['llegada' => 0, 'rafaga' => 5, 'prioridad' => 3],
                ['llegada' => 1, 'rafaga' => 3, 'prioridad' => 4],
                ['llegada' => 2, 'rafaga' => 1, 'prioridad' => 1],
                ['llegada' => 3, 'rafaga' => 4, 'prioridad' => 2],
            ],
            'algoritmo'    => 'fcfs',
            'quantum'      => 2,
            'resultado'    => null,
            'comparacion'  => null,
            'guardada'     => null,
        ];
    }
}
