<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Orientador extends Model
{
    use HasFactory;

    protected $table = 'orientador';
    protected $primaryKey = 'id_orientador';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'biografia',
        'email',
        'telefone',
        'formacao',
        'nomeUsuario',
        'senha',
        'qtn_projetos',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'id_orientador', 'id_orientador');
    }
}
