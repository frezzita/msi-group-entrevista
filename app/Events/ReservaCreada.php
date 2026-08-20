<?php

namespace App\Events;

use App\Models\Reserva;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se emite despues de que la reserva quedo commiteada.
 *
 * Es el punto de enganche para actualizar la ocupacion en tiempo real: implementando
 * ShouldBroadcast y devolviendo un canal por ubicacion, Laravel Reverb empujaria el
 * cambio a los clientes conectados. Se dejo sin broadcasting a proposito para que el
 * proyecto se levante sin un proceso extra ni compilacion de assets; la pantalla de
 * estado se refresca con polling. Ver STACK.md.
 */
class ReservaCreada
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Reserva $reserva) {}
}
