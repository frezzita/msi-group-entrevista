<?php

use App\Events\ReservaCreada;
use App\Models\Reserva;
use App\Models\Ubicacion;
use App\Services\Reservas\DisponibilidadService;
use App\Services\Reservas\HorarioService;
use App\Services\Reservas\ReservaService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse(LUNES.' 12:00'));
    crearLocalDemo();
    $this->service = app(ReservaService::class);
    $this->disponibilidad = app(DisponibilidadService::class);
    $this->user = usuario();
    $this->zonaA = Ubicacion::where('nombre', 'A')->firstOrFail();
    $this->claveA = DisponibilidadService::clave(MARTES, $this->zonaA->id);
});

function reservarEn(int $personas, string $hora = '20:00'): Reserva
{
    return test()->service->crear(test()->user, CarbonImmutable::parse(MARTES), $hora, $personas);
}

it('cachea la ocupacion del dia por ubicacion', function () {
    expect(Cache::has($this->claveA))->toBeFalse();

    $this->disponibilidad->ocupacion(CarbonImmutable::parse(MARTES), $this->zonaA->id);

    expect(Cache::has($this->claveA))->toBeTrue();
});

it('lee la disponibilidad desde la cache y no de la base', function () {
    $reserva = reservarEn(personas: 2); // ocupa la mesa 1 de A
    $franja = app(HorarioService::class)->resolver(CarbonImmutable::parse(MARTES), '20:00');

    // se miente a la cache: "no hay nada ocupado"
    Cache::put($this->claveA, [], now()->addMinutes(10));

    // la lectura confia en la cache, que es justamente para lo que esta
    expect($this->disponibilidad->mesasLibres($this->zonaA, $franja))->toHaveCount(3);
});

it('invalida la clave de la ubicacion al confirmar una reserva', function () {
    $this->disponibilidad->ocupacion(CarbonImmutable::parse(MARTES), $this->zonaA->id);
    expect(Cache::has($this->claveA))->toBeTrue();

    reservarEn(personas: 2);

    expect(Cache::has($this->claveA))->toBeFalse();
});

it('no toca la cache de las ubicaciones que no se usaron', function () {
    $zonaB = Ubicacion::where('nombre', 'B')->firstOrFail();
    $claveB = DisponibilidadService::clave(MARTES, $zonaB->id);

    $this->disponibilidad->ocupacion(CarbonImmutable::parse(MARTES), $zonaB->id);

    reservarEn(personas: 2); // cae en A

    expect(Cache::has($claveB))->toBeTrue();
});

it('espera al commit para invalidar la cache', function () {
    $this->disponibilidad->ocupacion(CarbonImmutable::parse(MARTES), $this->zonaA->id);

    DB::transaction(function () {
        reservarEn(personas: 2);

        // todavia adentro de la transaccion: invalidar aca dejaria que un lector
        // concurrente repueble la clave con datos que aun no incluyen esta reserva
        expect(Cache::has($this->claveA))->toBeTrue();
    });

    expect(Cache::has($this->claveA))->toBeFalse();
});

it('no produce doble reserva aunque la cache este envenenada', function () {
    $primera = reservarEn(personas: 2);
    expect($primera->mesas->pluck('numero')->all())->toBe([1]);

    // cache con datos viejos: dice que la mesa 1 sigue libre
    Cache::put($this->claveA, [], now()->addMinutes(10));

    $segunda = reservarEn(personas: 2);

    // la confirmacion no se apoya en la cache: re-verifica contra la base
    expect($segunda->mesas->pluck('numero')->all())->toBe([2]);
});

it('emite el evento de reserva creada despues del commit', function () {
    Event::fake([ReservaCreada::class]);

    $reserva = reservarEn(personas: 2);

    Event::assertDispatched(ReservaCreada::class, fn (ReservaCreada $e) => $e->reserva->is($reserva));
});
