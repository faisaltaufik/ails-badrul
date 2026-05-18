<?php

namespace App\Models;

use App\Models\Proyek;
use App\Models\SintakBadrul;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressSintak extends Model
{
    use HasFactory;

    protected $table = 'progress_sintak';

    protected $primaryKey = 'id_progress';

    public $timestamps = false;

    protected $fillable = [
        'id_proyek',
        'id_sintak',
        'status',
        'terakhir_update',
    ];

    protected function casts(): array
    {
        return [
            'terakhir_update' => 'datetime',
        ];
    }

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'id_proyek', 'id_proyek');
    }

    public function sintakBadrul(): BelongsTo
    {
        return $this->belongsTo(SintakBadrul::class, 'id_sintak', 'id_sintak');
    }
}