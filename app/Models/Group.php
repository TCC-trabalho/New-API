<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    protected $table = 'grupo';
    protected $primaryKey = 'id_grupo';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'descricao',
        'data_criacao',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'id_grupo', 'id_grupo');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'aluno_grupo', 'id_grupo', 'id_aluno')
            ->withPivot('data_ingresso');
    }
}