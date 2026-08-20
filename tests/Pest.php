<?php

use App\Models\Mesa;
use App\Models\Seccion;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit', 'Concurrencia');

// RefreshDatabase solo en Feature: los tests unitarios no tocan la base, y los de
// concurrencia no pueden usarlo porque envuelve el test en una transaccion y una
// segunda conexion no veria nada de lo que esa transaccion todavia no commiteo.
pest()->use(RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function usuario(): User
{
    return User::factory()->create();
}

/**
 * Arma un local con el layout indicado.
 *
 * @param  array<string, array<int, int>>  $zonas  ['A' => [numeroMesa => capacidad], ...]
 */
function crearLocal(array $zonas): void
{
    $seccion = Seccion::create(['nombre' => 'Salon']);
    $orden = 1;

    foreach ($zonas as $nombre => $mesas) {
        $ubicacion = Ubicacion::create([
            'seccion_id' => $seccion->id,
            'nombre' => $nombre,
            'orden' => $orden++,
        ]);

        foreach ($mesas as $numero => $capacidad) {
            Mesa::create([
                'ubicacion_id' => $ubicacion->id,
                'numero' => $numero,
                'capacidad' => $capacidad,
            ]);
        }
    }
}

/** El mismo layout que siembran los seeders. */
function crearLocalDemo(): void
{
    crearLocal([
        'A' => [1 => 2, 2 => 2, 3 => 4],
        'B' => [4 => 4, 5 => 4, 6 => 6],
        'C' => [7 => 6, 8 => 8],
        'D' => [9 => 10, 10 => 4, 11 => 2],
    ]);
}

/** Mesa en memoria, sin tocar la base: para los tests del asignador. */
function mesaFalsa(int $numero, int $capacidad): Mesa
{
    $mesa = new Mesa(['numero' => $numero, 'capacidad' => $capacidad]);
    $mesa->id = $numero;

    return $mesa;
}

/*
|--------------------------------------------------------------------------
| Fechas de referencia
|--------------------------------------------------------------------------
| Agosto 2026: 24 lunes, 25 martes, 29 sabado, 30 domingo.
*/

const LUNES = '2026-08-24';
const MARTES = '2026-08-25';
const SABADO = '2026-08-29';
const DOMINGO = '2026-08-30';
