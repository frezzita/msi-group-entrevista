<?php

use App\Services\Reservas\AsignadorDeMesas;
use App\Services\Reservas\CombinacionDeMesas;

beforeEach(function () {
    $this->asignador = new AsignadorDeMesas;
});

it('prefiere una sola mesa antes que unir dos', function () {
    $combinacion = $this->asignador->resolver(
        collect([mesaFalsa(1, 2), mesaFalsa(2, 2), mesaFalsa(3, 4)]),
        personas: 4
    );

    expect($combinacion->numeros())->toBe([3]);
});

it('elige la mesa mas ajustada y no la mas grande', function () {
    $combinacion = $this->asignador->resolver(
        collect([mesaFalsa(1, 10), mesaFalsa(2, 6), mesaFalsa(3, 8)]),
        personas: 6
    );

    expect($combinacion->numeros())->toBe([2])
        ->and($combinacion->desperdicio(6))->toBe(0);
});

it('une dos mesas cuando ninguna sola alcanza', function () {
    $combinacion = $this->asignador->resolver(
        collect([mesaFalsa(1, 2), mesaFalsa(2, 2), mesaFalsa(3, 4)]),
        personas: 6
    );

    expect($combinacion->cantidad())->toBe(2)
        ->and($combinacion->numeros())->toBe([1, 3])
        ->and($combinacion->capacidadEfectiva)->toBe(6);
});

it('une tres mesas cuando dos no alcanzan', function () {
    $combinacion = $this->asignador->resolver(
        collect([mesaFalsa(1, 2), mesaFalsa(2, 2), mesaFalsa(3, 4)]),
        personas: 8
    );

    expect($combinacion->cantidad())->toBe(3)
        ->and($combinacion->numeros())->toBe([1, 2, 3]);
});

it('nunca une mas de tres mesas', function () {
    // cuatro mesas de 2 alcanzarian para 8 personas, pero el tope son 3
    $combinacion = $this->asignador->resolver(
        collect([mesaFalsa(1, 2), mesaFalsa(2, 2), mesaFalsa(3, 2), mesaFalsa(4, 2)]),
        personas: 8
    );

    expect($combinacion)->toBeNull();
});

it('desempata por el menor numero de mesa', function () {
    // 1+4 y 2+3 dan la misma capacidad y el mismo desperdicio
    $combinacion = $this->asignador->resolver(
        collect([mesaFalsa(1, 2), mesaFalsa(2, 2), mesaFalsa(3, 4), mesaFalsa(4, 4)]),
        personas: 6
    );

    expect($combinacion->numeros())->toBe([1, 3]);
});

it('devuelve null cuando ninguna combinacion alcanza', function () {
    expect($this->asignador->resolver(collect([mesaFalsa(1, 2), mesaFalsa(2, 2)]), personas: 10))->toBeNull();
});

it('descuenta asientos por cada juntura cuando la perdida esta configurada', function () {
    config()->set('reservas.asientos_perdidos_por_union', 2);

    // dos mesas de 4: 8 asientos sumados, 6 utiles
    expect(CombinacionDeMesas::de([mesaFalsa(1, 4), mesaFalsa(2, 4)])->capacidadEfectiva)->toBe(6)
        // tres mesas de 4: dos junturas, 12 - 4
        ->and(CombinacionDeMesas::de([mesaFalsa(1, 4), mesaFalsa(2, 4), mesaFalsa(3, 4)])->capacidadEfectiva)->toBe(8)
        // una sola mesa no tiene junturas
        ->and(CombinacionDeMesas::de([mesaFalsa(1, 4)])->capacidadEfectiva)->toBe(4);

    // y con la perdida activa, dos mesas de 4 ya no alcanzan para 8 personas
    expect($this->asignador->resolver(collect([mesaFalsa(1, 4), mesaFalsa(2, 4)]), personas: 8))->toBeNull();
});

it('suma sin descontar nada con la configuracion por defecto', function () {
    expect(config('reservas.asientos_perdidos_por_union'))->toBe(0)
        ->and(CombinacionDeMesas::de([mesaFalsa(1, 4), mesaFalsa(2, 4)])->capacidadEfectiva)->toBe(8);
});
