<?php

namespace App\Services;

/**
 * M4 · Interbloqueos.
 *
 * - Algoritmo del banquero (estado seguro, secuencia segura y paso a paso).
 * - Evaluación de una solicitud de recursos (¿se concede o no?).
 * - Detección de interbloqueos.
 * - Grafo de asignación de recursos con detección de ciclos.
 * - Estrategias de recuperación (terminación y apropiación).
 *
 * Solo hace cálculos con matrices: no ejecuta comandos del sistema.
 */
class Banquero
{
    /** Necesidad = Máximo - Asignación. */
    public function necesidad(array $maximo, array $asignacion): array
    {
        $necesidad = [];
        foreach ($maximo as $i => $fila) {
            foreach ($fila as $j => $valor) {
                $necesidad[$i][$j] = $valor - $asignacion[$i][$j];
            }
        }

        return $necesidad;
    }

    /**
     * Algoritmo del banquero: busca una secuencia segura.
     *
     * @return array{seguro: bool, secuencia: array, pasos: array, necesidad: array, restantes: array, trabajo_final: array}
     */
    public function ejecutar(array $asignacion, array $maximo, array $disponible): array
    {
        $necesidad = $this->necesidad($maximo, $asignacion);
        $resultado = $this->buscarSecuencia($asignacion, $necesidad, $disponible);

        // Procesos que no pudieron terminar (si el estado es inseguro)
        $restantes = [];
        foreach (array_keys($asignacion) as $i) {
            if (! in_array($i, $resultado['secuencia'], true)) {
                $restantes[] = ['proceso' => $i, 'necesidad' => $necesidad[$i]];
            }
        }

        return [
            'seguro'        => $resultado['terminaron'] === count($asignacion),
            'secuencia'     => $resultado['secuencia'],
            'pasos'         => $resultado['pasos'],
            'necesidad'     => $necesidad,
            'restantes'     => $restantes,
            'trabajo_final' => $resultado['trabajo'],
        ];
    }

    /**
     * Evaluación de una solicitud de recursos de un proceso.
     * Se concede solo si: 1) no excede su necesidad, 2) hay recursos disponibles
     * y 3) el estado que resulta de concederla sigue siendo seguro.
     */
    public function solicitud(array $asignacion, array $maximo, array $disponible, int $proceso, array $solicitud): array
    {
        $nombre = 'P' . ($proceso + 1);
        $necesidad = $this->necesidad($maximo, $asignacion);
        $verificaciones = [];

        // 1) Solicitud <= Necesidad
        $dentroDeNecesidad = $this->cabe($solicitud, $necesidad[$proceso]);
        $verificaciones[] = [
            'texto'   => "Solicitud ≤ Necesidad de {$nombre}",
            'detalle' => $this->vector($solicitud) . ' ≤ ' . $this->vector($necesidad[$proceso]),
            'cumple'  => $dentroDeNecesidad,
        ];
        if (! $dentroDeNecesidad) {
            return $this->denegada('El proceso pide más de lo que declaró necesitar (excede su máximo).', $verificaciones);
        }

        // 2) Solicitud <= Disponible
        $hayDisponible = $this->cabe($solicitud, $disponible);
        $verificaciones[] = [
            'texto'   => 'Solicitud ≤ Disponible',
            'detalle' => $this->vector($solicitud) . ' ≤ ' . $this->vector($disponible),
            'cumple'  => $hayDisponible,
        ];
        if (! $hayDisponible) {
            return $this->denegada('No hay recursos suficientes: el proceso debe esperar.', $verificaciones);
        }

        // 3) Se simula la asignación y se comprueba que el estado siga siendo seguro
        $nuevaAsignacion = $asignacion;
        $nuevoDisponible = $disponible;
        foreach ($solicitud as $j => $valor) {
            $nuevaAsignacion[$proceso][$j] += $valor;
            $nuevoDisponible[$j] -= $valor;
        }
        $nuevaNecesidad = $this->necesidad($maximo, $nuevaAsignacion);
        $busqueda = $this->buscarSecuencia($nuevaAsignacion, $nuevaNecesidad, $nuevoDisponible);
        $seguro = $busqueda['terminaron'] === count($asignacion);

        $secuenciaTexto = implode(' → ', array_map(fn ($i) => 'P' . ($i + 1), $busqueda['secuencia']));
        $verificaciones[] = [
            'texto'   => 'El estado que resulta de concederla es seguro',
            'detalle' => $seguro ? "secuencia segura: {$secuenciaTexto}" : 'no existe una secuencia segura',
            'cumple'  => $seguro,
        ];

        return [
            'concedida'      => $seguro,
            'motivo'         => $seguro
                ? 'Se concede: el sistema queda en estado seguro.'
                : 'Se niega: concederla dejaría al sistema en estado inseguro.',
            'verificaciones' => $verificaciones,
            'estado'         => [
                'asignacion' => $nuevaAsignacion,
                'disponible' => $nuevoDisponible,
                'necesidad'  => $nuevaNecesidad,
            ],
            'secuencia'      => $seguro ? $busqueda['secuencia'] : [],
            'pasos'          => $busqueda['pasos'],
        ];
    }

    /**
     * Detección de interbloqueos: los procesos que no pueden terminar
     * con los recursos disponibles quedan bloqueados entre sí.
     * Aquí la solicitud pendiente de cada proceso es su necesidad.
     *
     * @return array{interbloqueo: bool, bloqueados: array, pasos: array}
     */
    public function detectar(array $asignacion, array $maximo, array $disponible): array
    {
        $necesidad = $this->necesidad($maximo, $asignacion);
        $resultado = $this->buscarSecuencia($asignacion, $necesidad, $disponible);

        $bloqueados = [];
        foreach (array_keys($asignacion) as $i) {
            if (! in_array($i, $resultado['secuencia'], true)) {
                $bloqueados[] = $i;
            }
        }

        return [
            'interbloqueo' => count($bloqueados) > 0,
            'bloqueados'   => $bloqueados,
            'pasos'        => $resultado['pasos'],
        ];
    }

    /**
     * Análisis completo: detección, grafo de asignación con sus ciclos y,
     * si hay interbloqueo, las estrategias de recuperación.
     */
    public function analizar(array $asignacion, array $maximo, array $disponible): array
    {
        $deteccion = $this->detectar($asignacion, $maximo, $disponible);
        $necesidad = $this->necesidad($maximo, $asignacion);

        return [
            'interbloqueo' => $deteccion['interbloqueo'],
            'bloqueados'   => $deteccion['bloqueados'],
            'pasos'        => $deteccion['pasos'],
            'grafo'        => $this->grafo($asignacion, $necesidad, $disponible, $deteccion['bloqueados']),
            'recuperacion' => $deteccion['interbloqueo']
                ? $this->recuperacion($asignacion, $maximo, $disponible, $deteccion['bloqueados'])
                : ['terminacion' => [], 'apropiacion' => []],
        ];
    }

    /**
     * Grafo de asignación de recursos.
     *  - Arista R → P (asignación): el proceso P tiene instancias del recurso R.
     *  - Arista P → R (solicitud): el proceso P necesita más instancias de R de
     *    las que hay disponibles, así que queda esperando.
     * Devuelve también los ciclos (R → P → R ...) y marca las aristas que participan.
     */
    private function grafo(array $asignacion, array $necesidad, array $disponible, array $bloqueados): array
    {
        $procesos = count($asignacion);
        $recursos = count($disponible);

        $nodos = [];
        for ($i = 0; $i < $procesos; $i++) {
            $nodos[] = ['id' => 'P' . ($i + 1), 'tipo' => 'proceso', 'bloqueado' => in_array($i, $bloqueados, true)];
        }
        for ($j = 0; $j < $recursos; $j++) {
            $nodos[] = ['id' => 'R' . ($j + 1), 'tipo' => 'recurso', 'bloqueado' => false];
        }

        $aristas = [];
        for ($i = 0; $i < $procesos; $i++) {
            for ($j = 0; $j < $recursos; $j++) {
                if ($asignacion[$i][$j] > 0) {
                    $aristas[] = [
                        'de' => 'R' . ($j + 1), 'a' => 'P' . ($i + 1),
                        'tipo' => 'asignacion', 'cantidad' => $asignacion[$i][$j], 'ciclo' => false,
                    ];
                }
                if ($necesidad[$i][$j] > $disponible[$j]) {
                    $aristas[] = [
                        'de' => 'P' . ($i + 1), 'a' => 'R' . ($j + 1),
                        'tipo' => 'solicitud', 'cantidad' => $necesidad[$i][$j], 'ciclo' => false,
                    ];
                }
            }
        }

        // Lista de adyacencia y búsqueda de ciclos
        $ids = array_column($nodos, 'id');
        $posicion = array_flip($ids);
        $adyacencia = array_fill_keys($ids, []);
        foreach ($aristas as $arista) {
            $adyacencia[$arista['de']][] = $arista['a'];
        }

        $ciclos = [];
        $presupuesto = 20000;
        foreach ($ids as $inicio) {
            $this->buscarCiclos($inicio, $inicio, [$inicio], $adyacencia, $posicion, $ciclos, $presupuesto);
        }

        // Se marcan las aristas que forman parte de algún ciclo
        $enCiclo = [];
        foreach ($ciclos as $ciclo) {
            $n = count($ciclo);
            for ($k = 0; $k < $n; $k++) {
                $enCiclo[$ciclo[$k] . '>' . $ciclo[($k + 1) % $n]] = true;
            }
        }
        foreach ($aristas as $k => $arista) {
            $aristas[$k]['ciclo'] = isset($enCiclo[$arista['de'] . '>' . $arista['a']]);
        }

        return ['nodos' => $nodos, 'aristas' => $aristas, 'ciclos' => $ciclos];
    }

    /**
     * Búsqueda en profundidad de ciclos que empiezan y terminan en $inicio.
     * Solo se visitan nodos que van después de $inicio en el orden, para no repetir ciclos.
     * Se limita la cantidad de ciclos y de pasos para no saturar el servidor.
     */
    private function buscarCiclos(
        string $inicio,
        string $actual,
        array $camino,
        array $adyacencia,
        array $posicion,
        array &$ciclos,
        int &$presupuesto
    ): void {
        if (count($ciclos) >= 12 || $presupuesto <= 0) {
            return;
        }
        $presupuesto--;

        foreach ($adyacencia[$actual] as $siguiente) {
            if ($siguiente === $inicio) {
                $ciclos[] = $camino;
                continue;
            }
            if ($posicion[$siguiente] <= $posicion[$inicio] || in_array($siguiente, $camino, true)) {
                continue;
            }
            $this->buscarCiclos($inicio, $siguiente, array_merge($camino, [$siguiente]), $adyacencia, $posicion, $ciclos, $presupuesto);
        }
    }

    /**
     * Estrategias de recuperación de un interbloqueo:
     *  - Terminación: se elimina un proceso bloqueado y se liberan sus recursos.
     *  - Apropiación: se le quitan a un proceso bloqueado las instancias que tiene de un recurso.
     * Cada opción se simula para saber si realmente resuelve el interbloqueo.
     */
    private function recuperacion(array $asignacion, array $maximo, array $disponible, array $bloqueados): array
    {
        $recursos = count($disponible);
        $terminacion = [];
        $apropiacion = [];

        foreach ($bloqueados as $i) {
            $a = $asignacion;
            $m = $maximo;
            $d = $disponible;
            foreach ($asignacion[$i] as $j => $valor) {
                $d[$j] += $valor;
            }
            $a[$i] = array_fill(0, $recursos, 0);
            $m[$i] = array_fill(0, $recursos, 0);

            $despues = $this->detectar($a, $m, $d);
            $terminacion[] = [
                'proceso'              => $i,
                'libera'               => $asignacion[$i],
                'costo'                => array_sum($asignacion[$i]),
                'resuelve'             => ! $despues['interbloqueo'],
                'bloqueados_restantes' => $despues['bloqueados'],
            ];
        }
        usort($terminacion, fn ($x, $y) => [! $x['resuelve'], $x['costo'], $x['proceso']] <=> [! $y['resuelve'], $y['costo'], $y['proceso']]);

        foreach ($bloqueados as $i) {
            foreach ($asignacion[$i] as $j => $cantidad) {
                if ($cantidad <= 0) {
                    continue;
                }
                $a = $asignacion;
                $d = $disponible;
                $a[$i][$j] = 0;
                $d[$j] += $cantidad;

                $despues = $this->detectar($a, $maximo, $d);
                $apropiacion[] = [
                    'proceso'              => $i,
                    'recurso'              => $j,
                    'cantidad'             => $cantidad,
                    'resuelve'             => ! $despues['interbloqueo'],
                    'bloqueados_restantes' => $despues['bloqueados'],
                ];
            }
        }
        usort($apropiacion, fn ($x, $y) => [! $x['resuelve'], $x['cantidad'], $x['proceso'], $x['recurso']] <=> [! $y['resuelve'], $y['cantidad'], $y['proceso'], $y['recurso']]);

        return ['terminacion' => $terminacion, 'apropiacion' => array_slice($apropiacion, 0, 8)];
    }

    /** Recorre los procesos buscando uno cuya necesidad quepa en lo disponible. */
    private function buscarSecuencia(array $asignacion, array $necesidad, array $disponible): array
    {
        $trabajo = $disponible;
        $termino = array_fill(0, count($asignacion), false);
        $secuencia = [];
        $pasos = [];

        do {
            $avanzo = false;
            foreach ($asignacion as $i => $filaAsignada) {
                if ($termino[$i] || ! $this->cabe($necesidad[$i], $trabajo)) {
                    continue;
                }

                $antes = $trabajo;
                foreach ($filaAsignada as $j => $valor) {
                    $trabajo[$j] += $valor;
                }
                $termino[$i] = true;
                $secuencia[] = $i;
                $pasos[] = [
                    'proceso'   => $i,
                    'necesidad' => $necesidad[$i],
                    'antes'     => $antes,
                    'trabajo'   => $trabajo,
                ];
                $avanzo = true;
            }
        } while ($avanzo);

        return [
            'secuencia'  => $secuencia,
            'pasos'      => $pasos,
            'terminaron' => count($secuencia),
            'trabajo'    => $trabajo,
        ];
    }

    /** ¿Cada valor de $necesidad es menor o igual al de $trabajo? */
    private function cabe(array $necesidad, array $trabajo): bool
    {
        foreach ($necesidad as $j => $valor) {
            if ($valor > $trabajo[$j]) {
                return false;
            }
        }

        return true;
    }

    private function vector(array $valores): string
    {
        return '(' . implode(', ', $valores) . ')';
    }

    private function denegada(string $motivo, array $verificaciones): array
    {
        return [
            'concedida'      => false,
            'motivo'         => $motivo,
            'verificaciones' => $verificaciones,
            'estado'         => null,
            'secuencia'      => [],
            'pasos'          => [],
        ];
    }
}
