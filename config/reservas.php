<?php

use Carbon\Carbon;

return [

    /*
    |--------------------------------------------------------------------------
    | Duracion y anticipacion
    |--------------------------------------------------------------------------
    */

    'duracion_minutos' => 120,

    // "se puede reservar hasta 15 minutos antes"
    'anticipacion_minutos' => 15,

    'max_mesas_por_reserva' => 3,

    /*
    |--------------------------------------------------------------------------
    | Asientos perdidos al unir mesas
    |--------------------------------------------------------------------------
    |
    | El enunciado permite unir mesas pero no define si se pierden asientos en los
    | lados que quedan pegados. En un restaurante real dos mesas de 4 unidas dan 6
    | lugares utiles, no 8. Como es un supuesto y no un requisito, el default es 0
    | (suma simple, apego literal al enunciado) y queda parametrizado: subirlo a 2
    | activa el comportamiento realista sin tocar codigo.
    |
    */

    'asientos_perdidos_por_union' => 0,

    /*
    |--------------------------------------------------------------------------
    | Cache de disponibilidad
    |--------------------------------------------------------------------------
    |
    | TTL de respaldo, no el mecanismo principal: la clave se invalida
    | explicitamente al confirmar una reserva.
    |
    */

    'cache_ttl_minutos' => 10,

    /*
    |--------------------------------------------------------------------------
    | Ventanas de servicio
    |--------------------------------------------------------------------------
    |
    | Se define apertura + duracion de la ventana en vez de apertura + cierre para
    | no tener que representar el "24:00" de lunes a viernes (que Carbon no parsea)
    | ni el cierre a las 2AM del sabado como si fuera anterior a la apertura.
    |
    | El ultimo horario reservable sale de: apertura + duracion_ventana - duracion_reserva
    |   L-V     10:00 + 840min - 120min = 22:00
    |   Sabado  22:00 + 240min - 120min = 00:00 del dia siguiente
    |   Domingo 12:00 + 240min - 120min = 14:00
    |
    */

    'horarios' => [
        Carbon::MONDAY => ['apertura' => '10:00', 'duracion_minutos' => 840], // 10 a 24
        Carbon::TUESDAY => ['apertura' => '10:00', 'duracion_minutos' => 840],
        Carbon::WEDNESDAY => ['apertura' => '10:00', 'duracion_minutos' => 840],
        Carbon::THURSDAY => ['apertura' => '10:00', 'duracion_minutos' => 840],
        Carbon::FRIDAY => ['apertura' => '10:00', 'duracion_minutos' => 840],
        Carbon::SATURDAY => ['apertura' => '22:00', 'duracion_minutos' => 240], // 22 a 02
        Carbon::SUNDAY => ['apertura' => '12:00', 'duracion_minutos' => 240], // 12 a 16
    ],

    /*
    |--------------------------------------------------------------------------
    | Grilla de horarios de la UI
    |--------------------------------------------------------------------------
    |
    | Paso en minutos del selector de hora del formulario. Es comodidad de interfaz:
    | el backend acepta cualquier hora valida dentro de la ventana.
    |
    */

    'paso_grilla_minutos' => 30,

];
