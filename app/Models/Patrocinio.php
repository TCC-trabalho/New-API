<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Patrocinio extends Model
{
    use HasFactory;

    protected $table = 'patrocinio';
    protected $primaryKey = 'id_projeto';
    public $timestamps = false;

    protected $fillable = [
        'id_empresa',
        'data_patrocinio',
        'tipo_apoio',
        'mensagem',
        'valorPatrocinio',
    ];

    public function reviews()
    {
        return $this->hasMany(CompanyReview::class, 'id_empresa', 'id_empresa');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'patrocinio', 'id_empresa', 'id_projeto')
            ->withPivot('data_patrocinio', 'tipo_apoio');
    }
}