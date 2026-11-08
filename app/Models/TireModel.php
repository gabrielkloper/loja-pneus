<?php

namespace App\Models;

use Database\Factories\TireModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Um pneu do catálogo: marca, modelo, medida e índices (ADR-0003).
 *
 * @property int $id
 * @property string $brand
 * @property string $model
 * @property int $width
 * @property int $aspect_ratio
 * @property string $rim
 * @property int $load_index
 * @property string $speed_index
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['brand', 'model', 'width', 'aspect_ratio', 'rim', 'load_index', 'speed_index'])]
class TireModel extends Model
{
    /** @use HasFactory<TireModelFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'aspect_ratio' => 'integer',
            'rim' => 'decimal:1',
            'load_index' => 'integer',
        ];
    }

    /** @return HasMany<Listing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** "205/55 R16" — aro inteiro sem ".0", aro 17.5 mantido. */
    public function sizeLabel(): string
    {
        $rim = rtrim(rtrim((string) $this->rim, '0'), '.');

        return "{$this->width}/{$this->aspect_ratio} R{$rim}";
    }

    /** "Pirelli Cinturato P7 205/55 R16 91V" */
    public function fullName(): string
    {
        return "{$this->brand} {$this->model} {$this->sizeLabel()} {$this->load_index}{$this->speed_index}";
    }
}
