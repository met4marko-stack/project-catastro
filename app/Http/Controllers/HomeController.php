<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\PdfToImage\Pdf;
use App\Services\IncomeProjectionService;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(IncomeProjectionService $projectionService)
    {
        $historicalData = [
            2022 => 45000,  
            2023 => 52000,  
            2024 => 55000,  
        ];

        $projection = $projectionService->calculateProjection($historicalData);

        // datos para Chart.js
        $chartData = [
            'labels' => [],
            'historical' => [],
            'projection' => [],
        ];
        
        if ($projection) {
            $chartData['labels'] = array_merge($projection['labels'], [$projection['projected_year']]);
            $chartData['historical'] = $projection['historical_values'];
            // Para el gráfico, la proyección necesita 'nulls' para los años históricos
            $chartData['projection'] = array_fill(0, count($projection['historical_values']), null);
            $chartData['projection'][] = $projection['projected_income'];
        }

        return view('home', [
            'projectedIncome' => $projection['projected_income'] ?? 0,
            'projectedYear'   => $projection['projected_year'] ?? date('Y') + 1, // <-- AÑADIR ESTA LÍNEA
            'chartData' => $chartData,
        ]);
    }
}
