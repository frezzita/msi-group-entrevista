<?php

namespace App\Models;

use Database\Factories\UbicacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ubicacion extends Model
{
    /** @use HasFactory<UbicacionFactory> */
    use HasFactory;

    // Laravel pluralizaria "Ubicacion" como "ubicacions".
    protected $table = 'ubicaciones';

    protected $fillable = ['seccion_id', 'nombre', 'orden'];

    /** @return BelongsTo<Seccion, $this> */
    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    /** @return HasMany<Mesa, $this> */
    public function mesas(): HasMany
    {
        return $this->hasMany(Mesa::class);
    }

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    /**
     * El enunciado dice que la ubicacion la asigna el sistema "por orden":
     * este es el orden en que se prueban las zonas (A, B, C, D).
     *
     * @param  Builder<Ubicacion>  $query
     */
    public function scopeEnOrdenDeAsignacion(Builder $query): void
    {
        $query->orderBy('orden');
    }
}
