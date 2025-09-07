<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Company extends Model
{
    use HasFactory;

    protected $table = 'empresa';
    protected $primaryKey = 'id_empresa';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'foto',
        'descricao',
        'setor',
        'cnpj',
        'endereco',
        'email',
        'telefone',
        'senha',
        'qnt_projetos_patrocinados',
    ];

    public function reviews()
    {
        return $this->hasMany(CompanyReview::class, 'id_empresa', 'id_empresa');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'patrocinio', 'id_empresa', 'id_projeto')
                    ->withPivot('data_patrocinio','tipo_apoio');
    }
}