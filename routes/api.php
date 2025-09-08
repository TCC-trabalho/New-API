<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\OrientadorController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatrocinioController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ========= Versão 1 da API =========
Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    //
    // — GET —
    //
    Route::get('projetos', [ProjectController::class, 'index']);
    Route::get('projetos-controlados', [ProjectController::class, 'projetoControlado']);
    Route::get('projetos/{id}', [ProjectController::class, 'show']);
    Route::get('projetos/area/{area}', [ProjectController::class, 'getByArea']);
    Route::get('/projetos/aluno/{id_aluno}', [ProjectController::class, 'listarPorAluno']);
    Route::get('/projetos/orientador/{id_orientador}', [ProjectController::class, 'listarPorOrientador']);
    Route::get('/projetos/grupo/{id_grupo}', [ProjectController::class, 'listarPorGrupo']);
    Route::get('/empresas/{id}/projetos-patrocinados', [CompanyController::class, 'listarProjetosPatrocinados']);


    Route::get('empresas', [CompanyController::class, 'index']);
    Route::get('/empresa/{id}', [CompanyController::class, 'show']);
    Route::get('/empresas/{id}/projetos', [CompanyController::class, 'showWithProjetos']);

    Route::get('grupos', [GroupController::class, 'index']);
    Route::get('alunos', [StudentController::class, 'index']);
    Route::get('orientadores', [OrientadorController::class, 'index']);

    //
    // — POST —
    //
    Route::post('projetos', [ProjectController::class, 'store']);
    Route::post('orientadores', [OrientadorController::class, 'store']);
    Route::post('alunos', [StudentController::class, 'store']);
    Route::post('grupos', [GroupController::class, 'store']);
    Route::post('/grupo/{id}/adicionar-integrantes', [GroupController::class, 'adicionarIntegrantes']);
    Route::post('empresas', [CompanyController::class, 'store']);
    Route::post('empresas/{empresa}/avaliacoes', [CompanyReviewController::class, 'store']);
    Route::post('/patrocinios', [PatrocinioController::class, 'store']);

    //
    // — PUT —
    //
    Route::put('projetos/{id}', [ProjectController::class, 'update']);
    Route::put('orientador/{id}', [OrientadorController::class, 'update']);
    Route::put('aluno/{id}', [StudentController::class, 'update']);
    Route::put('grupos/{id}', [GroupController::class, 'update']);
    Route::put('empresa/{id}', [CompanyController::class, 'update']);
    Route::put('empresas/{empresa}/avaliacoes/{avaliacao}', [CompanyReviewController::class, 'update']);

    //
    // — DELETE —
    //
    Route::delete('projetos/{id}', [ProjectController::class, 'destroy']);
    Route::delete('orientadores/{id}', [OrientadorController::class, 'destroy']);
    Route::delete('alunos/{id}', [StudentController::class, 'destroy']);
    Route::delete('grupos/{id}', [GroupController::class, 'destroy']);
    Route::delete('empresas/{id}', [CompanyController::class, 'destroy']);
    Route::delete('empresas/{empresa}/avaliacoes/{avaliacao}', [CompanyReviewController::class, 'destroy']);
});

