<?php

namespace App\Models;

use App\Models\ProgressSintak;
use App\Models\Refleksi;
use App\Models\User;
use App\Models\WorkspaceSintak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyek extends Model
{
    use HasFactory;

    protected $table = 'proyek';

    protected $primaryKey = 'id_proyek';

    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'pertemuan_ke',
        'materi',
        'nama_proyek',
        'deskripsi',
        'tanggal_buat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_buat' => 'datetime',
        ];
    }
    public function displayName(): string
    {
        $name = trim((string) $this->nama_proyek);

        return $name !== ''
            ? $name
            : 'Proyek Pertemuan '.$this->pertemuan_ke;
    }

    public function displayDescription(): string
    {
        $description = trim((string) $this->deskripsi);

        return $description !== ''
            ? $description
            : 'Nama dan deskripsi proyek belum diisi. Lengkapi saat mengerjakan Sintaks BADRUL.';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function workspaceSintak(): HasMany
    {
        return $this->hasMany(WorkspaceSintak::class, 'id_proyek', 'id_proyek');
    }

    public function refleksi(): HasMany
    {
        return $this->hasMany(Refleksi::class, 'id_proyek', 'id_proyek');
    }

    public function progressSintak(): HasMany
    {
        return $this->hasMany(ProgressSintak::class, 'id_proyek', 'id_proyek');
    }
}