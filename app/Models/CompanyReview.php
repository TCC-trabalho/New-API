<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyReview extends Model
{
    use HasFactory;

    protected $table = 'empresa_avaliacao';
    protected $primaryKey = 'id_avaliacao';
    public $timestamps = false;

    protected $fillable = [
        'id_empresa',
        'estrelas',
        'comentario',
        'data_avaliacao',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_empresa', 'id_empresa');
    }
}