<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $table = 'projeto';
    protected $primaryKey = 'id_projeto';
    public $timestamps = false;

    protected $fillable = [
        'foto',
        'titulo',
        'descricao',
        'area',
        'data_criacao',
        'status',
        'objetivo',
        'justificativa',
        'id_grupo',
        'id_orientador',
        'qnt_empresas_patrocinam',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'id_grupo', 'id_grupo');
    }

    public function orientador()
    {
        return $this->belongsTo(Orientador::class, 'id_orientador', 'id_orientador');
    }
}