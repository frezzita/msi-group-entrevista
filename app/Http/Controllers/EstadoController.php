<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use App\Services\Reservas\DisponibilidadService;
use App\Services\Reservas\HorarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Ocupacion en vivo. Lee la disponibilidad desde la cache: es el caso de uso para el
 * que la cache existe, porque la pantalla se refresca sola cada pocos segundos.
 */
class EstadoController extends Controller
{
    public function __construct(
        private readonly DisponibilidadService $disponibilidad,
        private readonly HorarioService $horarios,
    ) {}

    public function index(): View
    {
        return view('estado.index', ['estado' => $this->estadoActual()]);
    }

    public function json(): JsonResponse
    {
        return response()->json($this->estadoActual());
    }

    private function estadoActual(): array
    {
        $fechaServicio = $this->horarios->fechaDeServicioActual();
        $ahora = now()->format('Y-m-d H:i:s');

        $ubicaciones = Ubicacion::with(['mesas' => fn ($q) => $q->orderBy('numero')])
            ->enOrdenDeAsignacion()
            ->get()
            ->map(function (Ubicacion $ubicacion) use ($fechaServicio, $ahora) {
                $ocupacion = $this->disponibilidad->ocupacion($fechaServicio, $ubicacion->id)
                    ->filter(fn (array $i) => $i['starts_at'] <= $ahora && $i['ends_at'] > $ahora)
                    ->keyBy('mesa_id');

                $mesas = $ubicacion->mesas->map(fn ($mesa) => [
                    'numero' => $mesa->numero,
                    'capacidad' => $mesa->capacidad,
                    'ocupada' => $ocupacion->has($mesa->id),
                    'hasta' => $ocupacion->has($mesa->id)
                        ? substr($ocupacion[$mesa->id]['ends_at'], 11, 5)
                        : null,
                ]);

                return [
                    'ubicacion' => $ubicacion->nombre,
                    'mesas' => $mesas->values()->all(),
                    'libres' => $mesas->where('ocupada', false)->count(),
                    'total' => $mesas->count(),
                ];
            });

        return [
            'fecha_servicio' => $fechaServicio->toDateString(),
            'actualizado' => now()->format('H:i:s'),
            'ubicaciones' => $ubicaciones->values()->all(),
        ];
    }
}
