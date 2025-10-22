<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\UsuarioContaMP;
use App\Providers\CloudiNary;
use App\Providers\MercadoPagoProvider;
use Illuminate\Support\Str;
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
        $projects = DB::table('projeto as p')
            ->select('p.*')
            ->selectSub(function ($q) {
                $q->from('aluno_grupo as ag')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ag.id_grupo', 'p.id_grupo');
            }, 'integrantes')
            ->get();

        return response()->json($projects);
    }

    public function projetoControlado(Request $request)
    {
        $limit = $request->query('limit', 4);

        $projects = Project::inRandomOrder()
            ->limit($limit)
            ->get();

        return response()->json($projects);
    }

    /**
     * GET /api/v1/projetos/{id}
     */
    public function show($id)
    {
        $projeto = DB::table('projeto')->where('id_projeto', $id)->first();
        if (!$projeto) {
            return response()->json(['message' => 'Projeto não encontrado'], 404);
        }

        $grupo = DB::table('grupo')
            ->where('id_grupo', $projeto->id_grupo)
            ->select('id_grupo', 'nome', 'descricao', 'data_criacao')
            ->first();

        $integrantes = DB::table('aluno_grupo as ag')
            ->join('aluno as a', 'a.id_aluno', '=', 'ag.id_aluno')
            ->where('ag.id_grupo', $projeto->id_grupo)
            ->select('a.id_aluno', 'a.nomeUsuario', 'a.nome', 'a.email')
            ->orderBy('a.nomeUsuario')
            ->get();

        $nomeOrientador = DB::table('orientador')
            ->where('id_orientador', $projeto->id_orientador)
            ->select('nome')
            ->first();

        $qtdIntegrantes = DB::table('aluno_grupo')
            ->where('id_grupo', $projeto->id_grupo)
            ->count();


        $response = [
            'id_projeto' => $projeto->id_projeto,
            'foto' => $projeto->foto,
            'titulo' => $projeto->titulo,
            'descricao' => $projeto->descricao,
            'area' => $projeto->area,
            'data_criacao' => $projeto->data_criacao,
            'objetivo' => $projeto->objetivo,
            'justificativa' => $projeto->justificativa,
            'id_grupo' => $projeto->id_grupo,
            'integrantes' => $qtdIntegrantes,
            'grupo' => [
                'id_grupo' => $grupo?->id_grupo,
                'nome' => $grupo?->nome,
                'descricao' => $grupo?->descricao,
                'integrantes' => $integrantes,
            ],
            'id_orientador' => $projeto->id_orientador,
            'nome_orientador' => $nomeOrientador?->nome,
            'id_gestor_financeiro' => $projeto->id_gestor_financeiro,
            'tipo_gestor' => $projeto->tipo_gestor,
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
        $projetos = DB::table('projeto as p')
            ->select('p.*')
            ->selectSub(function ($q) {
                $q->from('aluno_grupo as ag')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ag.id_grupo', 'p.id_grupo');
            }, 'integrantes')
            ->where('p.id_orientador', $id_orientador)
            ->orderByDesc('p.id_projeto')
            ->get();

        $projetos->each(fn($p) => $p->integrantes = (int) $p->integrantes);

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
        $projetos = Project::where('id_grupo', $id_grupo)
            ->orderByDesc('id_projeto')
            ->get()
            ->map(function ($projeto) {
                $projeto->integrantes = DB::table('aluno_grupo')
                    ->where('id_grupo', $projeto->id_grupo)
                    ->count();
                return $projeto;
            });

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

        $projetos = DB::table('projeto as p')
            ->select('p.*')
            ->selectSub(function ($q) {
                $q->from('aluno_grupo as ag')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ag.id_grupo', 'p.id_grupo');
            }, 'integrantes')
            ->whereIn('p.id_grupo', function ($q) use ($id_aluno) {
                $q->select('id_grupo')->from('aluno_grupo')->where('id_aluno', $id_aluno);
            })
            ->orderByDesc('p.id_projeto')
            ->get();

        $projetos->each(fn($p) => $p->integrantes = (int) $p->integrantes);

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
            'titulo' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'area' => 'nullable|string|max:50',
            'data_criacao' => 'nullable|date',
            'status' => 'nullable|in:ativo,inativo',
            'objetivo' => 'nullable|string',
            'justificativa' => 'nullable|string',
            'id_grupo' => 'required|integer|exists:grupo,id_grupo',
            'id_orientador' => 'required|integer|exists:orientador,id_orientador',
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        // Upload opcional
        if ($request->hasFile('foto')) {
            $publicId = Str::slug($data['titulo']) . '-' . Str::random(6);
            $secureUrl = CloudiNary::upload($request->file('foto'), $publicId);
            $data['foto'] = $secureUrl;
        } else {
            $data['foto'] = null;
        }

        $project = Project::create([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'area' => $data['area'] ?? null,
            'data_criacao' => $data['data_criacao'] ?? now(),
            'status' => $data['status'] ?? 'ativo',
            'objetivo' => $data['objetivo'] ?? null,
            'justificativa' => $data['justificativa'] ?? null,
            'id_grupo' => $data['id_grupo'],
            'id_orientador' => $data['id_orientador'],
            'foto' => $data['foto'],
        ]);

        return response()->json([
            'message' => 'Projeto criado com sucesso',
            'data' => $project,
        ], 201);
    }

    /**
     * PUT /api/v1/projetos/{id}
     */
    public function update(Request $request, int $id)
    {
        $project = Project::findOrFail($id);

        $data = $request->validate([
            'titulo' => ['sometimes', 'string', 'max:255'],
            'descricao' => ['sometimes', 'string'],
            'area' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'max:50'],
            'objetivo' => ['nullable', 'string'],
            'justificativa' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remover_foto' => ['nullable', 'boolean'],
        ]);

        // Remoção explícita
        if ($request->boolean('remover_foto')) {
            CloudiNary::destroyByUrl($project->foto);
            $data['foto'] = null;
        }

        // Troca de foto
        if ($request->hasFile('foto')) {
            // apaga a antiga (se houver)
            CloudiNary::destroyByUrl($project->foto);

            // sobe a nova
            $tituloBase = $data['titulo'] ?? $project->titulo;
            $publicIdNew = Str::slug($tituloBase) . '-' . Str::random(6);
            $secureUrl = CloudiNary::upload($request->file('foto'), $publicIdNew);

            $data['foto'] = $secureUrl ?? $project->foto;
        }

        $project->update($data);

        return response()->json([
            'message' => 'Projeto atualizado com sucesso',
            'data' => $project->fresh(),
        ], 200);
    }

    public function definirGestorFinanceiro(Request $request, $id)
    {
        // validação básica
        $request->validate([
            'id_usuario' => 'required|integer',
            'tipo_usuario' => 'required|in:aluno,orientador',
        ]);

        $projeto = Project::findOrFail($id);

        // verifica se o usuário realmente tem conta Mercado Pago vinculada
        $conta = UsuarioContaMP::where('id_usuario', $request->id_usuario)
            ->where('tipo_usuario', $request->tipo_usuario)
            ->first();

        if (!$conta) {
            return response()->json([
                'erro' => 'Este usuário não possui uma conta Mercado Pago vinculada à plataforma.'
            ], 400);
        }

        // verifica se há access token salvo
        if (empty($conta->mp_access_token)) {
            return response()->json([
                'erro' => 'A conta Mercado Pago vinculada não possui um access token ativo. ' .
                    'Peça ao usuário para refazer a vinculação.'
            ], 400);
        }

        // descriptografa token
        try {
            $accessToken = \Illuminate\Support\Facades\Crypt::decryptString($conta->mp_access_token);
        } catch (\Exception $e) {
            return response()->json([
                'erro' => 'Falha ao descriptografar o access token. Refazer a vinculação pode resolver.'
            ], 400);
        }


        $mp = new MercadoPagoProvider();
        // valida a conta Mercado Pago (usa a função auxiliar)
        if ($erro = $mp->validarContaMercadoPago($accessToken)) {
            return $erro;
        }

        // se passou por todas as verificações, atualiza o projeto
        $projeto->update([
            'id_gestor_financeiro' => $request->id_usuario,
            'tipo_gestor' => $request->tipo_usuario,
        ]);

        return response()->json([
            'mensagem' => 'Gestor financeiro vinculado com sucesso!',
            'projeto' => $projeto,
        ]);
    }


}
