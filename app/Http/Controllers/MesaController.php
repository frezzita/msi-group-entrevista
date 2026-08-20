<?php

namespace App\Http\Controllers;

use App\Http\Requests\MesaRequest;
use App\Models\Mesa;
use App\Models\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MesaController extends Controller
{
    public function index(): View
    {
        return view('mesas.index', [
            'ubicaciones' => Ubicacion::with(['mesas' => fn ($q) => $q->orderBy('numero')])
                ->enOrdenDeAsignacion()
                ->get(),
        ]);
    }

    public function store(MesaRequest $request): RedirectResponse
    {
        $mesa = Mesa::create($request->validated());

        return redirect()->route('mesas.index')
            ->with('ok', "Mesa {$mesa->numero} creada en la ubicacion {$mesa->ubicacion->nombre}.");
    }

    public function edit(Mesa $mesa): View
    {
        return view('mesas.edit', [
            'mesa' => $mesa,
            'ubicaciones' => Ubicacion::enOrdenDeAsignacion()->get(),
        ]);
    }

    public function update(MesaRequest $request, Mesa $mesa): RedirectResponse
    {
        // Cambiar la capacidad no revalida las reservas ya asignadas: se documenta
        // como supuesto en el README.
        $mesa->update($request->validated());

        return redirect()->route('mesas.index')->with('ok', "Mesa {$mesa->numero} actualizada.");
    }

    public function destroy(Mesa $mesa): RedirectResponse
    {
        if ($mesa->tieneReservasFuturas()) {
            $cantidad = $mesa->reservas()->where('ends_at', '>', now())->count();

            return redirect()->route('mesas.index')->with(
                'error',
                "La mesa {$mesa->numero} tiene {$cantidad} reserva(s) por venir: cancelalas o espera a que pasen."
            );
        }

        // Baja logica: las reservas historicas tienen que seguir mostrando en que
        // mesa fueron, aunque la mesa ya no exista para el restaurante.
        $mesa->delete();

        return redirect()->route('mesas.index')->with('ok', "Mesa {$mesa->numero} dada de baja.");
    }
}
