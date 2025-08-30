<?php
// app/Http/Controllers/CompanyController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;

class CompanyController extends Controller
{
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