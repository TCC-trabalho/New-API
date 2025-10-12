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
        'id_gestor_financeiro',
        'tipo_gestor',
        'qnt_empresas_patrocinam',
    ];

    public function gestorFinanceiro()
    {
        return match ($this->tipo_gestor) {
            'aluno' => $this->belongsTo(Student::class, 'id_gestor_financeiro'),
            'orientador' => $this->belongsTo(Orientador::class, 'id_gestor_financeiro'),
            default => null,
        };
    }

    public function contaGestor()
    {
        return $this->hasOne(UsuarioContaMP::class, 'id_usuario', 'id_gestor_financeiro')
            ->whereColumn('usuario_conta_mp.tipo_usuario', 'projeto.tipo_gestor');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'id_grupo', 'id_grupo');
    }

    public function orientador()
    {
        return $this->belongsTo(Orientador::class, 'id_orientador', 'id_orientador');
    }
}