<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ResendService;

class ApoioController extends Controller
{
    public function solicitar(Request $request, ResendService $resendService)
    {
        $request->validate([
            'nome_usuario' => 'required|string',
            'tipo_usuario' => 'required|string',
            'projeto' => 'required|string',
            'id_projeto' => 'required|integer',
            'tipo_apoio' => 'required|string',
            'mensagem' => 'required|string|max:200',
            'email_empresa' => 'required|email',
        ]);

        $resendService->sendSupportRequest($request->all());

        return response()->json(['message' => 'Solicitação enviada com sucesso!']);
    }
}

