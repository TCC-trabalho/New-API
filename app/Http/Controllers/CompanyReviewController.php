<?php
// app/Http/Controllers/CompanyReviewController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\CompanyReview;

class CompanyReviewController extends Controller
{
    /**
     * POST /api/v1/empresas/{empresa}/avaliacoes
     */
    public function store(Request $request, $empresa)
    {
        $company = Company::find($empresa);
        if (!$company) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        $data = $request->validate([
            'estrelas' => 'required|integer|between:1,5',
            'comentario' => 'nullable|string',
        ]);

        $data['id_empresa'] = $empresa;
        $review = CompanyReview::create($data);

        return response()->json($review, 201);
    }

    /**
     * PUT /api/v1/empresas/{empresa}/avaliacoes/{avaliacao}
     */
    public function update(Request $request, $empresa, $avaliacao)
    {
        $company = Company::find($empresa);
        if (!$company) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        $review = CompanyReview::find($avaliacao);
        if (!$review || $review->id_empresa != $empresa) {
            return response()->json(['message' => 'Avaliação não encontrada'], 404);
        }

        $data = $request->validate([
            'estrelas' => 'sometimes|required|integer|between:1,5',
            'comentario' => 'nullable|string',
        ]);

        $review->update($data);
        return response()->json($review);
    }

    /**
     * DELETE /api/v1/empresas/{empresa}/avaliacoes/{avaliacao}
     */
    public function destroy($empresa, $avaliacao)
    {
        $company = Company::find($empresa);
        if (!$company) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        $review = CompanyReview::find($avaliacao);
        if (!$review || $review->id_empresa != $empresa) {
            return response()->json(['message' => 'Avaliação não encontrada'], 404);
        }

        $review->delete();
        return response()->json(null, 204);
    }
}