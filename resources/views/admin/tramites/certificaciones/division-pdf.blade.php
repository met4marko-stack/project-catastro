<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resolución División y Partición</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            margin: 1.5cm;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-justify { text-align: justify; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Encabezado */
        #header { width: 100%; padding-bottom: 5px; }
        #header .logo { width: 80px; height: auto; }
        #header .info { text-align: center; font-size: 10pt; font-weight: bold; }
        #header td { vertical-align: middle; }

        h1 {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }
        h2 {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        /* Cuerpo del documento */
        p { margin: 8px 0; }
        .section-title {
            font-weight: bold;
            text-align: center;
            font-size: 11pt;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        
        .resolucion-articulo {
            font-weight: bold;
            text-align: center;
            margin-top: 15px;
        }
        
        /* Tabla de Lotes */
        .lotes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 9pt;
            text-align: center;
        }
        .lotes-table th, .lotes-table td {
            border: 1px solid #000;
            padding: 4px;
        }
        .lotes-table th { background-color: #f0f0f0; }

        .footer-text {
            margin-top: 25px;
            font-size: 9pt;
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

    <h1>RESOLUCIÓN TÉCNICA ADMINISTRATIVA</h1>
    <h2>Nº {{ $tramite->id }}/{{ date('Y') }}</h2>

    <table class="lotes-table" style="font-size: 10pt; text-align: left;">
        <tr>
            <td style="width: 20%;" class="bold">Motivo:</td>
            <td>APROBACIÓN DE DIVISIÓN Y PARTICIÓN</td>
        </tr>
        <tr>
            <td class="bold">Propietario(s):</td>
            <td>
                @foreach($propietarios as $index => $propietario)
                    <span class="uppercase">{{ $propietario->persona->nombre_completo }}</span>@if(!$loop->last), @endif
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="bold">Ubicación:</td>
            <td>CENTRO POBLADO TOLAR – URBANIZACIÓN CANTON COLLANA TOLAR</td>
        </tr>
    </table>


    <p class="section-title">ANTECEDENTES</p>
    <p class="text-justify">
        Que la Constitución Política del Estado en el Art. 283 concordante con el Art. 34 de la Ley Marco de Autonomías y Descentralización ¨Andrés Ibáñez¨, establece que el Gobierno Autónomo Municipal está constituido por un Órgano Legislativo y Ejecutivo, de este último el Alcalde Municipal es la Máxima Autoridad Ejecutiva.
    </p>
    <p class="text-justify">
        Que el Articulo 302 en el parágrafo I de la norma suprema constitucional establece como competencias exclusivas de los Gobiernos Municipales las gestiones sobre ¨6) Elaboración de Planes de Ordenamiento Territorial y de uso de suelos...¨, ¨ 10) Catastro Urbano en el ámbito de su jurisdicción...¨, y 29) Desarrollo urbano y asentamientos humanos urbanos¨.
    </p>
    <p class="text-justify">
        Que, la Ley Nº 31 Ley Marco de Autonomías y Descentralización ¨Andrés Ibáñez¨ establece en el Articulo 82 sobre las competencias exclusivas de los gobiernos municipales...
    </p>
    
    <p class="section-title">VISTOS Y CONSIDERADOS:</p>
    <p class="text-justify">
        De acuerdo a Testimonio Nº {{ $testimonio_numero }} de fecha {{ $testimonio_fecha_formato }} sobre División y Partición voluntaria suscrito por los Señores:
        @foreach($propietarios as $index => $propietario)
            <span class="bold uppercase">{{ $propietario->persona->nombre_completo }}</span> con C.I. <span class="bold">{{ $propietario->persona->carnet }} {{ $propietario->persona->expedido }}</span>@if(!$loop->last) y @else. @endif
        @endforeach
        y los documentos técnicos.
    </p>
    
    <p class="section-title">REQUISITOS TÉCNICOS</p>
    <p class="text-justify">
        El Solicitante, Cuenta con el Plano de la división y Partición.
        El Solicitante, cuentan con la información en CD digitalizado de la división y Partición.
    </p>
    
    <p class="text-justify">
        Que el Informe Técnico emitido por el Responsable Técnico de la Unidad de Catastro - Limites del Gobierno Autónomo Municipal de Ayo Ayo, refiere: Que los Sres.
        @foreach($propietarios as $index => $propietario)
            <span class="bold uppercase">{{ $propietario->persona->nombre_completo }}</span> con C.I. <span class="bold">{{ $propietario->persona->carnet }} {{ $propietario->persona->expedido }}</span>@if(!$loop->last) y @endif
        @endforeach
        , son propietarios de un lote de terreno de extensión Superficie {{ number_format($superficie_total, 2, ',', '.') }} m2, ubicado en la Urbanización Cantón Collana Tolar, dentro del Centro Poblado Tolar, Municipio Ayo Ayo, Provincia Aroma del Departamento de La Paz.
    </p>
    <p class="text-justify">
        Quienes presentaron un Plano de División y Partición del lote de terreno Nº {{ $predio->lote ?? 'S/N' }}, Manzana {{ $predio->manzano ?? 'S/N' }} de la siguiente manera:
        @foreach($incisos as $index => $inciso)
            <span class="bold">
                {{ chr(97 + $index) }}) “MANZANO {{ $inciso['manzano'] }}”, LOTE {{ $inciso['lote'] }} con una superficie de {{ number_format($inciso['superficie'], 2, ',', '.') }} m2 para: {{ $inciso['propietario_nombre'] }} con C.I. {{ $inciso['propietario_ci'] }};
            </span>
        @endforeach
    </p>
    <p class="text-justify">
        Siendo que la división y partición no presenta ninguna observación en la inspección de campo, concluye indicando que PROCEDE el presente trámite.
    </p>

    <p class="section-title">POR TANTO</p>
    <p class="text-justify">
        El Gobierno Autónomo Municipal de Ayo Ayo, a través de la Unidad de Catastro - Limites, en uso de sus atribuciones conferidas por la Constitución Política del Estado Plurinacional de Bolivia.
    </p>

    <p class="section-title">RESUELVE</p>
    <p class="resolucion-articulo">ARTICULO PRIMERO. -</p>
    <p class="text-justify">
        APROBAR EL PLANO DE LOTE DE DIVISIÓN Y PARTICIÓN de los Sres. 
        @foreach($propietarios as $index => $propietario)
            <span class="bold uppercase">{{ $propietario->persona->nombre_completo }}</span> con C.I. <span class="bold">{{ $propietario->persona->carnet }} {{ $propietario->persona->expedido }}</span>@if(!$loop->last) y @endif
        @endforeach
        , en {{ count($incisos) }} lotes, propiedad que se encuentra ubicado en la {{ $predio->via->nombre ?? 'N/A' }}, predio inmerso en la Planimetría de la Urbanización Cantón Collana Tolar, con las siguientes características:
    </p>
    
    <table class="lotes-table">
        <thead>
            <tr>
                <th>LOTE</th>
                <th>NUEVO CODIGO ASIGNADO</th>
                <th>SUPERFICIE</th>
                <th>LEGAL</th>
                <th>%</th>
                <th>SUPERFICIE</th>
                <th>UTIL</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incisos as $inciso)
            <tr>
                <td>{{ $inciso['lote'] }}</td>
                <td>LOTE {{ $inciso['lote_nuevo'] }}</td>
                <td>{{ number_format($inciso['superficie'], 2, ',', '.') }} M2</td>
                <td>{{ number_format($inciso['superficie'], 2, ',', '.') }} M2</td>
                <td>{{ number_format($inciso['superficie_legal_porcentaje'], 2, ',', '.') }}</td>
                <td>{{ number_format($inciso['superficie'], 2, ',', '.') }} M2</td>
                <td>{{ number_format($inciso['superficie'], 2, ',', '.') }} M2</td>
                <td>{{ number_format($inciso['superficie_util_porcentaje'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="bold">
                <td colspan="2">TOTAL</td>
                <td>{{ number_format($superficie_total, 2, ',', '.') }} M2</td>
                <td>{{ number_format($superficie_total, 2, ',', '.') }} M2</td>
                <td>100,00</td>
                <td>{{ number_format($superficie_total, 2, ',', '.') }} M2</td>
                <td>{{ number_format($superficie_total, 2, ',', '.') }} M2</td>
                <td>100,00</td>
            </tr>
        </tbody>
    </table>
    
    @foreach($incisos as $index => $inciso)
        <p class="text-justify">
            Al terreno del “MANZANO {{ $inciso['manzano'] }}, LOTE {{ $inciso['lote'] }}”, se le asigna nuevo código de lote, el cual se denomina en adelante “ MANZANO {{ $inciso['manzano'] }}, LOTE {{ $inciso['lote_nuevo'] }} ” del señor(a) <span class="bold uppercase">{{ $inciso['propietario_nombre'] }}</span> con C.I. {{ $inciso['propietario_ci'] }}.
        </p>
    @endforeach
    
    <table class="lotes-table">
        <thead>
            <tr>
                @foreach($incisos as $inciso)
                <th class="bold">LOTE {{ $inciso['lote_nuevo'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach($incisos as $inciso)
                <td style="text-align: left;">Norte: {{ $inciso['col_norte'] }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach($incisos as $inciso)
                <td style="text-align: left;">Sur: {{ $inciso['col_sur'] }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach($incisos as $inciso)
                <td style="text-align: left;">Este: {{ $inciso['col_este'] }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach($incisos as $inciso)
                <td style="text-align: left;">Oeste: {{ $inciso['col_oeste'] }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <p class="resolucion-articulo">ARTICULO SEGUNDO. -</p>
    <p class="text-justify">
        AUTORIZAR, a la Unidad de Catastro - Limites proceder a viabilizar el tramite técnico bajo la APROBACIÓN DE LA RESOLUCIÓN TÉCNICA ADMINISTRATIVA DE APROBACIÓN DE LA DIVISIÓN Y PARTICIÓN.
    </p>

    <p class="footer-text">
        Regístrese, comuníquese y envíese copias a las unidades correspondientes: Unidad de Catastro - Limites para su conocimiento y cumplimiento.
    </p>
    <p class="footer-text">
        Es dado en el Gobierno Autónomo Municipal de Ayo Ayo, a los {{ $fecha_actual_larga }}.
    </p>

</body>
</html>