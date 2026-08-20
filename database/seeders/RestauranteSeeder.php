<?php

namespace Database\Seeders;

use App\Models\Mesa;
use App\Models\Seccion;
use App\Models\Ubicacion;
use Illuminate\Database\Seeder;

/**
 * Layout del local.
 *
 * Las capacidades no son arbitrarias: estan elegidas para que la logica del punto 3
 * se pueda ver funcionando sin tener que adivinar que probar.
 *
 *   2 personas  -> A, mesa 1                 (mejor ajuste: no ocupa una mesa grande)
 *   6 personas  -> A, mesas 1 + 3            (union de 2 mesas)
 *   8 personas  -> A, mesas 1 + 2 + 3        (union de 3 mesas, el maximo)
 *   9 personas  -> A no llega (max 8), cae a B  (salto de ubicacion por orden)
 *  17 personas  -> ninguna ubicacion llega   (sin disponibilidad)
 */
class RestauranteSeeder extends Seeder
{
    public function run(): void
    {
        // Volver a correr los seeders sobre una base ya sembrada no deberia explotar
        // con una violacion de unicidad: se avisa y se corta.
        if (Seccion::exists()) {
            $this->command?->warn('El local ya esta cargado. Usa migrate:fresh --seed para regenerarlo.');

            return;
        }

        $salon = Seccion::create(['nombre' => 'Salon']);
        $terraza = Seccion::create(['nombre' => 'Terraza']);

        $layout = [
            ['seccion' => $salon,   'nombre' => 'A', 'orden' => 1, 'mesas' => [1 => 2, 2 => 2, 3 => 4]],
            ['seccion' => $salon,   'nombre' => 'B', 'orden' => 2, 'mesas' => [4 => 4, 5 => 4, 6 => 6]],
            ['seccion' => $terraza, 'nombre' => 'C', 'orden' => 3, 'mesas' => [7 => 6, 8 => 8]],
            ['seccion' => $terraza, 'nombre' => 'D', 'orden' => 4, 'mesas' => [9 => 10, 10 => 4, 11 => 2]],
        ];

        foreach ($layout as $zona) {
            $ubicacion = Ubicacion::create([
                'seccion_id' => $zona['seccion']->id,
                'nombre' => $zona['nombre'],
                'orden' => $zona['orden'],
            ]);

            foreach ($zona['mesas'] as $numero => $capacidad) {
                Mesa::create([
                    'ubicacion_id' => $ubicacion->id,
                    'numero' => $numero,
                    'capacidad' => $capacidad,
                ]);
            }
        }
    }
}
