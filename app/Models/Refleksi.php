<?php

namespace App\Models;

use App\Models\Proyek;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refleksi extends Model
{
    use HasFactory;

    protected $table = 'refleksi';

    protected $primaryKey = 'id_refleksi';

    public $timestamps = false;

    protected $fillable = [
        'id_proyek',
        'isi_refleksi',
        'tanggal_refleksi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_refleksi' => 'datetime',
        ];
    }

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'id_proyek', 'id_proyek');
    }
}