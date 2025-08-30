<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * GET /api/v1/projetos
     */
    public function index()
    {
        $projects = Project::all();
        return response()->json($projects);
    }

    public function projetoControlado(Request $request)
    {
        $limit = $request->query('limit');

        if ($limit) {
            $projects = Project::limit($limit)->get();
        } else {
            $projects = Project::all();
        }

        return response()->json($projects);
    }

    /**
     * GET /api/v1/projetos/{id}
     */
    public function show($id)
    {
        // 1) Busca o projeto
        $projeto = DB::table('projeto')->where('id_projeto', $id)->first();
        if (!$projeto) {
            return response()->json(['message' => 'Projeto não encontrado'], 404);
        }

        // 2) Busca dados do grupo
        $grupo = DB::table('grupo')
            ->where('id_grupo', $projeto->id_grupo)
            ->select('id_grupo', 'nome', 'descricao', 'data_criacao')
            ->first();

        // 3) Busca integrantes do grupo (via aluno_grupo)
        $integrantes = DB::table('aluno_grupo as ag')
            ->join('aluno as a', 'a.id_aluno', '=', 'ag.id_aluno')
            ->where('ag.id_grupo', $projeto->id_grupo)
            ->select('a.id_aluno', 'a.nomeUsuario', 'a.nome', 'a.email')
            ->orderBy('a.nomeUsuario')
            ->get();

        // 4) Monta resposta no formato que você pediu
        $response = [
            'id_projeto' => $projeto->id_projeto,
            'titulo' => $projeto->titulo,
            'descricao' => $projeto->descricao,
            'area' => $projeto->area,
            'data_criacao' => $projeto->data_criacao,
            'objetivo' => $projeto->objetivo,
            'justificativa' => $projeto->justificativa,
            'id_grupo' => $projeto->id_grupo,
            // bloco do grupo + integrantes
            'grupo' => [
                'id_grupo' => $grupo?->id_grupo,
                'nome' => $grupo?->nome,
                'descricao' => $grupo?->descricao,
                'integrantes' => $integrantes, // [{ id_aluno, nomeUsuario, ... }]
            ],
            'id_orientador' => $projeto->id_orientador,
            'qnt_empresas_patrocinam' => $projeto->qnt_empresas_patrocinam,
            'status' => $projeto->status ?? null,
        ];

        return response()->json($response, 200);
    }

    /**
     * GET /api/v1/projetos/area/{area}
     */
    public function getByArea($area)
    {
        $projects = Project::where('area', $area)->get();
        return response()->json($projects);
    }

    /**
     * GET /api/v1/projetos/orientador/{id_orientador}
     */
    public function listarPorOrientador($id_orientador)
    {
        // Projetos em que esse orientador participa
        $projetos = Project::where('id_orientador', $id_orientador)
            ->orderByDesc('id_projeto')
            ->get();

        return response()->json([
            'total' => $projetos->count(),
            'projetos' => $projetos,
        ], 200);
    }

    /**
     * GET /api/v1/projetos/grupo/{id_grupo}
     */
    public function listarPorGrupo($id_grupo)
    {
        // Projetos do grupo (para uso pelo aluno via id do grupo)
        $projetos = Project::where('id_grupo', $id_grupo)
            ->orderByDesc('id_projeto')
            ->get();

        return response()->json([
            'total' => $projetos->count(),
            'projetos' => $projetos,
        ], 200);
    }

    /**
     * GET /api/v1/projetos/aluno/{id_aluno}
     */
    public function listarPorAluno($id_aluno)
    {
        if (!Student::where('id_aluno', $id_aluno)->exists()) {
            return response()->json(['message' => 'Aluno não encontrado'], 404);
        }

        $projetos = Project::whereIn('id_grupo', function ($q) use ($id_aluno) {
            $q->select('id_grupo')
                ->from('aluno_grupo')
                ->where('id_aluno', $id_aluno);
        })
            ->orderByDesc('id_projeto')
            ->get();

        return response()->json([
            'total' => $projetos->count(),
            'projetos' => $projetos,
        ], 200);
    }

    /**
     * POST /api/v1/projetos
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'foto' => 'nullable|string|max:255',
            'titulo' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'area' => 'nullable|string|max:50',
            'data_criacao' => 'nullable|date',
            'status' => 'nullable|in:ativo,inativo',
            'objetivo' => 'nullable|string',
            'justificativa' => 'nullable|string',
            'id_grupo' => 'required|integer|exists:grupo,id_grupo',
            'id_orientador' => 'required|integer|exists:orientador,id_orientador',
        ]);

        $project = Project::create($data);
        return response()->json($project, 201);
    }

    /**
     * PUT /api/v1/projetos/{id}
     */
    public function update(Request $request, $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['message' => 'Projeto não encontrado'], 404);
        }

        $data = $request->validate([
            'titulo' => 'sometimes|required|string|max:150',
            'area' => 'nullable|string|max:50',
            'status' => 'nullable|in:ativo,inativo',
            'descricao' => 'nullable|string',
            'objetivo' => 'nullable|string',
            'justificativa' => 'nullable|string',
        ]);

        $project->update($data);
        return response()->json($project);
    }

    /**
     * DELETE /api/v1/projetos/{id}
     */
    public function destroy($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['message' => 'Projeto não encontrado'], 404);
        }

        $project->delete();
        return response()->json(null, 204);
    }
}
