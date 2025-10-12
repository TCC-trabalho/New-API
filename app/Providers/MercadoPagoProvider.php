<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Models\UsuarioContaMP;
use Carbon\Carbon;

class MercadoPagoProvider
{
    /**
     * Verifica se o token expirou baseado em mp_token_expires_at
     */
    public function tokenExpirado(UsuarioContaMP $usuarioMP): bool
    {
        if (!$usuarioMP->mp_token_expires_at) {
            return true;
        }

        return Carbon::now()->greaterThanOrEqualTo(Carbon::parse($usuarioMP->mp_token_expires_at));
    }

    /**
     * Usa o refresh_token para gerar um novo access_token e atualizar o banco
     */
    public function renovarToken(UsuarioContaMP $usuarioMP): ?UsuarioContaMP
    {
        try {
            $clientId = config('services.mercadopago.client_id');
            $clientSecret = config('services.mercadopago.client_secret');
            $refreshToken = $usuarioMP->mp_refresh_token; // já descriptografado pelo accessor

            $response = Http::asForm()->post('https://api.mercadopago.com/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
            ]);

            if ($response->failed()) {
                Log::error('Erro ao renovar token Mercado Pago', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                return null;
            }

            $dados = $response->json();

            // Atualiza o banco de dados corretamente
            $usuarioMP->mp_access_token = Crypt::encryptString($dados['access_token']);
            $usuarioMP->mp_refresh_token = Crypt::encryptString($dados['refresh_token'] ?? $refreshToken);
            $usuarioMP->mp_token_expires_at = now()->addSeconds($dados['expires_in']);
            $usuarioMP->save();

            return $usuarioMP;
        } catch (\Exception $e) {
            Log::error('Exceção ao renovar token Mercado Pago: ' . $e->getMessage());
            return null;
        }
    }
}
