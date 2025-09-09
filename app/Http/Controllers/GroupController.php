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
     * POST /api/v1//grupo/{id}/adicionar-integrantes
     */
    public function adicionarIntegrantes(Request $request, $id)
    {
        $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email',
        ]);

        $grupoId = $id;
        $emails = $request->input('emails');

        $alunos = Student::whereIn('email', $emails)->get();

        if (count($alunos) === 0) {
            return response()->json([
                'message' => 'Nenhum aluno encontrado com os e-mails fornecidos.',
            ], 404);
        }

        foreach ($alunos as $aluno) {
            DB::table('aluno_grupo')->updateOrInsert(
                [
                    'id_aluno' => $aluno->id_aluno,
                    'id_grupo' => $grupoId
                ],
                [
                    'data_ingresso' => now()->toDateString()
                ]
            );
        }

        return response()->json([
            'message' => 'Integrantes adicionados com sucesso.',
            'grupo_id' => $grupoId,
            'ids_alunos' => $alunos->pluck('id_aluno'),
            'emails_adicionados' => $alunos->pluck('email'),
        ]);
    }

}
