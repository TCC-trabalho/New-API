<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstituitionController extends Controller
{
    // GET - Retornar todas as instituições
    public function index()
    {
        try {
            $instituicoes = DB::table('instituicao')->get();

            return response()->json([
                'success' => true,
                'data' => $instituicoes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar instituições',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
