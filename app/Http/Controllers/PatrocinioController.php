<?php

namespace App\Http\Controllers;

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
            'data_apoio' => ['nullable', 'date'],
            'tipo_apoio' => ['required', Rule::in(['dinheiro', 'divulgacao', 'equipamentos', 'capacitacao'])],
            'mensagem' => ['nullable', 'string'],
            'valorApoio' => ['nullable', 'integer', 'min:0'],
        ]);

        if (empty($data['data_apoio'])) {
            $data['data_apoio'] = now()->toDateString();
        }

        $created = DB::transaction(function () use ($data) {
            DB::table('apoio')->insert([
                'id_visitante' => $data['id_visitante'],
                'id_projeto' => $data['id_projeto'],
                'data_apoio' => $data['data_apoio'],
                'tipo_apoio' => $data['tipo_apoio'],
                'mensagem' => $data['mensagem'] ?? null,
                'valorApoio' => $data['valorApoio'] ?? null,
            ]);

            DB::table('visitante')
                ->where('id_visitante', $data['id_visitante'])
                ->increment('qnt_projetos_patrocinados', 1);

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
        // Patrocínios em projetos dos grupos do aluno
        $valorPat = (int) DB::table('patrocinio as pt')
            ->join('projeto as pr', 'pr.id_projeto', '=', 'pt.id_projeto')
            ->join('aluno_grupo as ag', 'ag.id_grupo', '=', 'pr.id_grupo')
            ->where('ag.id_aluno', $aluno)
            ->sum('pt.valorPatrocinio');

        // Apoios em projetos dos grupos do aluno
        $valorApo = (int) DB::table('apoio as ap')
            ->join('projeto as pr', 'pr.id_projeto', '=', 'ap.id_projeto')
            ->join('aluno_grupo as ag', 'ag.id_grupo', '=', 'pr.id_grupo')
            ->where('ag.id_aluno', $aluno)
            ->sum('ap.valorApoio');

        return response()->json([
            'id_aluno' => (int) $aluno,
            'valor' => (int) ($valorPat + $valorApo),
        ], 200);
    }

    /**
     * GET /api/v1/orientadores/{orientador}/patrocinios/valor-total
     */
    public function valorTotalPorOrientador(int $orientador): JsonResponse
    {
        // Patrocínios de projetos orientados
        $valorPat = (int) DB::table('patrocinio as pt')
            ->join('projeto as pr', 'pr.id_projeto', '=', 'pt.id_projeto')
            ->where('pr.id_orientador', $orientador)
            ->sum('pt.valorPatrocinio');

        // Apoios de projetos orientados
        $valorApo = (int) DB::table('apoio as ap')
            ->join('projeto as pr', 'pr.id_projeto', '=', 'ap.id_projeto')
            ->where('pr.id_orientador', $orientador)
            ->sum('ap.valorApoio');

        return response()->json([
            'id_orientador' => (int) $orientador,
            'valor' => (int) ($valorPat + $valorApo),
        ], 200);
    }

    /** Soma dos valores de patrocínio do projeto. */
    private function sumValorProjeto(int $idProjeto): int
    {
        $patrocinio = (int) DB::table('patrocinio')
            ->where('id_projeto', $idProjeto)
            ->sum('valorPatrocinio');

        $apoio = (int) DB::table('apoio')
            ->where('id_projeto', $idProjeto)
            ->sum('valorApoio');

        return $patrocinio + $apoio;
    }
}
