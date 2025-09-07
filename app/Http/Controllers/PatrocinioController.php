<?php

namespace App\Http\Controllers;

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
}
