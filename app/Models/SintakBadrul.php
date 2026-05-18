<?php

namespace App\Models;

use App\Models\AiAssistant;
use App\Models\ProgressSintak;
use App\Models\WorkspaceSintak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SintakBadrul extends Model
{
    use HasFactory;

    protected $table = 'sintak_badrul';

    protected $primaryKey = 'id_sintak';

    public $timestamps = false;

    protected $fillable = [
        'kode_sintak',
        'nama_sintak',
    ];

    public function workspaceSintak(): HasMany
    {
        return $this->hasMany(WorkspaceSintak::class, 'id_sintak', 'id_sintak');
    }

    public function aiAssistants(): HasMany
    {
        return $this->hasMany(AiAssistant::class, 'id_sintak', 'id_sintak');
    }

    public function progressSintak(): HasMany
    {
        return $this->hasMany(ProgressSintak::class, 'id_sintak', 'id_sintak');
    }
}