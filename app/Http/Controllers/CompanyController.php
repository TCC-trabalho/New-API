<?php
// app/Http/Controllers/CompanyController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{

    /**
     * GET /api/v1/empresas
     */
    public function index(Request $request)
    {
        $empresas = Company::all();
        return response()->json($empresas, 200);
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

        $patrocinios = DB::table('patrocinio')
            ->where('id_empresa', $idEmpresa)
            ->select('id_projeto', 'data_patrocinio', 'tipo_apoio')
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
                $projeto['tipo_apoio']      = $p->tipo_apoio;
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
    public function update(Request $request, $id)
    {
        $company = Company::find($id);
        if (!$company) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        $data = $request->validate([
            'nome' => 'sometimes|required|string|max:150',
            'foto' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'setor' => 'nullable|string|max:100',
            'cnpj' => "sometimes|required|digits:14|unique:empresa,cnpj,{$id},id_empresa",
            'endereco' => 'nullable|string|max:200',
            'email' => "nullable|email|max:150|unique:empresa,email,{$id},id_empresa",
            'telefone' => 'nullable|string|max:20',
            'senha' => 'nullable|string|min:6|max:255',
        ]);

        if (isset($data['senha'])) {
            $data['senha'] = bcrypt($data['senha']);
        }

        $company->update($data);
        return response()->json($company);
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