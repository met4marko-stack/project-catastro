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
        // 1. Datos históricos fijos (modificables en código por el usuario)
        $historicalData = [
            2022 => 45000.00,  
            2023 => 52000.00,  
            2024 => 55000.00,  
            2025 => 58000.00,  
        ];

        // 2. Calcular la proyección para la gestión vigente (2026) a partir de los datos fijos
        $projection = $projectionService->calculateProjection($historicalData);
        $projectedIncome2026 = $projection['projected_income'] ?? 0.00;

        // 3. Obtener el ingreso real acumulado dinámicamente de la base de datos para la gestión 2026
        // Se calcula sumando el costo de los trámites en estado ENTREGADO (ID 6) creados en el año 2026
        $realIncome2026 = (float)\App\Models\Tramite::where('estado_id', 6)
            ->whereYear('fecha_ingreso', 2026)
            ->join('tramite_tipos', 'tramites.tramite_tipo_id', '=', 'tramite_tipos.id')
            ->sum('tramite_tipos.costo');

        // 4. Preparar los datos estructurados para Chart.js
        $years = array_keys($historicalData);
        $incomes = array_values($historicalData);

        // Los labels incluirán el año 2026
        $labels = array_merge($years, [2026]);

        // El dataset histórico/real incluirá el histórico fijo y el valor dinámico del 2026
        $historicalDataset = array_merge($incomes, [$realIncome2026]);

        // La proyección se conecta desde el 2025 (último valor real) hasta el 2026 proyectado
        $projectionDataset = array_fill(0, count($incomes) - 1, null);
        $projectionDataset[] = end($incomes);          // 2025 (58000)
        $projectionDataset[] = $projectedIncome2026;   // 2026 (Proyectado)

        $chartData = [
            'labels'     => $labels,
            'historical' => $historicalDataset,
            'projection' => $projectionDataset,
        ];

        return view('home', [
            'projectedIncome' => $projectedIncome2026,
            'projectedYear'   => 2026,
            'chartData'       => $chartData,
        ]);
    }
}
