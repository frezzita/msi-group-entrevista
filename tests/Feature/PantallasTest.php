<?php

use App\Models\Reserva;
use App\Services\Reservas\ReservaService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse(LUNES.' 12:00'));
    crearLocalDemo();
    $this->actingAs($this->user = usuario());
    $this->service = app(ReservaService::class);
});

it('muestra el listado del dia con las mesas de cada reserva', function () {
    $this->service->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 6);

    $this->get(route('reservas.index', ['fecha' => MARTES]))
        ->assertOk()
        ->assertSeeTextInOrder(['A', '1 reserva(s)'])
        ->assertSee('20:00 a 22:00')
        ->assertSee('1, 3'); // mesas unidas
});

it('avisa cuando la fecha no tiene reservas', function () {
    $this->get(route('reservas.index', ['fecha' => MARTES]))
        ->assertOk()
        ->assertSee('No hay reservas para esta fecha');
});

it('ofrece en el formulario solo los horarios validos del dia elegido', function () {
    $this->get(route('reservas.create', ['fecha' => SABADO]))
        ->assertOk()
        ->assertSee('22:00')
        ->assertSee('00:00')   // el ultimo inicio del sabado ya es del domingo
        ->assertDontSee('21:00');
});

it('crea la reserva desde el formulario', function () {
    $this->post(route('reservas.store'), [
        'fecha' => MARTES,
        'hora' => '20:00',
        'cantidad_personas' => 9,
    ])->assertRedirect(route('reservas.index', ['fecha' => MARTES]))
        ->assertSessionHas('ok');

    $reserva = Reserva::firstOrFail();

    expect($reserva->ubicacion->nombre)->toBe('B')  // A no da para 9
        ->and($reserva->mesas)->toHaveCount(2);
});

it('devuelve el rechazo de negocio como mensaje y no como error 500', function () {
    $this->post(route('reservas.store'), [
        'fecha' => MARTES,
        'hora' => '23:00', // la reserva terminaria despues del cierre
        'cantidad_personas' => 2,
    ])->assertRedirect()->assertSessionHas('error');

    expect(Reserva::count())->toBe(0);
});

it('valida el formato de los datos del formulario', function () {
    $this->post(route('reservas.store'), ['fecha' => 'ayer', 'hora' => '25:99', 'cantidad_personas' => 0])
        ->assertSessionHasErrors(['fecha', 'hora', 'cantidad_personas']);
});

it('limita los POST a /reservas para evitar que se acaparen los locks de una zona', function () {
    $payload = ['fecha' => 'ayer', 'hora' => '25:99', 'cantidad_personas' => 0]; // invalido: no llega a tomar locks

    collect(range(1, 20))->each(
        fn () => $this->post(route('reservas.store'), $payload)->assertStatus(302)
    );

    $this->post(route('reservas.store'), $payload)->assertStatus(429);
});

it('cancela una reserva desde el listado', function () {
    $reserva = $this->service->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 2);

    $this->delete(route('reservas.destroy', $reserva))->assertSessionHas('ok');

    expect($reserva->fresh()->trashed())->toBeTrue();

    $this->get(route('reservas.index', ['fecha' => MARTES]))->assertSee('No hay reservas para esta fecha');
});

it('expone el listado del punto 4 como JSON', function () {
    $this->service->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 6);

    $this->getJson(route('api.reservas', ['fecha' => MARTES]))
        ->assertOk()
        ->assertJsonPath('fecha', MARTES)
        ->assertJsonPath('reservas.0.ubicacion', 'A')
        ->assertJsonPath('reservas.0.mesas', '1, 3');
});

it('expone los horarios validos de una fecha', function () {
    $this->getJson(route('api.horarios', ['fecha' => DOMINGO]))
        ->assertOk()
        ->assertJsonPath('horarios', ['12:00', '12:30', '13:00', '13:30', '14:00']);
});

it('muestra la ocupacion actual del salon', function () {
    // una reserva en curso en este mismo momento
    $ahora = CarbonImmutable::parse(MARTES.' 20:30');
    $this->travelTo($ahora->subHours(2));
    $this->service->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 2);
    $this->travelTo($ahora);

    $this->get(route('estado.index'))->assertOk()->assertSee('Mesa 1');

    $this->getJson(route('api.estado'))
        ->assertOk()
        ->assertJsonPath('ubicaciones.0.ubicacion', 'A')
        ->assertJsonPath('ubicaciones.0.mesas.0.ocupada', true)
        ->assertJsonPath('ubicaciones.0.mesas.0.hasta', '22:00')
        ->assertJsonPath('ubicaciones.0.mesas.1.ocupada', false)
        ->assertJsonPath('ubicaciones.0.libres', 2);
});
