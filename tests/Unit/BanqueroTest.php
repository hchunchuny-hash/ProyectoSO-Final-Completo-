<?php

namespace Tests\Unit;

use App\Services\Banquero;
use PHPUnit\Framework\TestCase;

class BanqueroTest extends TestCase
{
    /** Ejemplo clásico de 5 procesos y 3 recursos. */
    private function clasico(): array
    {
        return [
            [[0, 1, 0], [2, 0, 0], [3, 0, 2], [2, 1, 1], [0, 0, 2]],
            [[7, 5, 3], [3, 2, 2], [9, 0, 2], [2, 2, 2], [4, 3, 3]],
            [3, 3, 2],
        ];
    }

    /** Dos procesos esperando uno el recurso del otro, y un tercero que no depende de nadie. */
    private function conInterbloqueo(): array
    {
        return [
            [[1, 0, 0], [0, 1, 0], [0, 0, 1]],
            [[1, 1, 0], [1, 1, 0], [0, 0, 1]],
            [0, 0, 0],
        ];
    }

    public function test_estado_seguro_con_ejemplo_clasico(): void
    {
        [$a, $m, $d] = $this->clasico();
        $r = (new Banquero())->ejecutar($a, $m, $d);

        $this->assertTrue($r['seguro']);
        $this->assertSame([1, 3, 4, 0, 2], $r['secuencia']);
    }

    public function test_calcula_la_necesidad(): void
    {
        $n = (new Banquero())->necesidad([[3, 2, 2]], [[1, 0, 1]]);

        $this->assertSame([[2, 2, 1]], $n);
    }

    public function test_detecta_interbloqueo(): void
    {
        $r = (new Banquero())->detectar([[1, 0], [0, 1]], [[1, 1], [1, 1]], [0, 0]);

        $this->assertTrue($r['interbloqueo']);
        $this->assertSame([0, 1], $r['bloqueados']);
    }

    public function test_sin_interbloqueo_si_alcanzan_los_recursos(): void
    {
        $r = (new Banquero())->detectar([[1, 0, 1], [2, 1, 0]], [[3, 2, 2], [3, 1, 1]], [2, 1, 1]);

        $this->assertFalse($r['interbloqueo']);
    }

    public function test_el_paso_a_paso_muestra_el_trabajo_de_cada_proceso(): void
    {
        [$a, $m, $d] = $this->clasico();
        $r = (new Banquero())->ejecutar($a, $m, $d);

        $this->assertCount(5, $r['pasos']);
        $this->assertSame(1, $r['pasos'][0]['proceso']);
        $this->assertSame([3, 3, 2], $r['pasos'][0]['antes']);
        $this->assertSame([5, 3, 2], $r['pasos'][0]['trabajo']);
        $this->assertSame([10, 5, 7], $r['pasos'][4]['trabajo']);
    }

    public function test_estado_inseguro_informa_los_procesos_que_no_terminan(): void
    {
        [$a, $m, $d] = $this->conInterbloqueo();
        $r = (new Banquero())->ejecutar($a, $m, $d);

        $this->assertFalse($r['seguro']);
        $this->assertSame([2], $r['secuencia']);
        $this->assertSame([0, 1], array_column($r['restantes'], 'proceso'));
    }

    public function test_solicitud_concedida_si_el_estado_sigue_seguro(): void
    {
        [$a, $m, $d] = $this->clasico();
        $r = (new Banquero())->solicitud($a, $m, $d, 1, [1, 0, 2]);

        $this->assertTrue($r['concedida']);
        $this->assertCount(3, $r['verificaciones']);
        $this->assertSame([1, 3, 4, 0, 2], $r['secuencia']);
        $this->assertSame([2, 3, 0], $r['estado']['disponible']);
    }

    public function test_solicitud_que_excede_la_necesidad_se_deniega(): void
    {
        [$a, $m, $d] = $this->clasico();
        $r = (new Banquero())->solicitud($a, $m, $d, 0, [8, 0, 0]);

        $this->assertFalse($r['concedida']);
        $this->assertCount(1, $r['verificaciones']);
        $this->assertFalse($r['verificaciones'][0]['cumple']);
    }

    public function test_solicitud_mayor_que_lo_disponible_se_deniega(): void
    {
        [$a, $m, $d] = $this->clasico();
        $r = (new Banquero())->solicitud($a, $m, $d, 0, [4, 0, 0]);

        $this->assertFalse($r['concedida']);
        $this->assertCount(2, $r['verificaciones']);
        $this->assertTrue($r['verificaciones'][0]['cumple']);
        $this->assertFalse($r['verificaciones'][1]['cumple']);
    }

    public function test_solicitud_que_deja_el_estado_inseguro_se_deniega(): void
    {
        [$a, $m, $d] = $this->clasico();
        $r = (new Banquero())->solicitud($a, $m, $d, 4, [3, 3, 0]);

        $this->assertFalse($r['concedida']);
        $this->assertCount(3, $r['verificaciones']);
        $this->assertFalse($r['verificaciones'][2]['cumple']);
    }

    public function test_el_grafo_con_interbloqueo_tiene_un_ciclo(): void
    {
        [$a, $m, $d] = $this->conInterbloqueo();
        $r = (new Banquero())->analizar($a, $m, $d);

        $this->assertTrue($r['interbloqueo']);
        $this->assertSame([['P1', 'R2', 'P2', 'R1']], $r['grafo']['ciclos']);

        $enCiclo = array_filter($r['grafo']['aristas'], fn ($arista) => $arista['ciclo']);
        $this->assertCount(4, $enCiclo);
    }

    public function test_el_grafo_sin_interbloqueo_no_tiene_ciclos(): void
    {
        $r = (new Banquero())->analizar(
            [[1, 0, 1], [2, 1, 0], [1, 1, 0]],
            [[3, 2, 2], [3, 1, 1], [2, 2, 1]],
            [2, 1, 1]
        );

        $this->assertFalse($r['interbloqueo']);
        $this->assertSame([], $r['grafo']['ciclos']);
        $this->assertSame([], $r['recuperacion']['terminacion']);
    }

    public function test_la_recuperacion_propone_terminacion_y_apropiacion(): void
    {
        [$a, $m, $d] = $this->conInterbloqueo();
        $r = (new Banquero())->analizar($a, $m, $d)['recuperacion'];

        $this->assertSame(0, $r['terminacion'][0]['proceso']);
        $this->assertTrue($r['terminacion'][0]['resuelve']);

        $this->assertSame(0, $r['apropiacion'][0]['proceso']);
        $this->assertSame(0, $r['apropiacion'][0]['recurso']);
        $this->assertSame(1, $r['apropiacion'][0]['cantidad']);
        $this->assertTrue($r['apropiacion'][0]['resuelve']);
    }
}
