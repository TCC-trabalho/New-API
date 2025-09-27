<?php

namespace App\Http\Controllers;

use App\Providers\CloudiNary;
use App\Providers\CompanyRatingService;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyController extends Controller
{

    /**
     * GET /api/v1/empresas
     */
    public function index(Request $request)
    {
        $empresas = Company::all();
        $empresas->transform(function ($c) {
            $c->avaliacao = CompanyRatingService::getAverageForCompany($c->id_empresa);
            return $c;
        });
        return response()->json($empresas, 200);
    }

    /**
     * GET /api/v1/empresa/{id}
     */
    public function show(int $id)
    {
        $company = Company::where('id_empresa', $id)->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        $company->avaliacao = CompanyRatingService::getAverageForCompany($company->id_empresa);

        return response()->json($company, 200);
    }

    /**
     * GET /api/v1/empresas/{id}/projetos
     */
    public function showWithProjetos(int $idEmpresa)
    {
        // 1) Empresa
        $empresa = DB::table('empresa')->where('id_empresa', $idEmpresa)->first();
        if (!$empresa) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        $empresa->avaliacao = CompanyRatingService::getAverageForCompany($empresa->id_empresa);

        $patrocinios = DB::table('patrocinio')
            ->where('id_empresa', $idEmpresa)
            ->select('id_projeto', DB::raw('MAX(data_patrocinio) as data_patrocinio'), DB::raw('MAX(tipo_apoio) as tipo_apoio'))
            ->groupBy('id_projeto')
            ->orderByDesc('data_patrocinio')
            ->get();

        if ($patrocinios->isEmpty()) {
            return response()->json([
                'empresa' => $empresa,
                'total_projetos' => 0,
                'projetos_patrocinados' => [],
            ], 200);
        }

        $projectController = app(ProjectController::class);
        $projetos = [];

        foreach ($patrocinios as $p) {
            $resp = $projectController->show((int) $p->id_projeto);

            $payload = method_exists($resp, 'getData') ? $resp->getData(true) : null;

            $projeto = is_array($payload)
                ? ($payload['projeto'] ?? $payload)
                : null;

            if ($projeto) {
                $projeto['data_patrocinio'] = $p->data_patrocinio;
                $projeto['tipo_apoio'] = $p->tipo_apoio;
                $projetos[] = $projeto;
            }
        }

        return response()->json([
            'empresa' => $empresa,
            'total_projetos' => count($projetos),
            'projetos_patrocinados' => $projetos,
        ], 200);
    }


    /**
     * GET /api/v1/empresas/{id}/projetos-patrocinados
     */
    public function listarProjetosPatrocinados($id_empresa)
    {
        $company = Company::find($id_empresa);
        if (!$company) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        $projetos = $company->projects()
            ->withPivot('data_patrocinio', 'tipo_apoio', 'valorPatrocinio', 'mensagem')
            ->orderBy('projeto.id_projeto', 'desc')
            ->get()
            ->unique('id_projeto')
            ->values();

        return response()->json([
            'total_projetos' => $projetos->count(),
            'projetos_patrocinados' => $projetos,
        ], 200);
    }

    /**
     * POST /api/v1/empresas
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'setor' => 'nullable|string|max:100',
            'cnpj' => 'required|digits:14|unique:empresa,cnpj',
            'endereco' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:150|unique:empresa,email',
            'telefone' => 'nullable|string|max:20',
            'senha' => 'required|string|min:6|max:255',
        ]);

        $data['senha'] = bcrypt($data['senha']);

        $company = Company::create($data);
        return response()->json($company, 201);
    }

    /**
     * PUT /api/v1/empresas/{id}
     */
    public function update(Request $request, int $id)
    {
        $company = Company::findOrFail($id);

        $data = $request->validate([
            'nome' => ['sometimes', 'string', 'max:150'],
            'descricao' => ['sometimes', 'string'],
            'email' => ['sometimes', 'email', 'max:150'],
            'site' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:ativo,inativo'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remover_foto' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remover_foto')) {
            CloudiNary::destroyByUrl($company->foto);
            $data['foto'] = null;
        }

        if ($request->hasFile('foto')) {
            CloudiNary::destroyByUrl($company->foto);

            $nomeBase = $data['nome'] ?? $company->nome ?? 'empresa';
            $publicId = Str::slug($nomeBase) . '-' . Str::random(6);
            $secureUrl = CloudiNary::upload($request->file('foto'), $publicId, 'tcc/empresas');

            $data['foto'] = $secureUrl ?? $company->foto;
        }

        $company->update($data);

        return response()->json([
            'message' => 'Empresa atualizada com sucesso',
            'data' => $company->fresh(),
        ], 200);
    }


    /**
     * DELETE /api/v1/empresas/{id}
     */
    public function destroy($id)
    {
        $company = Company::find($id);
        if (!$company) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        $company->delete();
        return response()->json(null, 204);
    }
}