<?php

namespace App\Models;

use Database\Factories\MesaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mesa extends Model
{
    /** @use HasFactory<MesaFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['ubicacion_id', 'numero', 'capacidad'];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'capacidad' => 'integer',
        ];
    }

    /** @return BelongsTo<Ubicacion, $this> */
    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    /** @return BelongsToMany<Reserva, $this> */
    public function reservas(): BelongsToMany
    {
        return $this->belongsToMany(Reserva::class);
    }

    /**
     * Reservas futuras de esta mesa. Es lo que bloquea la baja: una mesa con
     * gente esperando no se puede dar de baja, pero una sin compromisos si,
     * conservando su historial.
     */
    public function tieneReservasFuturas(): bool
    {
        return $this->reservas()->where('ends_at', '>', now())->exists();
    }
}
