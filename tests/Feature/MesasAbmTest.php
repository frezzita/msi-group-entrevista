<?php

use App\Models\Mesa;
use App\Models\Ubicacion;
use App\Services\Reservas\ReservaService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse(LUNES.' 12:00'));
    crearLocalDemo();
    $this->actingAs($this->user = usuario());
    $this->zonaA = Ubicacion::where('nombre', 'A')->firstOrFail();
});

it('lista las mesas agrupadas por ubicacion', function () {
    $this->get(route('mesas.index'))
        ->assertOk()
        ->assertSee('Ubicacion A')
        ->assertSee('Ubicacion D');
});

it('da de alta una mesa', function () {
    $this->post(route('mesas.store'), [
        'ubicacion_id' => $this->zonaA->id,
        'numero' => 99,
        'capacidad' => 5,
    ])->assertRedirect(route('mesas.index'));

    $this->assertDatabaseHas('mesas', ['ubicacion_id' => $this->zonaA->id, 'numero' => 99, 'capacidad' => 5]);
});

it('no permite repetir el numero de mesa dentro de una ubicacion', function () {
    $this->post(route('mesas.store'), [
        'ubicacion_id' => $this->zonaA->id,
        'numero' => 1, // ya existe en A
        'capacidad' => 4,
    ])->assertSessionHasErrors('numero');

    expect(Mesa::where('ubicacion_id', $this->zonaA->id)->where('numero', 1)->count())->toBe(1);
});

it('permite repetir el numero en otra ubicacion', function () {
    $zonaB = Ubicacion::where('nombre', 'B')->firstOrFail();

    $this->post(route('mesas.store'), ['ubicacion_id' => $zonaB->id, 'numero' => 1, 'capacidad' => 4])
        ->assertSessionHasNoErrors();
});

it('edita una mesa', function () {
    $mesa = Mesa::where('numero', 1)->firstOrFail();

    $this->put(route('mesas.update', $mesa), [
        'ubicacion_id' => $mesa->ubicacion_id,
        'numero' => 1,
        'capacidad' => 12,
    ])->assertRedirect(route('mesas.index'));

    expect($mesa->fresh()->capacidad)->toBe(12);
});

it('bloquea la baja de una mesa con reservas por venir', function () {
    $reserva = app(ReservaService::class)->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 2);
    $mesa = $reserva->mesas->first();

    $this->delete(route('mesas.destroy', $mesa))
        ->assertRedirect(route('mesas.index'))
        ->assertSessionHas('error');

    expect($mesa->fresh()->trashed())->toBeFalse();
});

it('da de baja logica una mesa sin reservas futuras', function () {
    $mesa = Mesa::where('numero', 11)->firstOrFail(); // zona D, sin reservas

    $this->delete(route('mesas.destroy', $mesa))->assertSessionHas('ok');

    expect($mesa->fresh()->trashed())->toBeTrue()
        // sigue en la base: el historico no se pierde
        ->and(Mesa::withTrashed()->find($mesa->id))->not->toBeNull();
});

it('deja el numero libre despues de una baja', function () {
    $mesa = Mesa::where('numero', 11)->firstOrFail();
    $this->delete(route('mesas.destroy', $mesa));

    $this->post(route('mesas.store'), [
        'ubicacion_id' => $mesa->ubicacion_id,
        'numero' => 11,
        'capacidad' => 3,
    ])->assertSessionHasNoErrors();
});

it('saca de la asignacion a las mesas dadas de baja', function () {
    // se dan de baja las tres mesas de A: una reserva ya no puede caer ahi
    $dadasDeBaja = Mesa::whereIn('numero', [1, 2, 3])->get();
    $dadasDeBaja->each->delete();

    $reserva = app(ReservaService::class)->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 2);

    // No se fija una ubicacion concreta: cual gana depende de la estrategia de
    // asignacion configurada, y lo que este test cubre es la exclusion de las bajas.
    expect($reserva->ubicacion->nombre)->not->toBe('A')
        ->and($reserva->mesas->pluck('id')->intersect($dadasDeBaja->pluck('id')))->toBeEmpty();
});
