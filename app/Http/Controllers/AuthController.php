<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\Student as Aluno;
use App\Models\Orientador;
use App\Models\Company as Empresa;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $tipo  = $request->input('tipo');   // 'aluno' | 'orientador' | 'empresa'
        $senha = $request->input('senha');

        // Localiza usuário conforme o tipo
        switch ($tipo) {
            case 'aluno':
                $user = Aluno::where('email', $request->input('email'))->first();
                break;

            case 'orientador':
                $user = Orientador::where('email', $request->input('email'))->first();
                break;

            case 'empresa':
                // Exemplo: empresa autentica por CNPJ + senha
                $user = Empresa::where('cnpj', $request->input('cnpj'))->first();
                break;

            default:
                return response()->json(['message' => 'Tipo de usuário inválido'], 400);
        }

        if (!$user || !isset($user->senha) || !Hash::check($senha, $user->senha)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $payloadUser = $user->toArray();

        if ($tipo === 'aluno') {
            $qtdProjetosAluno = DB::table('projeto as p')
                ->join('aluno_grupo as ag', 'p.id_grupo', '=', 'ag.id_grupo')
                ->where('ag.id_aluno', $user->id_aluno)
                ->count();

            try {
                $user->qtn_projetos = $qtdProjetosAluno;
                $user->save();
            } catch (\Throwable $e) {
            }

            $payloadUser['qtn_projetos'] = $qtdProjetosAluno;
        }

        if ($tipo === 'orientador') {
            $qtdProjetosOrientador = DB::table('projeto')
                ->where('id_orientador', $user->id_orientador)
                ->count();

            try {
                $user->qtn_projetos = $qtdProjetosOrientador;
                $user->save();
            } catch (\Throwable $e) {
            }

            $payloadUser['qtn_projetos'] = $qtdProjetosOrientador;
        }

        // Retorno final
        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user'    => $payloadUser,
            'tipo'    => $tipo,
        ], 200);
    }
}
