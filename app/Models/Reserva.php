<?php

namespace App\Models;

use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reserva extends Model
{
    /** @use HasFactory<ReservaFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'ubicacion_id',
        'fecha_servicio',
        'starts_at',
        'ends_at',
        'cantidad_personas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_servicio' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cantidad_personas' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Ubicacion, $this> */
    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    /** @return BelongsToMany<Mesa, $this> */
    public function mesas(): BelongsToMany
    {
        return $this->belongsToMany(Mesa::class);
    }

    /**
     * Reservas que pisan el intervalo [$inicio, $fin).
     *
     * Las desigualdades son estrictas a proposito: una reserva que termina 12:00
     * no bloquea otra que empieza 12:00, porque la mesa ya quedo libre.
     *
     * @param  Builder<Reserva>  $query
     */
    public function scopeSolapando(Builder $query, \DateTimeInterface $inicio, \DateTimeInterface $fin): void
    {
        $query->where('starts_at', '<', $fin)->where('ends_at', '>', $inicio);
    }
}
