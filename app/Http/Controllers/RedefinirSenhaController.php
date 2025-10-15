<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Providers\ResendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RedefinirSenhaController extends Controller
{
    protected $resendService;

    public function __construct(ResendService $resendService)
    {
        $this->resendService = $resendService;
    }

    // Envia ou reenvia o código de verificação
    public function enviarCodigo(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'tipo_user' => 'required|string'
        ]);

        $tabela = $this->resolveTabela($request->tipo_user);

        $user = DB::table($tabela)->where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        $codigo = rand(1000, 9999);
        Cache::put("codigo_{$request->email}", $codigo, now()->addMinutes(10));

        $dados = [
            'email_destino' => $request->email,
            'codigo' => $codigo,
            'nome' => $user->nome ?? 'Usuário',
        ];

        $this->resendService->sendPasswordResetCode($dados);

        return response()->json(['message' => 'Código enviado com sucesso.']);
    }

    // Verifica o código recebido pelo usuário
    public function verificarCodigo(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'codigo' => 'required|digits:4'
        ]);

        $codigoSalvo = Cache::get("codigo_{$request->email}");

        if (!$codigoSalvo || $codigoSalvo != $request->codigo) {
            return response()->json(['message' => 'Código inválido ou expirado.'], 400);
        }

        return response()->json(['message' => 'Código verificado com sucesso.']);
    }

    // Redefine a senha do usuário
    public function redefinirSenha(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'nova_senha' => 'required|min:6|confirmed',
            'tipo_user' => 'required|string',
            'codigo' => 'required|digits:4'
        ]);

        $codigoSalvo = Cache::get("codigo_{$request->email}");
        if (!$codigoSalvo || $codigoSalvo != $request->codigo) {
            return response()->json(['message' => 'Código inválido ou expirado.'], 400);
        }

        $tabela = $this->resolveTabela($request->tipo_user);

        $user = DB::table($tabela)->where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        DB::table($tabela)
            ->where('email', $request->email)
            ->update(['senha' => Hash::make($request->nova_senha)]);

        Cache::forget("codigo_{$request->email}");

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }

    // Define a tabela de acordo com o tipo de usuário
    private function resolveTabela($tipo)
    {
        return match (strtolower($tipo)) {
            'aluno' => 'aluno',
            'empresa' => 'empresa',
            'orientador' => 'orientadore',
            'visitante' => 'visitante',
            default => throw new \Exception('Tipo de usuário inválido.')
        };
    }
}
