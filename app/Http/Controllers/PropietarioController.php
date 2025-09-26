<?php

namespace App\Http\Controllers;

use App\Models\Propietario;
use App\Models\Persona;
use App\Models\Municipio;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\PdfToImage\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Illuminate\Support\Str;
use Intervention\Image\Exceptions\NotReadableException;

class PropietarioController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $propietarios = collect();

        if ($user->hasRole('Super-Admin')) {
            $propietarios = Propietario::with(['persona', 'municipio'])->get();
        } else {
            $propietarios = Propietario::with(['persona', 'municipio'])
                ->where('municipio_id', $user->municipio_id)
                ->get();
        }

        return view('admin.propietarios.index', compact('propietarios'));
    }

    public function create()
    {
        $municipios = Municipio::all();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD'];
        return view('admin.propietarios.create', compact('municipios', 'expedidoOptions'));
    }

    public function store(Request $request)
    {
        //\xdebug_info();
        die('Revisando la información de Xdebug...');
        $request->validate([
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'carnet' => 'required|string|max:255|unique:personas,carnet',
            'ci_fecha_caducidad' => 'nullable|date|required_if:ci_es_indefinido,false',
            'ci_es_indefinido' => 'nullable|boolean',
            'municipio_id' => Auth::user()->hasRole('Super-Admin') ? 'required|exists:municipios,id' : 'nullable',
        ]);

        try {
            DB::beginTransaction();

            $persona = Persona::create([
                'nombre' => $request->nombre,
                'primer_apellido' => $request->primer_apellido,
                'segundo_apellido' => $request->segundo_apellido,
                'carnet' => $request->carnet,
                'expedido' => $request->expedido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'ci_es_indefinido' => $request->has('ci_es_indefinido'),
                'ci_fecha_caducidad' => $request->has('ci_es_indefinido') ? null : $request->ci_fecha_caducidad,
            ]);

            $municipio_id = Auth::user()->hasRole('Super-Admin')
                ? $request->municipio_id
                : Auth::user()->municipio_id;

            Propietario::create([
                'persona_id' => $persona->id,
                'municipio_id' => $municipio_id,
                'estado' => true,
            ]);

            DB::commit();
            return redirect()->route('admin.propietarios.index')->with('success', 'Propietario registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error. Es posible que el carnet ya exista o la persona ya esté registrada como propietaria en este municipio.']);
        }
    }

    public function edit(Propietario $propietario)
    {
        $municipios = Municipio::all();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD'];
        return view('admin.propietarios.edit', compact('propietario', 'municipios', 'expedidoOptions'));
    }

    public function update(Request $request, Propietario $propietario)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'carnet' => 'required|string|max:255|unique:personas,carnet,' . $propietario->persona_id,
            'ci_fecha_caducidad' => 'nullable|date|required_if:ci_es_indefinido,false',
            'ci_es_indefinido' => 'nullable|boolean',
            'municipio_id' => Auth::user()->hasRole('Super-Admin') ? 'required|exists:municipios,id' : 'nullable',
            'estado' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();
            $propietario->persona->update([
                'nombre' => $request->nombre,
                'primer_apellido' => $request->primer_apellido,
                'segundo_apellido' => $request->segundo_apellido,
                'carnet' => $request->carnet,
                'expedido' => $request->expedido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'ci_es_indefinido' => $request->has('ci_es_indefinido'),
                'ci_fecha_caducidad' => $request->has('ci_es_indefinido') ? null : $request->ci_fecha_caducidad,
            ]);

            $propietario->estado = $request->estado;
            if (Auth::user()->hasRole('Super-Admin')) {
                $propietario->municipio_id = $request->municipio_id;
            }
            $propietario->save();

            DB::commit();
            return redirect()->route('admin.propietarios.index')->with('success', 'Propietario actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al actualizar.']);
        }
    }

    /**
     * Desactiva un propietario (borrado lógico).
     */
    public function destroy(Propietario $propietario)
    {
        $propietario->estado = false;
        $propietario->save();

        return redirect()->route('admin.propietarios.index')->with('success', 'Propietario desactivado exitosamente.');
    }

    /**
     * Reactiva un propietario.
     */
    public function restore($id)
    {
        // Usamos findOrFail para asegurarnos de que el propietario exista
        $propietario = Propietario::findOrFail($id);
        $propietario->estado = true;
        $propietario->save();

        return redirect()->route('admin.propietarios.index')->with('success', 'Propietario reactivado exitosamente.');
    }

    /*public function procesarOcr(Request $request)
    {
        $request->validate(['documento_ci' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096']);
        $file = $request->file('documento_ci');
        $imagePath = $file->getPathname();
        $tempDir = storage_path('app/public/ocr_temp');

        if ($file->getMimeType() == 'application/pdf') {
            try {
                if (!file_exists($tempDir)) { mkdir($tempDir, 0755, true); }
                $imagePath = $tempDir . '/' . uniqid() . '.jpg';
                (new Pdf($file->getPathname()))->save($imagePath);
            } catch (\Exception $e) {
                // --- ERROR MEJORADO ---
                return response()->json(['error' => 'Error al convertir PDF: ' . $e->getMessage()], 500);
            }
        }
        
        $manager = ImageManager::withDriver(new ImagickDriver());
        $manager->read($imagePath)->greyscale()->contrast(40)->save($imagePath);

        try {
            $text = (new TesseractOCR($imagePath))->lang('spa')->run();
            $data = $this->parseOcrData($text);
            
            if (file_exists($imagePath) && str_contains($imagePath, 'ocr_temp')) { 
                unlink($imagePath);
            }
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar con Tesseract: ' . $e->getMessage()], 500);
        }
    }

    private function parseOcrData($text)
    {
        $data = [];
        if (preg_match('/(Nombres|Nombre)[:\s\n]+([\w\sÁÉÍÓÚÑ]+)/i', $text, $matches)) { $data['nombre'] = trim($matches[2]); }
        if (preg_match('/(Apellidos|Apellido)[:\s\n]+([\w\sÁÉÍÓÚÑ]+)/i', $text, $matches)) {
            $apellidos = explode(' ', trim($matches[2]), 2);
            $data['primer_apellido'] = $apellidos[0] ?? '';
            $data['segundo_apellido'] = $apellidos[1] ?? '';
        }
        if (empty($data['nombre']) && preg_match('/(?:pertenece\sA:)\s*([^\n\r]+)/i', $text, $matches)) {
            $fullName = explode(' ', trim($matches[1]));
            $data['nombre'] = array_shift($fullName);
            $data['primer_apellido'] = array_shift($fullName) ?? '';
            $data['segundo_apellido'] = implode(' ', $fullName);
        }
        if (preg_match('/(Cédula de Identidad|No\.|N°)\s*([\d\.-]+)/i', $text, $matches)) {
            $data['carnet'] = str_replace(['.', '-'], '', trim($matches[2]));
        }
        if (preg_match('/(Fecha de Nacimiento|Nacido el)\s*(\d{1,2}(?:\/| de )\w+(?:\/| de )\d{4})/i', $text, $matches)) {
            $data['fecha_nacimiento'] = $this->parseSpanishDate(trim($matches[2]));
        }
        if (preg_match('/(Fecha de Expiración|Válida hasta el)\s*(INDEFINIDO|\d{1,2}(?:\/| de )\w+(?:\/| de )\d{4})/i', $text, $matches)) {
            $expiracion = trim($matches[2]);
            if (strtoupper($expiracion) === 'INDEFINIDO') {
                $data['ci_es_indefinido'] = true;
                $data['ci_fecha_caducidad'] = null;
            } else {
                $data['ci_es_indefinido'] = false;
                $data['ci_fecha_caducidad'] = $this->parseSpanishDate($expiracion);
            }
        }
        return $data;
    }

    private function parseSpanishDate($dateString)
    {
        if (strpos($dateString, '/') !== false) {
            $fecha = \DateTime::createFromFormat('d/m/Y', $dateString);
            return $fecha ? $fecha->format('Y-m-d') : null;
        }
        $months = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04', 'mayo' => '05', 'junio' => '06',
            'julio' => '07', 'agosto' => '08', 'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12'
        ];
        $dateString = str_ireplace(array_keys($months), array_values($months), strtolower($dateString));
        $dateString = str_replace(' de ', '/', $dateString);
        $fecha = \DateTime::createFromFormat('d/m/Y', $dateString);
        return $fecha ? $fecha->format('Y-m-d') : null;
    }*/

    public function procesarOcr(Request $request)
    {
        $request->validate(['documento_ci' => 'required|file|mimes:pdf,jpg,jpeg,png|max:8192']);

        $file = $request->file('documento_ci');
        $tempDir = public_path('storage/ocr_temp'); 
        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);

        $originalPath = $tempDir . '/' . uniqid('ocr_original_') . '.' . $file->guessExtension();
        $file->move($tempDir, basename($originalPath));

        try {
            $imagePath = $originalPath;

            // Si es un PDF, conviértelo a imagen
            if (mime_content_type($originalPath) === 'application/pdf') {
                $imagePath = $tempDir . '/' . uniqid('pdfimg_') . '.jpg';
                (new Pdf($originalPath))->resolution(300)->save($imagePath);
                @unlink($originalPath);

                if (!file_exists($imagePath) || filesize($imagePath) === 0) {
                    throw new \Exception('La conversión de PDF a imagen falló.');
                }
            }

            // Genera variantes de la imagen para mejorar la precisión
            $imageVariants = $this->generateImageVariants($imagePath, $tempDir);

            // Ejecuta Tesseract en todas las variantes y combina el texto
            $combinedText = $this->runOcrOnVariants($imageVariants);

            // Analiza el texto combinado para extraer los datos
            $data = $this->parseOcrData($combinedText);

            // Limpia todos los archivos de imagen temporales
            foreach ($imageVariants as $path) {
                if (file_exists($path)) @unlink($path);
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error procesando OCR: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crea diferentes versiones de una imagen para mejorar el OCR.
     */
    private function generateImageVariants(string $path, string $tempDir): array
    {
        try {
            $variants = [$path];
            $manager = ImageManager::withDriver(new ImagickDriver());

            // Se lee la imagen aquí. Si falla, se captura el error.
            $image = $manager->read($path);

            // Variante 1: Escala de grises y contraste
            $v1Path = $tempDir . '/' . uniqid('var_contrast_') . '.jpg';
            $manager->read($path)->greyscale()->contrast(30)->save($v1Path);
            $variants[] = $v1Path;

            // Variante 2: Binarización
            $v2Path = $tempDir . '/' . uniqid('var_binary_') . '.jpg';
            $manager->read($path)->greyscale()->contrast(10)->brightness(10)->gamma(1.2)->save($v2Path);
            $variants[] = $v2Path;

            return $variants;
        } catch (Exception $e) {
            // Este error es específico de Intervention Image si no puede leer el archivo.
            throw new \Exception('Unable to decode input. El archivo de imagen generado desde el PDF podría estar corrupto.');
        }
    }

    /**
     * Ejecuta Tesseract en múltiples imágenes y combina el texto resultante.
     */
    private function runOcrOnVariants(array $paths): string
    {
        $fullText = '';
        foreach ($paths as $path) {
            try {
                $fullText .= (new TesseractOCR($path))->lang('spa')->run() . "\n";
            } catch (\Exception $e) {
                // Ignorar si una variante falla
            }
        }
        return $fullText;
    }

    /**
     * Analiza el texto crudo del OCR y extrae los datos del carnet de identidad.
     */
    private function parseOcrData(string $text)
    {
        $data = [];

        // Extraer Nombres y Apellidos (maneja múltiples formatos)
        if (preg_match('/(?:Nombres|Nombre)[:\s\n]+([^\n\r]+)/i', $text, $matches)) {
            $data['nombre'] = trim($matches[1]);
        }
        if (preg_match('/(?:Apellidos|Apellido)[:\s\n]+([^\n\r]+)/i', $text, $matches)) {
            $apellidos = explode(' ', trim($matches[1]), 2);
            $data['primer_apellido'] = $apellidos[0] ?? '';
            $data['segundo_apellido'] = $apellidos[1] ?? '';
        }
        if (empty($data['nombre']) && preg_match('/(?:pertenece\sA:)\s*([^\n\r]+)/i', $text, $matches)) {
            $fullName = explode(' ', trim($matches[1]));
            $data['nombre'] = array_shift($fullName) ?? '';
            $data['primer_apellido'] = array_shift($fullName) ?? '';
            $data['segundo_apellido'] = implode(' ', $fullName) ?? '';
        }

        // Extraer Cédula de Identidad
        if (preg_match('/(?:Cédula de Identidad|No\.|N°)\s*([\d\.-]+)/i', $text, $matches)) {
            $data['carnet'] = str_replace(['.', '-'], '', trim($matches[2]));
        }

        // Extraer Fecha de Nacimiento
        if (preg_match('/(?:Fecha de Nacimiento|Nacido el)\s*(\d{1,2}(?:\/| de )\w+(?:\/| de )\d{4})/i', $text, $matches)) {
            $data['fecha_nacimiento'] = $this->parseSpanishDate(trim($matches[1]));
        }
        if (preg_match('/(?:Fecha de Expiración|Válida hasta el)\s*(INDEFINIDO|\d{1,2}(?:\/| de )\w+(?:\/| de )\d{4})/i', $text, $matches)) {
            $expiracion = trim($matches[1]);
            // --- FIN DE LA CORRECCIÓN ---

            if (strtoupper($expiracion) === 'INDEFINIDO') {
                $data['ci_es_indefinido'] = true;
                $data['ci_fecha_caducidad'] = null;
            } else {
                $data['ci_es_indefinido'] = false;
                $data['ci_fecha_caducidad'] = $this->parseSpanishDate($expiracion);
            }
        }

        return $data;
    }

    /**
     * Función auxiliar para convertir fechas en formato español a YYYY-MM-DD.
     */
    private function parseSpanishDate($dateString)
    {
        if (strpos($dateString, '/') !== false) {
            $fecha = \DateTime::createFromFormat('d/m/Y', $dateString);
            return $fecha ? $fecha->format('Y-m-d') : null;
        }
        $months = ['enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04', 'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08', 'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12'];
        $dateString = str_ireplace(array_keys($months), array_values($months), strtolower($dateString));
        $dateString = str_replace(' de ', '/', $dateString);
        $fecha = \DateTime::createFromFormat('d/m/Y', $dateString);
        return $fecha ? $fecha->format('Y-m-d') : null;
    }
}
