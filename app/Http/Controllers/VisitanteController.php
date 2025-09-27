<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Visitante;
use Illuminate\Support\Facades\DB;

class VisitanteController extends Controller
{
    /**
     * GET /api/v1/visitantes
     */
    public function index()
    {
        $visitantes = Visitante::all();
        return response()->json($visitantes);
    }


    /**
     * GET /api/v1/visitantes/{id}/projetos
     */
    public function showWithProjetos(int $idVisitante)
    {
        // 1) Visitante
        $visitante = DB::table('visitante')->where('id_visitante', $idVisitante)->first();
        if (!$visitante) {
            return response()->json(['message' => 'Visitante não encontrado'], 404);
        }

        $apoios = DB::table('apoio')
            ->where('id_visitante', $idVisitante)
            ->select(
                'id_projeto',
                DB::raw('MAX(data_apoio) AS data_apoio'),
                DB::raw('MAX(tipo_apoio) AS tipo_apoio')
            )
            ->groupBy('id_projeto')
            ->orderByDesc('data_apoio')
            ->get();

        if ($apoios->isEmpty()) {
            return response()->json([
                'visitante' => $visitante,
                'total_projetos' => 0,
                'projetos_patrocinados' => [],
            ], 200);
        }

        $projectController = app(ProjectController::class);
        $projetos = [];

        foreach ($apoios as $p) {
            $resp = $projectController->show((int) $p->id_projeto);

            $payload = method_exists($resp, 'getData') ? $resp->getData(true) : null;

            $projeto = is_array($payload)
                ? ($payload['projeto'] ?? $payload)
                : null;

            if ($projeto) {
                $projeto['data_apoio'] = $p->data_apoio;
                $projeto['tipo_apoio'] = $p->tipo_apoio;
                $projetos[] = $projeto;
            }
        }

        return response()->json([
            'visitante' => $visitante,
            'total_projetos' => count($projetos),
            'projetos_patrocinados' => $projetos,
        ], 200);
    }

    /**
     * GET /api/v1/visitantes/{id}/projetos-apoiados
     */
    public function showProjetosApoiados($id_visitante)
    {
        $visitante = Visitante::find($id_visitante);
        if (!$visitante) {
            return response()->json(['message' => 'Visitante não encontrado'], 404);
        }
        $projetos = $visitante->projects()
            ->withPivot('data_apoio', 'tipo_apoio', 'valorApoio', 'mensagem')
            ->orderBy('projeto.id_projeto', 'desc')
            ->get()
            ->unique('id_projeto')
            ->values();

        return response()->json([
            'total_projetos' => $projetos->count(),
            'projetos_apoiados' => $projetos,
        ], 200);
    }

    /**
     * POST /api/v1/visitantes
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:100',
            'biografia' => 'nullable|string',
            'email' => 'required|email|max:150|unique:visitante,email',
            'nomeUsuario' => 'required|string|max:100',
            'senha' => 'required|string|min:6|max:255',
        ]);

        // hash da senha
        $data['senha'] = bcrypt($data['senha']);

        $visitante = Visitante::create($data);
        return response()->json($visitante, 201);
    }


    /**
     * PUT /api/v1/visitantes/{id}
     */
    public function update(Request $request, $id)
    {
        $visitante = Visitante::find($id);
        if (!$visitante) {
            return response()->json(['message' => 'Visitante não encontrado'], 404);
        }

        $data = $request->validate([
            'biografia' => 'nullable|string',
            'nomeUsuario' => 'required|string|max:100',
            'senha' => 'nullable|string|min:6|max:255',
        ]);

        if (isset($data['senha'])) {
            $data['senha'] = bcrypt($data['senha']);
        }

        $visitante->update($data);
        return response()->json($visitante);
    }



    /**
     * DELETE /api/v1/visitantes/{id}
     */
    public function destroy($id)
    {
        $visitante = Visitante::find($id);
        if (!$visitante) {
            return response()->json(['message' => 'Visitante não encontrado'], 404);
        }

        $visitante->delete();
        return response()->json(null, 204);
    }
}