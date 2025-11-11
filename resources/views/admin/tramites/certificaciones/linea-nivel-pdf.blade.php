<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Línea y Nivel Municipal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt; /* Respetando tu cambio de tamaño */
            line-height: 1.5;
            margin-top: 0cm;
            margin-left: 0.5cm;
            margin-right: 0.5cm;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-justify { text-align: justify; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Encabezado */
        #header { width: 100%; border-bottom: 1px solid #000; padding-bottom: 5px; }
        #header .logo { width: 80px; height: auto; }
        #header .info { text-align: center; font-size: 10pt; font-weight: bold; }
        #header td { vertical-align: middle; }

        h1 {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin-top: 20px;
        }

        /* Cuerpo del documento */
        p { margin: 10px 0; }
        .certifica-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-top: 15px;
            margin-bottom: 15px;
        }

        /* Tabla de Datos Técnicos */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 10pt; /* Respetando tu tamaño de tabla */
        }
        .data-table td {
            border: 1px solid #000;
            padding: 5px;
        }
        /* Ajuste para 4 columnas */
        .data-table .label { font-weight: bold; width: 20%; }
        .data-table .value { width: 30%; }
        
        .section-title {
            font-weight: bold;
            font-size: 10pt;
            background-color: #f0f0f0;
            text-align: center;
            border: 1px solid #000;
            padding: 5px;
        }

        .details-list {
            margin-top: 10px;
            padding-left: 20px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
        }

    </style>
</head>
<body>

    <table id="header">
        <tr>
            <td style="width: 20%; text-align: left;">
                @if($img_escudo)
                    <img src="{{ $img_escudo }}" alt="Escudo" class="logo">
                @endif
            </td>
            <td style="width: 60%;" class="info">
                GOBIERNO AUTONOMO MUNICIPAL DE AYO AYO
                <br>UNIDAD DE CATASTRO - LIMITES
            </td>
            <td style="width: 20%; text-align: right;">
                 @if($img_logo)
                    <img src="{{ $img_logo }}" alt="Logo Catastro" class="logo">
                @endif
            </td>
        </tr>
    </table>

    <h1>CERTIFICADO DE LINEA Y NIVEL MUNICIPAL</h1>

    <p class="text-justify">
        Que los señores: 
        @foreach($propietarios as $index => $propietario)
            <span class="bold uppercase">{{ $propietario->persona->nombre_completo }}</span> con C.I. <span class="bold">{{ $propietario->persona->carnet }} {{ $propietario->persona->expedido }}</span>@if(!$loop->last), @else. @endif
        @endforeach
        En atención a la nota de fecha {{ \Carbon\Carbon::parse($tramite->fecha_inicio)->format('d \d\e M \d\e\l Y') }}, donde solicitan LA FIJACIÓN Y EMISIÓN DE CERTIFICADO DE LINEA Y NIVEL MUNICIPAL.
    </p>

    <p class="text-justify">
        {{ $parrafo_dos }}
    </p>

    <p class="certifica-title">SE CERTIFICA:</p>

    <p class="text-justify">
        Que, según verificación en Situ y en la planimetría, el lote referido se encuentra en la “URBANIZACIÓN CANTÓN COLLANA TOLAR”, perteneciente al Gobierno Autónomo Municipal de Ayo Ayo, Provincia {{ $predio->provincia->nombre ?? 'Aroma' }} del Departamento de La Paz y de acuerdo a solicitud responde a los siguientes datos técnicos:
    </p>

    {{-- --- TABLA DE DATOS TÉCNICOS MODIFICADA --- --}}
    <table class="data-table">
        <tr>
            <td colspan="4" class="section-title">DATOS TÉCNICOS</td>
        </tr>
        <tr>
            <td class="label">CENTRO POBLADO:</td>
            <td class="value">{{ $centroPoblado }}</td>
            <td class="label">AV/CALLE/PASAJE:</td>
            <td class="value">{{ $predio->via->nombre ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">DISTRITO:</td>
            <td class="value">{{ $distrito }}</td>
            <td class="label">SUP. LEGAL:</td>
            <td class="value">{{ number_format($predio->sup_testimonio, 2, ',', '.') ?? 'N/A' }} m²</td>
        </tr>
        <tr>
            <td class="label">CODIGO CATASTRAL:</td>
            <td class="value">{{ $predio->codigo_catastral ?? 'N/A' }}</td>
            <td class="label">SUP. REAL:</td>
            <td class="value">{{ number_format($predio->sup_levantamiento, 2, ',', '.') ?? 'N/A' }} m²</td>
        </tr>
    </table>
    
    {{-- --- TABLA DE SUPERFICIES AÑADIDA --- --}}
    <table class="data-table">
        <tr>
            <td colspan="5" class="section-title">RELACIÓN DE SUPERFICIES</td>
        </tr>
        <tr style="text-align: center;" class="bold">
            <td>LOTE</td>
            <td>SUP. LEGAL</td>
            <td>%</td>
            <td>SUP. REAL</td>
            <td>%</td>
        </tr>
        <tr style="text-align: center;">
            <td>{{ $predio->lote ?? 'S/N' }}</td>
            <td>{{ number_format($predio->sup_testimonio, 2, ',', '.') ?? 'N/A' }} m²</td>
            <td>100%</td>
            <td>{{ number_format($predio->sup_levantamiento, 2, ',', '.') ?? 'N/A' }} m²</td>
            <td>100%</td>
        </tr>
    </table>
    {{-- --- FIN DE TABLA AÑADIDA --- --}}


    <div class="details-list">
        <p class="bold">DETALLE:</p>
        <ol>
            <li>Se realizó la fijación de la Línea y Nivel Municipal con la presencia del solicitante.</li>
            <li>Existe compatibilidad con la Planimetría aprobado bajo Ley Municipal.</li>
            <li>...</li>
            <li>...</li>
        </ol>
    </div>

    <div class="footer">
        <p>
            Es dado en el Gobierno Autónomo Municipal de Ayo Ayo, a los 
            {{ $fecha_actual['dia'] }} días del mes de {{ $fecha_actual['mes'] }} del año {{ $fecha_actual['ano'] }}.
        </p>

        <br><br><br><br>

        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; text-align: center;">
                    ..............................................<br>
                    <span class="bold">JEFE DE LA UCL</span>
                </td>
                
            </tr>
        </table>
    </div>

</body>
</html>