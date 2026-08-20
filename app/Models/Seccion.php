<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seccion extends Model
{
    /** @use HasFactory<\Database\Factories\SeccionFactory> */
    use HasFactory;

    // Laravel pluralizaria "Seccion" como "seccions".
    protected $table = 'secciones';

    protected $fillable = ['nombre'];

    /** @return HasMany<Ubicacion, $this> */
    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class);
    }
}
