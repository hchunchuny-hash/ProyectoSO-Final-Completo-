<?php

namespace App\Services;

use InvalidArgumentException;

class PlanificadorCPU
{
    public const ALGORITMOS = [
        'fcfs'      => 'FCFS',
        'sjf'       => 'SJF (no expropiativo)',
        'srtf'      => 'SRTF',
        'rr'        => 'Round Robin',
        'prioridad' => 'Prioridades',
    ];

    /** Simula un algoritmo y devuelve el Gantt, los tiempos y los promedios. */
    public static function simular(string $algoritmo, array $procesos, int $quantum = 2): array
    {
        $procesos = self::normalizar($procesos);

        $segmentos = match ($algoritmo) {
            'fcfs', 'sjf', 'prioridad' => self::noExpropiativo($procesos, $algoritmo),
            'srtf'                     => self::srtf($procesos),
            'rr'                       => self::roundRobin($procesos, max(1, $quantum)),
            default                    => throw new InvalidArgumentException('Algoritmo no válido.'),
        };

        return self::resultado($procesos, $segmentos);
    }

    /** Corre los cinco algoritmos con los mismos procesos para compararlos. */
    public static function compararTodos(array $procesos, int $quantum = 2): array
    {
        $tabla = [];

        foreach (self::ALGORITMOS as $clave => $nombre) {
            $r = self::simular($clave, $procesos, $quantum);
            $tabla[$clave] = [
                'nombre'  => $nombre . ($clave === 'rr' ? " (q={$quantum})" : ''),
                'espera'  => $r['prom_espera'],
                'retorno' => $r['prom_retorno'],
            ];
        }

        return $tabla;
    }

    /** Pone nombre (P1, P2...) y orden de ingreso a cada proceso. */
    private static function normalizar(array $procesos): array
    {
        $salida = [];

        foreach (array_values($procesos) as $i => $p) {
            $salida[] = [
                'pid'       => 'P' . ($i + 1),
                'orden'     => $i,
                'llegada'   => (int) $p['llegada'],
                'rafaga'    => (int) $p['rafaga'],
                'prioridad' => (int) ($p['prioridad'] ?? 0),
            ];
        }

        return $salida;
    }

    /** Criterio de desempate: primero el valor del algoritmo, luego la llegada, luego el orden. */
    private static function clave(array $p, string $criterio): array
    {
        return match ($criterio) {
            'fcfs'      => [$p['llegada'], $p['orden']],
            'sjf'       => [$p['rafaga'], $p['llegada'], $p['orden']],
            'prioridad' => [$p['prioridad'], $p['llegada'], $p['orden']],
            'srtf'      => [$p['restante'], $p['llegada'], $p['orden']],
        };
    }

    private static function elegir(array $listos, string $criterio): array
    {
        usort($listos, fn ($a, $b) => self::clave($a, $criterio) <=> self::clave($b, $criterio));

        return $listos[0];
    }

    /** FCFS, SJF y Prioridades: el proceso elegido corre hasta terminar. */
    private static function noExpropiativo(array $procesos, string $criterio): array
    {
        $segmentos = [];
        $tiempo = 0;
        $pendientes = array_column($procesos, null, 'pid');

        while ($pendientes) {
            $listos = array_filter($pendientes, fn ($p) => $p['llegada'] <= $tiempo);

            if (!$listos) {
                // Nadie ha llegado: la CPU queda ociosa hasta la próxima llegada
                $proximo = min(array_column($pendientes, 'llegada'));
                self::agregarSegmento($segmentos, null, $tiempo, $proximo);
                $tiempo = $proximo;
                continue;
            }

            $p = self::elegir($listos, $criterio);
            self::agregarSegmento($segmentos, $p['pid'], $tiempo, $tiempo + $p['rafaga']);
            $tiempo += $p['rafaga'];
            unset($pendientes[$p['pid']]);
        }

        return $segmentos;
    }

    /** SRTF: en cada unidad de tiempo corre el que tenga menos tiempo restante. */
    private static function srtf(array $procesos): array
    {
        $restante = array_column($procesos, 'rafaga', 'pid');
        $segmentos = [];
        $tiempo = 0;
        $faltan = count($procesos);

        while ($faltan > 0) {
            $listos = [];

            foreach ($procesos as $p) {
                if ($restante[$p['pid']] > 0 && $p['llegada'] <= $tiempo) {
                    $p['restante'] = $restante[$p['pid']];
                    $listos[] = $p;
                }
            }

            $pid = $listos ? self::elegir($listos, 'srtf')['pid'] : null;
            self::agregarSegmento($segmentos, $pid, $tiempo, $tiempo + 1);

            if ($pid !== null) {
                $restante[$pid]--;
                if ($restante[$pid] === 0) {
                    $faltan--;
                }
            }

            $tiempo++;
        }

        return $segmentos;
    }

    /** Round Robin: cada proceso corre como máximo un quantum y vuelve al final de la cola. */
    private static function roundRobin(array $procesos, int $quantum): array
    {
        usort($procesos, fn ($a, $b) => [$a['llegada'], $a['orden']] <=> [$b['llegada'], $b['orden']]);

        $restante = array_column($procesos, 'rafaga', 'pid');
        $n = count($procesos);
        $cola = [];
        $siguiente = 0;
        $tiempo = 0;
        $terminados = 0;
        $segmentos = [];

        // Mete a la cola los procesos que ya llegaron
        $admitir = function () use (&$cola, &$siguiente, &$tiempo, $procesos, $n) {
            while ($siguiente < $n && $procesos[$siguiente]['llegada'] <= $tiempo) {
                $cola[] = $procesos[$siguiente]['pid'];
                $siguiente++;
            }
        };

        $admitir();

        while ($terminados < $n) {
            if (!$cola) {
                $proximo = $procesos[$siguiente]['llegada'];
                self::agregarSegmento($segmentos, null, $tiempo, $proximo);
                $tiempo = $proximo;
                $admitir();
                continue;
            }

            $pid = array_shift($cola);
            $ejecuta = min($quantum, $restante[$pid]);
            self::agregarSegmento($segmentos, $pid, $tiempo, $tiempo + $ejecuta);
            $tiempo += $ejecuta;
            $restante[$pid] -= $ejecuta;

            // Los que llegaron mientras corría entran antes que el que vuelve a la cola
            $admitir();

            if ($restante[$pid] > 0) {
                $cola[] = $pid;
            } else {
                $terminados++;
            }
        }

        return $segmentos;
    }

    /** Agrega un bloque al Gantt; si es el mismo proceso seguido, lo une al anterior. */
    private static function agregarSegmento(array &$segmentos, ?string $pid, int $inicio, int $fin): void
    {
        $ultimo = count($segmentos) - 1;

        if ($ultimo >= 0 && $segmentos[$ultimo]['pid'] === $pid && $segmentos[$ultimo]['fin'] === $inicio) {
            $segmentos[$ultimo]['fin'] = $fin;
            return;
        }

        $segmentos[] = ['pid' => $pid, 'inicio' => $inicio, 'fin' => $fin];
    }

    /** Retorno = fin - llegada. Espera = retorno - ráfaga. */
    private static function resultado(array $procesos, array $segmentos): array
    {
        $fin = [];

        foreach ($segmentos as $s) {
            if ($s['pid'] !== null) {
                $fin[$s['pid']] = $s['fin'];
            }
        }

        $filas = [];
        $sumaEspera = 0;
        $sumaRetorno = 0;

        foreach ($procesos as $p) {
            $final = $fin[$p['pid']];
            $retorno = $final - $p['llegada'];
            $espera = $retorno - $p['rafaga'];

            $filas[] = [
                'pid'       => $p['pid'],
                'llegada'   => $p['llegada'],
                'rafaga'    => $p['rafaga'],
                'prioridad' => $p['prioridad'],
                'fin'       => $final,
                'retorno'   => $retorno,
                'espera'    => $espera,
            ];

            $sumaEspera += $espera;
            $sumaRetorno += $retorno;
        }

        $n = count($procesos);
        $ultimo = end($segmentos);

        return [
            'segmentos'    => $segmentos,
            'filas'        => $filas,
            'tiempo_total' => $ultimo['fin'],
            'prom_espera'  => round($sumaEspera / $n, 2),
            'prom_retorno' => round($sumaRetorno / $n, 2),
        ];
    }
}
