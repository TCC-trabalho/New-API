<?php

namespace App\Providers;

use App\Models\CompanyReview;

class CompanyRatingService
{
    /**
     * Retorna a média das estrelas (0.0 se não houver avaliações).
     */
    public static function getAverageForCompany(int $idEmpresa): float
    {
        $avg = CompanyReview::where('id_empresa', $idEmpresa)->avg('estrelas');
        return $avg ? round((float)$avg, 2) : 0.0;
    }

    /**
     * Retorna também o total de avaliações.
     */
    public static function getAverageAndCount(int $idEmpresa): array
    {
        $avg   = self::getAverageForCompany($idEmpresa);
        $count = CompanyReview::where('id_empresa', $idEmpresa)->count();

        return [$avg, $count];
    }
}
