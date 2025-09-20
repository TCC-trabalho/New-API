<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Orientador;

class OrientadorController extends Controller
{
    /**
     * GET /api/v1/orientadores
     */
    public function index()
    {
        $orientadores = Orientador::all();
        return response()->json($orientadores);
    }

    /**
     * GET /api/v1/orientadores/emails
     */
    public function listarEmails()
    {
        try {
            $orientadores = Orientador::select('id_orientador', 'email')->get();

            return response()->json([
                'status' => 'success',
                'data' => $orientadores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao listar orientadores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/v1/orientadores
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:100',
            'biografia' => 'nullable|string',
            'cpf' => 'required|digits:11|unique:orientador,cpf',
            'rg' => 'nullable|string|max:20|unique:orientador,rg',
            'email' => 'required|email|max:150|unique:orientador,email',
            'telefone' => 'nullable|string|max:20',
            'formacao' => 'nullable|string|max:100',
            'nomeUsuario' => 'required|string|max:100',
            'senha' => 'required|string|min:6|max:255',
        ]);

        $data['senha'] = bcrypt($data['senha']);

        $orientador = Orientador::create($data);
        return response()->json($orientador, 201);
    }

    /**
     * PUT /api/v1/orientadores/{id}
     */
    public function update(Request $request, $id)
    {
        $orientador = Orientador::find($id);
        if (!$orientador) {
            return response()->json(['message' => 'Orientador não encontrado'], 404);
        }

        $data = $request->validate([
            'nome' => 'sometimes|required|string|max:100',
            'biografia' => 'nullable|string',
            'telefone' => 'nullable|string|max:20',
            'formacao' => 'nullable|string|max:100',
            'nomeUsuario' => 'required|string|max:100',
            'senha' => 'nullable|string|min:6|max:255',
        ]);

        if (isset($data['senha'])) {
            $data['senha'] = bcrypt($data['senha']);
        }

        $orientador->update($data);
        return response()->json($orientador);
    }

    /**
     * DELETE /api/v1/orientadores/{id}
     */
    public function destroy($id)
    {
        $orientador = Orientador::find($id);
        if (!$orientador) {
            return response()->json(['message' => 'Orientador não encontrado'], 404);
        }

        $orientador->delete();
        return response()->json(null, 204);
    }

}
