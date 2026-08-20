<?php

use App\Models\User;

it('manda al login a quien no esta autenticado', function (string $ruta) {
    $this->get($ruta)->assertRedirect(route('login'));
})->with(['/mesas', '/reservas', '/reservas/nueva', '/estado']);

it('responde 401 en las rutas JSON en vez de redirigir', function (string $ruta) {
    // bootstrap/app.php ya renderiza JSON para api/*: un cliente que consume el
    // endpoint necesita un 401, no el HTML del formulario de login
    $this->getJson($ruta)->assertUnauthorized();
})->with(['/api/reservas', '/api/estado', '/api/horarios']);

it('permite ingresar con credenciales validas', function () {
    $user = User::factory()->create(['email' => 'demo@msigroup.test', 'password' => 'password']);

    $this->post(route('login.store'), ['email' => 'demo@msigroup.test', 'password' => 'password'])
        ->assertRedirect(route('reservas.index'));

    $this->assertAuthenticatedAs($user);
});

it('rechaza credenciales invalidas', function () {
    User::factory()->create(['email' => 'demo@msigroup.test', 'password' => 'password']);

    $this->post(route('login.store'), ['email' => 'demo@msigroup.test', 'password' => 'incorrecta'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('permite registrarse y deja la sesion iniciada', function () {
    $this->post(route('register.store'), [
        'name' => 'Nuevo',
        'email' => 'nuevo@msigroup.test',
        'password' => 'contrasena-larga',
        'password_confirmation' => 'contrasena-larga',
    ])->assertRedirect(route('reservas.index'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'nuevo@msigroup.test']);
});

it('cierra la sesion', function () {
    $this->actingAs(usuario())->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});
