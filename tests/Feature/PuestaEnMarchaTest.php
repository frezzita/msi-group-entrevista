<?php

use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Seccion;
use App\Models\Ubicacion;

/*
|--------------------------------------------------------------------------
| Generacion de URLs detras de un reverse proxy
|--------------------------------------------------------------------------
|
| La aplicacion se despliega detras de un proxy que termina TLS y reenvia HTTP al
| origen. Sin confiar en ese proxy, Laravel ve la request como http y genera los
| action de los formularios apuntando a http bajo un dominio https: el proxy
| redirige el POST y el cuerpo se pierde, asi que el login deja de funcionar.
*/

// El generador de URLs usa la request real: el esquema sale de si la conexion es
// segura, no de una constante. Detras del proxy la conexion al origen es HTTP, asi que
// sin confiar en la cabecera reenviada los formularios apuntarian a http bajo un dominio
// https, el proxy redirigiria el POST y el cuerpo se perderia.
//
// Se piden URLs absolutas y no rutas relativas a proposito: en tests el framework arma
// una request a partir de APP_URL (SetRequestForConsole) y expande las rutas relativas
// contra ella. Como APP_URL puede ser https, una ruta relativa haria pasar el test sin
// que trustProxies hiciera nada.

it('genera URLs https cuando el proxy lo indica', function () {
    $this->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->get('http://localhost/login')
        ->assertOk()
        ->assertSee('action="https://localhost/login"', escape: false);
});

it('genera URLs http cuando no hay proxy delante', function () {
    // el contrapunto: confirma que el esquema sale de la cabecera reenviada y no de
    // la configuracion
    $this->get('http://localhost/login')
        ->assertOk()
        ->assertSee('action="http://localhost/login"', escape: false)
        ->assertDontSee('action="https://', escape: false);
});

/*
|--------------------------------------------------------------------------
| Seeders
|--------------------------------------------------------------------------
*/

it('siembra el local y las reservas de demostracion', function () {
    $this->seed();

    expect(Seccion::count())->toBe(2)
        ->and(Ubicacion::count())->toBe(4)
        ->and(Mesa::count())->toBe(11)
        ->and(Reserva::count())->toBeGreaterThan(0);
});

it('deja el layout preparado para que la union de mesas sea demostrable', function () {
    $this->seed();

    $zonaA = Ubicacion::where('nombre', 'A')->firstOrFail();

    // A no tiene ninguna mesa que reciba 6 personas sola: obliga a unir.
    // Y su capacidad total es 8, de modo que un grupo de 9 tiene que saltar de zona.
    expect($zonaA->mesas->max('capacidad'))->toBeLessThan(6)
        ->and($zonaA->mesas->sum('capacidad'))->toBe(8);
});

it('se puede volver a correr sin romper ni duplicar', function () {
    $this->seed();

    $mesas = Mesa::count();
    $reservas = Reserva::count();

    // Correr migrate --seed sobre una base ya sembrada no deberia explotar con una
    // violacion de unicidad: los seeders detectan que el local ya esta cargado.
    $this->seed();

    expect(Mesa::count())->toBe($mesas)
        ->and(Reserva::count())->toBe($reservas)
        ->and(Seccion::count())->toBe(2);
});

it('crea el usuario de prueba que documenta el README', function () {
    $this->seed();

    $this->assertDatabaseHas('users', ['email' => 'demo@msigroup.test']);

    $this->post(route('login.store'), ['email' => 'demo@msigroup.test', 'password' => 'password'])
        ->assertRedirect(route('reservas.index'));

    $this->assertAuthenticated();
});
