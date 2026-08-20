<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base de los rechazos de negocio al crear una reserva. Se distinguen de un error
 * tecnico: son respuestas esperadas del dominio y llegan al usuario como mensaje,
 * no como error 500.
 */
abstract class ReservaNoPosibleException extends RuntimeException {}
