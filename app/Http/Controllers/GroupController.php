<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Student;

class GroupController extends Controller
{
    /**
     * GET /api/v1/grupos
     */
    public function index()
    {
        $groups = Group::all();
        return response()->json($groups);
    }

    /**
     * GET /api/v1/grupo/{id}/integrantes
     */
    public function integrantes($id)
    {
        $grupo = Group::with(['students:id_aluno,nome,email'])
            ->findOrFail($id);

        return response()->json($grupo->students);
    }

    /**
     * POST /api/v1/grupos
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string',
            'data_criacao' => 'nullable|date',
        ]);

        $group = Group::create($data);
        return response()->json($group, 201);
    }

    /**
     * POST /api/v1/grupo/{id}/adicionar-integrantes
     */
    public function adicionarIntegrante(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $grupoId = $id;
        $email = $request->input('email');

        $aluno = Student::where('email', $email)->first();

        if (!$aluno) {
            return response()->json([
                'message' => 'Nenhum aluno encontrado com o e-mail fornecido.',
            ], 404);
        }

        DB::table('aluno_grupo')->updateOrInsert(
            [
                'id_aluno' => $aluno->id_aluno,
                'id_grupo' => $grupoId
            ],
            [
                'data_ingresso' => now()->toDateString()
            ]
        );

        return response()->json([
            'message' => 'Integrante adicionado com sucesso.',
            'grupo_id' => $grupoId,
            'id_aluno' => $aluno->id_aluno,
            'email_adicionado' => $aluno->email,
        ]);
    }

    /**
     * DELETE /api/v1/grupo/{id}/remover-integrante/{studentId} 
     */
    public function removerIntegrante($idGrupo, $idAluno)
    {
        $grupo = Group::findOrFail($idGrupo);

        $grupo->students()->detach($idAluno);

        return response()->json(['message' => 'Integrante removido com sucesso']);
    }

}
