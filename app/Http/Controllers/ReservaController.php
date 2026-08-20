<?php

namespace App\Http\Controllers;

use App\Exceptions\ReservaNoPosibleException;
use App\Http\Requests\ReservaRequest;
use App\Models\Reserva;
use App\Queries\ReservasPorFechaQuery;
use App\Services\Reservas\HorarioService;
use App\Services\Reservas\ReservaService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $reservas,
        private readonly HorarioService $horarios,
        private readonly ReservasPorFechaQuery $listado,
    ) {}

    /** Punto 4: listado por fecha, por ubicacion, en una sola consulta. */
    public function index(Request $request): View
    {
        $fecha = $this->fechaPedida($request);

        return view('reservas.index', [
            'fecha' => $fecha,
            'grupos' => $this->listado->agrupadasPorUbicacion($fecha),
        ]);
    }

    /** El mismo listado como JSON. */
    public function indexJson(Request $request): JsonResponse
    {
        $fecha = $this->fechaPedida($request);

        return response()->json([
            'fecha' => $fecha->toDateString(),
            'reservas' => $this->listado->paraFecha($fecha),
        ]);
    }

    public function create(Request $request): View
    {
        $fecha = $this->fechaPedida($request);

        return view('reservas.create', [
            'fecha' => $fecha,
            'horarios' => $this->horarios->horariosDisponibles($fecha),
        ]);
    }

    public function store(ReservaRequest $request): RedirectResponse
    {
        try {
            $reserva = $this->reservas->crear(
                $request->user(),
                CarbonImmutable::parse($request->string('fecha')->toString()),
                $request->string('hora')->toString(),
                $request->integer('cantidad_personas'),
            );
        } catch (ReservaNoPosibleException $e) {
            // Rechazo de negocio, no error tecnico: vuelve como mensaje al usuario.
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('reservas.index', ['fecha' => $reserva->fecha_servicio->toDateString()])
            ->with('ok', sprintf(
                'Reserva confirmada: ubicacion %s, mesa(s) %s, %s de %s a %s.',
                $reserva->ubicacion->nombre,
                $reserva->mesas->pluck('numero')->sort()->implode(', '),
                $reserva->starts_at->format('d/m'),
                $reserva->starts_at->format('H:i'),
                $reserva->ends_at->format('H:i'),
            ));
    }

    public function destroy(Reserva $reserva): RedirectResponse
    {
        $fecha = $reserva->fecha_servicio->toDateString();

        $this->reservas->cancelar($reserva);

        return redirect()->route('reservas.index', ['fecha' => $fecha])->with('ok', 'Reserva cancelada.');
    }

    /** Horarios validos de una fecha, para que el formulario actualice el selector. */
    public function horarios(Request $request): JsonResponse
    {
        $fecha = $this->fechaPedida($request);

        return response()->json([
            'fecha' => $fecha->toDateString(),
            'horarios' => $this->horarios->horariosDisponibles($fecha),
        ]);
    }

    private function fechaPedida(Request $request): CarbonImmutable
    {
        $fecha = $request->string('fecha')->toString();

        return $fecha !== '' && strtotime($fecha) !== false
            ? CarbonImmutable::parse($fecha)->startOfDay()
            : $this->horarios->fechaDeServicioActual();
    }
}
