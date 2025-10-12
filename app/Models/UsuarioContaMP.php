<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UsuarioContaMP extends Model
{
    protected $table = 'usuario_conta_mp';
    protected $primaryKey = 'id_conta_mp';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tipo_usuario',
        'mp_user_id',
        'mp_access_token',
        'mp_refresh_token',
        'mp_token_expires_at',
        'mp_scope',
        'data_vinculo'
    ];

    protected $dates = ['mp_token_expires_at', 'data_vinculo'];

    /**
     *  Retorna o usuário dono dessa conta (Aluno ou Orientador)
     */
    public function usuario()
    {
        return match ($this->tipo_usuario) {
            'aluno' => $this->belongsTo(Student::class, 'id_usuario'),
            'orientador' => $this->belongsTo(Orientador::class, 'id_usuario'),
            default => null,
        };
    }

    /**
     * Retorna o access token descriptografado
     */
    public function getAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Retorna o refresh token descriptografado
     */
    public function getRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
