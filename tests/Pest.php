<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit', 'Concurrencia');

// RefreshDatabase envuelve cada test en una transaccion. Eso es lo que queremos en
// Feature y Unit, pero rompe los tests de concurrencia: una segunda conexion no ve
// nada de lo que la transaccion del test todavia no commiteo.
pest()->use(RefreshDatabase::class)->in('Feature', 'Unit');
