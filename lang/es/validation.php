<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser una direccion de correo valida.',
    'confirmed' => 'La confirmacion de :attribute no coincide.',
    'unique' => 'Ese :attribute ya esta registrado.',
    'date' => 'El campo :attribute debe ser una fecha valida.',
    'date_format' => 'El campo :attribute no tiene un formato valido.',
    'integer' => 'El campo :attribute debe ser un numero entero.',
    'exists' => 'El valor de :attribute no existe.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede tener mas de :max caracteres.',
    ],
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo',
        'password' => 'contrasena',
        'numero' => 'numero de mesa',
        'capacidad' => 'capacidad',
        'ubicacion_id' => 'ubicacion',
        'fecha' => 'fecha',
        'hora' => 'hora',
        'cantidad_personas' => 'cantidad de personas',
    ],
];
