<?php

namespace App\Http\Controllers;

use App\Mail\SolicitarApoioMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ApoioController extends Controller
{
    public function solicitar(Request $request)
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

        $dados = $request->all();

        Mail::to($request->email_empresa)
            ->send(new SolicitarApoioMail($dados));

        return response()->json(['message' => 'Solicitação enviada com sucesso!']);
    }
}
