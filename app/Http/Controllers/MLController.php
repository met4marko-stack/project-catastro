<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class MLController extends Controller
{
    public function reentrenarModelo(Request $request)
    {
        try {
            // 1. Extraer datos frescos de la BD y generar CSV
            Artisan::call('ml:exportar-datos');

            // 2. Ejecutar el script de Python para reentrenar (Ajusta la ruta según tu sistema operativo)
            // Si estás en Windows usa 'python', en Linux/Mac usa 'python3'
            $pythonScriptPath = base_path('../python_catastro/modules/proyecto_ml/train_model.py');
            $process = Process::fromShellCommandline("python \"{$pythonScriptPath}\"");
            
            // Opcional: Aumentar el tiempo de espera si tienes miles de datos
            $process->setTimeout(120); 
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return back()->with('success', '¡Inteligencia Artificial actualizada! El modelo ha aprendido de los datos más recientes.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al reentrenar el modelo: ' . $e->getMessage());
        }
    }
}