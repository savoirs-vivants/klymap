<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Klymap API",
    version: "1.0.0",
    description: "Documentation de l'API Klymap : capteurs, points, témoins et export de données pour QGIS."
)]
abstract class Controller
{
    //
}
