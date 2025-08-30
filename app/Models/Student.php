<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $table = 'aluno';
    protected $primaryKey = 'id_aluno';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'biografia',
        'cpf',
        'rg',
        'email',
        'nascimento',
        'telefone',
        'curso',
        'inst_ensino',
        'nomeUsuario',
        'senha',
        'qtn_projetos',
    ];

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'aluno_grupo', 'id_aluno', 'id_grupo')
            ->withPivot('data_ingresso');
    }
}