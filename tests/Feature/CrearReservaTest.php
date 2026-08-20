<?php

use App\Exceptions\FueraDePlazoException;
use App\Exceptions\HorarioNoDisponibleException;
use App\Exceptions\SinDisponibilidadException;
use App\Models\Reserva;
use App\Services\Reservas\ReservaService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse(LUNES.' 12:00'));
    crearLocalDemo();
    $this->service = app(ReservaService::class);
    $this->user = usuario();
});

function reservar(int $personas, string $fecha = MARTES, string $hora = '20:00'): Reserva
{
    return test()->service->crear(test()->user, CarbonImmutable::parse($fecha), $hora, $personas);
}

it('asigna la primera ubicacion y la mesa mas ajustada', function () {
    $reserva = reservar(personas: 2);

    expect($reserva->ubicacion->nombre)->toBe('A')
        ->and($reserva->mesas->pluck('numero')->all())->toBe([1])
        ->and($reserva->fecha_servicio->toDateString())->toBe(MARTES)
        ->and($reserva->ends_at->format('H:i'))->toBe('22:00');
});

it('une dos mesas cuando ninguna sola alcanza', function () {
    $reserva = reservar(personas: 6);

    expect($reserva->ubicacion->nombre)->toBe('A')
        ->and($reserva->mesas->pluck('numero')->all())->toBe([1, 3]);
});

it('une tres mesas para agotar la capacidad de la ubicacion', function () {
    $reserva = reservar(personas: 8);

    expect($reserva->ubicacion->nombre)->toBe('A')
        ->and($reserva->mesas)->toHaveCount(3)
        ->and($reserva->mesas->pluck('numero')->all())->toBe([1, 2, 3]);
});

it('pasa a la ubicacion siguiente cuando la primera no da', function () {
    // A suma 8 asientos como maximo: 9 personas obligan a probar B
    $reserva = reservar(personas: 9);

    expect($reserva->ubicacion->nombre)->toBe('B')
        ->and($reserva->mesas->pluck('numero')->all())->toBe([4, 6]);
});

it('rechaza cuando ninguna ubicacion tiene capacidad y no deja nada a medio crear', function () {
    expect(fn () => reservar(personas: 17))->toThrow(SinDisponibilidadException::class);

    expect(Reserva::count())->toBe(0)
        ->and(DB::table('mesa_reserva')->count())->toBe(0);
});

it('no asigna la misma mesa a dos reservas que se pisan', function () {
    $primera = reservar(personas: 2, hora: '20:00');
    $segunda = reservar(personas: 2, hora: '21:00'); // se solapa con la anterior

    expect($primera->mesas->pluck('numero')->all())->toBe([1])
        ->and($segunda->mesas->pluck('numero')->all())->toBe([2]);
});

it('reutiliza la mesa apenas termina la reserva anterior', function () {
    $primera = reservar(personas: 2, hora: '20:00'); // 20:00 a 22:00
    $segunda = reservar(personas: 2, hora: '22:00'); // 22:00 a 24:00

    // los intervalos se tocan en el borde pero no se pisan: la mesa ya quedo libre
    expect($primera->mesas->pluck('numero')->all())->toBe([1])
        ->and($segunda->mesas->pluck('numero')->all())->toBe([1]);
});

it('persiste la reserva del sabado a la noche dentro del servicio del sabado', function () {
    $reserva = reservar(personas: 4, fecha: SABADO, hora: '23:30');

    expect($reserva->starts_at->format('Y-m-d H:i'))->toBe(SABADO.' 23:30')
        ->and($reserva->ends_at->format('Y-m-d H:i'))->toBe('2026-08-30 01:30')
        ->and($reserva->fecha_servicio->toDateString())->toBe(SABADO);

    // y sigue compitiendo por la mesa con otra reserva de esa misma madrugada
    $otra = reservar(personas: 4, fecha: SABADO, hora: '00:00');

    expect($otra->mesas->pluck('numero')->all())->not->toBe($reserva->mesas->pluck('numero')->all());
});

it('rechaza horarios fuera de la ventana del dia', function () {
    expect(fn () => reservar(personas: 2, hora: '09:00'))->toThrow(HorarioNoDisponibleException::class);
    expect(fn () => reservar(personas: 2, hora: '23:00'))->toThrow(HorarioNoDisponibleException::class);
    expect(fn () => reservar(personas: 2, fecha: DOMINGO, hora: '17:00'))->toThrow(HorarioNoDisponibleException::class);

    expect(Reserva::count())->toBe(0);
});

it('rechaza reservas pedidas sobre la hora', function () {
    $this->travelTo(CarbonImmutable::parse(MARTES.' 19:55'));

    expect(fn () => reservar(personas: 2, hora: '20:00'))->toThrow(FueraDePlazoException::class);
});

it('deja la reserva asociada al usuario que la cargo', function () {
    expect(reservar(personas: 2)->user_id)->toBe($this->user->id);
});
