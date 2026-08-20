<?php

use App\Models\Ubicacion;
use App\Services\Reservas\ReservaService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Estos tests no pueden usar RefreshDatabase: ese trait envuelve el test en una
 * transaccion que nunca se commitea, y una segunda conexion no veria nada de lo que
 * pasa adentro. Se limpia la base a mano.
 */
beforeEach(function () {
    Artisan::call('migrate', ['--force' => true]);
    limpiarBase();

    $this->travelTo(CarbonImmutable::parse(LUNES.' 12:00'));
    crearLocalDemo();

    $this->service = app(ReservaService::class);
    $this->user = usuario();

    // Segunda conexion al mismo esquema: simula otro request en paralelo.
    config()->set('database.connections.mysql_paralela', config('database.connections.mysql'));
    $this->paralela = DB::connection('mysql_paralela');

    // Sin esto el test quedaria esperando el default de 50 segundos.
    DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
});

afterEach(function () {
    $this->paralela?->rollBack();
    DB::disconnect('mysql_paralela');
    limpiarBase();
});

function limpiarBase(): void
{
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    foreach (['mesa_reserva', 'reservas', 'mesas', 'ubicaciones', 'users'] as $tabla) {
        DB::table($tabla)->truncate();
    }
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
}

it('toma un lock de escritura sobre las mesas de la ubicacion que esta evaluando', function () {
    $zonaA = Ubicacion::where('nombre', 'A')->firstOrFail();

    // Otro request esta en medio de su asignacion sobre la zona A y todavia no commiteo.
    $this->paralela->beginTransaction();
    $this->paralela->table('mesas')->where('ubicacion_id', $zonaA->id)->lockForUpdate()->get();

    // Si ReservaService no bloqueara las mesas, esta llamada pasaria de largo y
    // asignaria mesas que el otro request esta por tomar.
    expect(fn () => $this->service->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 2))
        ->toThrow(QueryException::class);

    // y al soltarse el lock, la misma reserva entra sin problemas: la excepcion
    // anterior fue por el lock y no por otra causa
    $this->paralela->rollBack();

    $reserva = $this->service->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 2);

    expect($reserva->ubicacion->nombre)->toBe('A')
        ->and($reserva->mesas->pluck('numero')->all())->toBe([1]);
});

it('ve las reservas que otra conexion acaba de commitear', function () {
    // La zona A entera se ocupa desde otra conexion, ya commiteada.
    $zonaA = Ubicacion::where('nombre', 'A')->firstOrFail();
    $ocupante = $this->service->crear($this->user, CarbonImmutable::parse(MARTES), '20:00', 8);
    expect($ocupante->ubicacion->nombre)->toBe('A');

    // Un request nuevo, en otra conexion, no puede reusar esas mesas.
    $reserva = $this->service->crear($this->user, CarbonImmutable::parse(MARTES), '21:00', 2);

    // Lo que importa no es en que zona cae, sino que no reuse mesas que la otra
    // conexion ya ocupo: si no viera ese commit, volveria a asignar las de A.
    expect($reserva->ubicacion->nombre)->not->toBe('A')
        ->and($reserva->mesas->pluck('id')->intersect($ocupante->mesas->pluck('id')))->toBeEmpty();
});
