<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visitante extends Model
{
    use HasFactory;

    protected $table = 'visitante';
    protected $primaryKey = 'id_visitante';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'biografia',
        'email',
        'nomeUsuario',
        'senha',
        'qnt_projetos_patrocinados',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'apoio', 'id_visitante', 'id_projeto')
            ->withPivot('data_apoio', 'tipo_apoio');
    }
}