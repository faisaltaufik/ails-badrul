<?php

namespace App\Models;

use App\Models\SintakBadrul;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssistant extends Model
{
    use HasFactory;

    protected $table = 'ai_assistant';

    protected $primaryKey = 'id_ai';

    public $timestamps = false;

    protected $fillable = [
        'id_sintak',
        'nama_ai',
        'deskripsi_ai',
        'prompt_otomatis',
    ];

    public function sintakBadrul(): BelongsTo
    {
        return $this->belongsTo(SintakBadrul::class, 'id_sintak', 'id_sintak');
    }
}