<?php

namespace App\Http\Controllers;

use App\Models\Patrocinio;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PatrocinioController extends Controller
{

    /**
     * POST /api/v1/patrocinios
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_empresa' => ['required', 'integer', 'exists:empresa,id_empresa'],
            'id_projeto' => ['required', 'integer', 'exists:projeto,id_projeto'],
            'data_patrocinio' => ['nullable', 'date'],
            'tipo_apoio' => ['required', Rule::in(['dinheiro', 'divulgacao', 'equipamentos', 'capacitacao'])],
            'mensagem' => ['nullable', 'string'],
            'valorPatrocinio' => ['nullable', 'integer', 'min:0'],
        ]);

        if (empty($data['data_patrocinio'])) {
            $data['data_patrocinio'] = now()->toDateString();
        }

        $exists = DB::table('patrocinio')
            ->where('id_empresa', $data['id_empresa'])
            ->where('id_projeto', $data['id_projeto'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Esta empresa já patrocina este projeto.'
            ], 409);
        }

        $created = DB::transaction(function () use ($data) {
            DB::table('patrocinio')->insert([
                'id_empresa' => $data['id_empresa'],
                'id_projeto' => $data['id_projeto'],
                'data_patrocinio' => $data['data_patrocinio'],
                'tipo_apoio' => $data['tipo_apoio'],
                'mensagem' => $data['mensagem'] ?? null,
                'valorPatrocinio' => $data['valorPatrocinio'] ?? null,
            ]);

            DB::table('empresa')
                ->where('id_empresa', $data['id_empresa'])
                ->increment('qnt_projetos_patrocinados', 1);

            return DB::table('patrocinio')
                ->where('id_empresa', $data['id_empresa'])
                ->where('id_projeto', $data['id_projeto'])
                ->first();
        });

        return response()->json([
            'message' => 'Patrocínio criado com sucesso.',
            'patrocinio' => $created,
        ], 201);
    }

    /**
     * POST /api/v1/apoios
     */
    public function storeApoio(Request $request)
    {
        $data = $request->validate([
            'id_visitante' => ['required', 'integer', 'exists:visitante,id_visitante'],
            'id_projeto' => ['required', 'integer', 'exists:projeto,id_projeto'],
            'data_patrocinio' => ['nullable', 'date'],
            'tipo_apoio' => ['required', Rule::in(['dinheiro', 'divulgacao', 'equipamentos', 'capacitacao'])],
            'mensagem' => ['nullable', 'string'],
            'valorPatrocinio' => ['nullable', 'integer', 'min:0'],
        ]);

        if (empty($data['data_patrocinio'])) {
            $data['data_patrocinio'] = now()->toDateString();
        }

        $exists = DB::table('apoio')
            ->where('id_visitante', $data['id_visitante'])
            ->where('id_projeto', $data['id_projeto'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Este visitante já apoia este projeto.'
            ], 409);
        }

        $created = DB::transaction(function () use ($data) {
            DB::table('apoio')->insert([
                'id_visitante' => $data['id_visitante'],
                'id_projeto' => $data['id_projeto'],
                'data_patrocinio' => $data['data_patrocinio'],
                'tipo_apoio' => $data['tipo_apoio'],
                'mensagem' => $data['mensagem'] ?? null,
                'valorPatrocinio' => $data['valorPatrocinio'] ?? null,
            ]);

            DB::table('visitante')
                ->where('id_visitante', $data['id_visitante'])
                ->increment('qnt_projetos_apoidados', 1);

            return DB::table('apoio')
                ->where('id_visitante', $data['id_visitante'])
                ->where('id_projeto', $data['id_projeto'])
                ->first();
        });

        return response()->json([
            'message' => 'Apoio criado com sucesso.',
            'patrocinio' => $created,
        ], 201);
    }

    /**
     * GET /api/v1/projetos/{projeto}/patrocinios/valor/aluno/{aluno}
     */
    public function valorPorProjetoAluno(int $projeto, int $aluno): JsonResponse
    {
        $proj = Project::select(['id_projeto', 'id_grupo'])
            ->where('id_projeto', $projeto)
            ->first();

        if (!$proj) {
            return response()->json(['message' => 'Projeto não encontrado'], 404);
        }

        // valida vínculo do aluno ao grupo do projeto
        $pertence = DB::table('aluno_grupo')
            ->where('id_aluno', $aluno)
            ->where('id_grupo', $proj->id_grupo)
            ->exists();

        if (!$pertence) {
            return response()->json(['message' => 'Aluno não pertence ao grupo do projeto'], 403);
        }

        $valor = $this->sumValorProjeto($projeto);

        // ✅ retorna somente o objeto final (sem response aninhada)
        return response()->json([
            'id_projeto' => (int) $projeto,
            'valor' => (int) $valor,
        ], 200);
    }

    /**
     * GET /api/v1/projetos/{projeto}/patrocinios/valor/orientador/{orientador}
     */
    public function valorPorProjetoOrientador(int $projeto, int $orientador): JsonResponse
    {
        $proj = Project::select(['id_projeto', 'id_orientador'])
            ->where('id_projeto', $projeto)
            ->first();

        if (!$proj) {
            return response()->json(['message' => 'Projeto não encontrado'], 404);
        }

        if ((int) $proj->id_orientador !== (int) $orientador) {
            return response()->json(['message' => 'Orientador não vinculado a este projeto'], 403);
        }

        $valor = $this->sumValorProjeto($projeto);

        return response()->json([
            'id_projeto' => (int) $projeto,
            'valor' => (int) $valor,
        ], 200);
    }

    /**
     * GET /api/v1/alunos/{aluno}/patrocinios/valor-total
     */
    public function valorTotalPorAluno(int $aluno): JsonResponse
    {
        $valor = (int) DB::table('patrocinio as pt')
            ->join('projeto as pr', 'pr.id_projeto', '=', 'pt.id_projeto')
            ->join('aluno_grupo as ag', 'ag.id_grupo', '=', 'pr.id_grupo')
            ->where('ag.id_aluno', $aluno)
            ->sum('pt.valorPatrocinio');

        return response()->json([
            'id_aluno' => $aluno,
            'valor' => $valor,
        ], 200);
    }

    /**
     * GET /api/v1/orientadores/{orientador}/patrocinios/valor-total
     */
    public function valorTotalPorOrientador(int $orientador): JsonResponse
    {
        $valor = (int) DB::table('patrocinio as pt')
            ->join('projeto as pr', 'pr.id_projeto', '=', 'pt.id_projeto')
            ->where('pr.id_orientador', $orientador)
            ->sum('pt.valorPatrocinio');

        return response()->json([
            'id_orientador' => $orientador,
            'valor' => $valor,
        ], 200);
    }

    /** Soma dos valores de patrocínio do projeto. */
    private function sumValorProjeto(int $idProjeto): int
    {
        return (int) Patrocinio::where('id_projeto', $idProjeto)->sum('valorPatrocinio');
    }
}
