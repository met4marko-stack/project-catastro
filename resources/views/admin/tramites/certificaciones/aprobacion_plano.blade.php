<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificación Técnica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
        }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        h1 { font-size: 18px; margin-bottom: 20px; text-decoration: underline; }
        .intro-text { margin-bottom: 20px; }
        .section-title { font-weight: bold; font-size: 14px; margin-top: 20px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        td { padding: 4px; }
        .table-bordered td { border: 1px solid #000; }
        .table-colindancias td:first-child { width: 20%; font-weight: bold; }
        .disclaimer { margin-top: 30px; font-size: 10px; font-weight: bold; text-align: justify; }
        .date-line { margin-top: 40px; }
    </style>
</head>
<body>
    <h1 class="text-center">CERTIFICACIÓN TÉCNICA</h1>

    <p class="intro-text text-justify">
        Conforme a solicitud presentada por el señor: <strong>{{ $tramite->solicitante->nombre_completo }}</strong> con
        C.I. <strong>{{ $tramite->solicitante->carnet }}{{ $tramite->solicitante->expedido }}.</strong>, quien presentó en calidad de prueba: Plano de Lote
        Georreferenciado parte de la "URBANIZACIÓN CANTÓN COLLANA TOLAR".
    </p>

    <h2 class="section-title text-center">CERTIFICA:</h2>

    <p class="text-justify">
        Que, realizada la revisión de información en la Unidad de Catastro, de la
        Secretaría Municipal de Obras e Infraestructura del Gobierno Autónomo
        Municipal de Ayo Ayo, se comprueba que el Plano de Lote referido se ubica
        en la "URBANIZACIÓN CANTÓN COLLANA TOLAR", MANZANO <strong>{{ $tramite->predio->manzano ?? 'S/N' }}</strong>, LOTE N° <strong>{{ $tramite->predio->lote ?? 'S/N' }}</strong>,
        ubicado en la <strong>{{ $tramite->predio->via->nombre ?? 'Calle sin nombre' }}</strong>, dentro del Centro Poblado Tolar, perteneciente
        al Municipio de Ayo Ayo, provincia Aroma del Departamento de La Paz.
    </p>

    <p>Con las siguientes colindancias:</p>
    <table class="table-bordered table-colindancias">
        <tr><td>NORTE:</td><td>{{ $tramite->predio->colindante_norte ?? 'N/A' }}</td></tr>
        <tr><td>SUR:</td><td>{{ $tramite->predio->colindante_sur ?? 'N/A' }}</td></tr>
        <tr><td>ESTE:</td><td>{{ $tramite->predio->colindante_este ?? 'N/A' }}</td></tr>
        <tr><td>OESTE:</td><td>{{ $tramite->predio->colindante_oeste ?? 'N/A' }}</td></tr>
    </table>

    <p>Verificado el lote tiene los siguientes datos:</p>
    <table>
        <tr>
            <td style="width: 50%;"><strong>Superficie de Lote s/Testimonio</strong></td>
            <td>{{ number_format($tramite->predio->sup_testimonio, 2, ',', '.') }} metros cuadrados</td>
        </tr>
        <tr>
            <td><strong>Superficie de Lote s/Levantamiento</strong></td>
            <td>{{ number_format($tramite->predio->sup_levantamiento, 2, ',', '.') }} metros cuadrados</td>
        </tr>
    </table>

    <p class="text-justify">
        Que revisado el Plano de Lote Georreferenciado y su compatibilidad con la
        Planimetría General de la Urbanización Cantón Collana Tolar, se encuentra
        dentro del Área Urbana del Centro Poblado Tolar, aprobado bajo Resolución
        Ministerial, ubicada en la Jurisdicción del Gobierno Autónomo
        Municipal de Ayo Ayo de la Provincia Aroma del Departamento de La Paz.
    </p>
    
    <p>Por lo que <strong>PROCEDE A CERTIFICACIÓN</strong> del mismo, para fines consiguientes.</p>
    
    <p class="disclaimer">
        Téngase presente que el Gobierno Autónomo Municipal de Ayo Ayo, en sujeción
        estricta a sus atribuciones conferidas por ley, NO OTORGA DERECHO
        PROPIETARIO ALGUNO, remitiéndose estrechamente al tenor de la presente
        certificación OTORGADA EN BASE A INFORMACIÓN TÉCNICO LEGAL PRESENTADA,
        salvo error u omisión.
    </p>
    
    <p class="date-line">
        Es dado en el Gobierno Autónomo Municipal de Ayo Ayo, en el mes de {{ $fecha_actual['mes'] }} del año {{ $fecha_actual['ano'] }}.
    </p>
</body>
</html>