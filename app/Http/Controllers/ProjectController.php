<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Str;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
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
    
        $projetos->each(fn ($p) => $p->integrantes = (int) $p->integrantes);
    
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
        // 1) Validação básica + imagem opcional (até 5MB)
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

        // 2) Configuração do Cloudinary (v3) p/ este request
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);

        // 3) Upload (se veio arquivo). Se falhar, lança exceção padrão.
        if ($request->hasFile('foto')) {
            $publicId = Str::slug($data['titulo']) . '-' . Str::random(6);

            $upload = (new UploadApi())->upload(
                $request->file('foto')->getRealPath(),
                [
                    'folder' => 'tcc/projetos',
                    'public_id' => $publicId,
                    'overwrite' => true,
                    'resource_type' => 'image',
                    // otimização web ao servir:
                    'transformation' => [['quality' => 'auto', 'fetch_format' => 'auto']],
                ]
            );

            $data['foto'] = $upload['secure_url'] ?? null;
        } else {
            $data['foto'] = null;
        }

        // 4) Criar e responder
        $project = Project::create([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'],
            'area' => $data['area'],
            'data_criacao' => $data['data_criacao'],
            'status' => $data['status'],
            'objetivo' => $data['objetivo'],
            'justificativa' => $data['justificativa'],
            'id_grupo' => $data['id_grupo'],
            'id_orientador' => $data['id_orientador'],
            'foto' => $data['foto'], // já vem null ou URL
        ]);

        return response()->json([
            'message' => 'Projeto criado com sucesso',
            'data' => $project,
        ], 201);
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
