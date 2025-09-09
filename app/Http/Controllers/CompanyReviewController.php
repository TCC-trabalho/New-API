<?php

namespace App\Http\Controllers;

use App\Providers\CompanyRatingService;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\CompanyReview;
use Illuminate\Support\Facades\Validator;

class CompanyReviewController extends Controller
{
    /**
     * POST /api/v1/empresas/{empresa}/avaliacoes
     */
    public function store(Request $request, int $empresa)
    {
        // valida empresa
        $company = Company::find($empresa);
        if (!$company) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        // valida payload (apenas estrelas)
        $validator = Validator::make($request->all(), [
            'estrelas' => ['required', 'integer', 'between:1,5'],
        ], [
            'estrelas.required' => 'O campo estrelas é obrigatório.',
            'estrelas.integer' => 'O campo estrelas deve ser um número inteiro.',
            'estrelas.between' => 'O campo estrelas deve estar entre 1 e 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        // cria avaliação (data do dia vinda do backend)
        $review = CompanyReview::create([
            'id_empresa' => $empresa,
            'estrelas' => (int) $request->input('estrelas'),
            'data_avaliacao' => now()->toDateString(), // YYYY-MM-DD
        ]);

        return response()->json([
            'message' => 'Avaliação registrada com sucesso.',
            'data' => $review,
        ], 201);
    }

    /**
     * GET /api/v1/empresas/{empresa}/avaliacoes/media
     */
    public function average(int $empresa)
    {
        [$avg, $count] = CompanyRatingService::getAverageAndCount($empresa);

        return response()->json([
            'id_empresa' => $empresa,
            'avaliacao' => $avg,     // média das estrelas
            'total_avaliacoes' => $count,
        ], 200);
    }
}
