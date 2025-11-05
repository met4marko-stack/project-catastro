<?php

namespace App\Services;

class IncomeProjectionService
{
    /**
     * Calcula la proyección de ingresos para el próximo año usando Regresión Lineal.
     *
     * @param array $historicalData Un array asociativo de [año => ingresos].
     * @return array|null Un array con los datos para el gráfico y la proyección, o null si no hay suficientes datos.
     */
    public function calculateProjection(array $historicalData): ?array
    {
        // Necesitamos al menos 2 puntos para una línea.
        if (count($historicalData) < 2) {
            return null;
        }

        $n = count($historicalData);
        $years = array_keys($historicalData);
        $incomes = array_values($historicalData);
        
        // Para simplificar el cálculo, usamos índices (0, 1, 2) en lugar de los años (2023, 2024, 2025)
        $x = range(0, $n - 1);
        $y = $incomes;

        // Fórmulas de Regresión Lineal Simple
        $sum_x = array_sum($x);
        $sum_y = array_sum($y);
        $sum_xy = 0;
        $sum_x2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sum_xy += ($x[$i] * $y[$i]);
            $sum_x2 += ($x[$i] * $x[$i]);
        }

        // Calcular la pendiente (m) y la intercepción (b) de la recta: y = mx + b
        $denominator = ($n * $sum_x2) - ($sum_x * $sum_x);
        if ($denominator == 0) {
            return null; // Evitar división por cero
        }
        
        $slope = (($n * $sum_xy) - ($sum_x * $sum_y)) / $denominator;
        $intercept = ($sum_y - $slope * $sum_x) / $n;

        // Predecir el valor para el siguiente índice (n)
        $nextYearIndex = $n;
        $projectedIncome = $slope * $nextYearIndex + $intercept;
        
        $nextYear = end($years) + 1;

        return [
            'labels' => $years,
            'historical_values' => $incomes,
            'projected_year' => $nextYear,
            'projected_income' => round($projectedIncome, 2),
        ];
    }
}

