<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resolución Fusión y Anexión</title>
    <style>
        @page { margin: 2.5cm 2.5cm; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.3; text-align: justify; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 80px; height: auto; position: absolute; top: -30px; left: 0; }
        .escudo { width: 80px; height: auto; position: absolute; top: -30px; right: 0; }
        .titulo { font-weight: bold; font-size: 14pt; margin-top: 40px; text-decoration: underline; }
        .codigo { font-weight: bold; margin-bottom: 10px; }
        .seccion { margin-bottom: 15px; }
        .negrita { font-weight: bold; }
        .tabla-datos { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10pt; }
        .tabla-datos th, .tabla-datos td { border: 1px solid #000; padding: 5px; text-align: center; }
        .firma { margin-top: 80px; text-align: center; page-break-inside: avoid; }
    </style>
</head>
<body>

    <div class="header">
        @if($img_logo) <img src="{{ $img_logo }}" class="logo"> @endif
        @if($img_escudo) <img src="{{ $img_escudo }}" class="escudo"> @endif
        
        <div style="margin-top: 0px;">GOBIERNO AUTÓNOMO MUNICIPAL DE AYO AYO</div>
        <div>PROVINCIA AROMA - LA PAZ - BOLIVIA</div>
        
        <div class="titulo">RESOLUCIÓN TÉCNICA ADMINISTRATIVA DE FUSIÓN Y ANEXIÓN</div>
        <div class="codigo">Nº {{ str_pad($tramite->id, 4, '0', STR_PAD_LEFT) }}/{{ now()->year }}</div>
    </div>

    <div class="seccion">
        <span class="negrita">VISTOS Y CONSIDERANDO:</span><br>
        Que, los señores propietarios han solicitado la Aprobación de Plano de Fusión y Anexión de Lotes de Terreno, ubicados en la zona {{ $predioBase->zona ?? '...' }} del Municipio de Ayo Ayo.
    </div>

    <div class="seccion">
        <span class="negrita">POR CUANTO:</span><br>
        Que, de la revisión de la documentación presentada, se evidencia que los solicitantes acreditan su derecho propietario sobre los siguientes lotes de terreno:
        
        <ul style="list-style-type: disc; padding-left: 20px;">
            {{-- Predio Base --}}
            <li>
                Lote Nº {{ $predioBase->lote }}, Manzano {{ $predioBase->manzano }}, con superficie de {{ $predioBase->sup_levantamiento }} m², registrado a nombre de 
                @foreach($predioBase->propietarios as $prop) {{ $prop->persona->nombre_completo }} @if(!$loop->last) y @endif @endforeach.
            </li>
            {{-- Predios Anexados --}}
            @foreach($prediosAnexados as $anexado)
            <li>
                Lote Nº {{ $anexado->lote }}, Manzano {{ $anexado->manzano }}, con superficie de {{ $anexado->sup_levantamiento }} m², registrado a nombre de 
                @foreach($anexado->propietarios as $prop) {{ $prop->persona->nombre_completo }} @if(!$loop->last) y @endif @endforeach.
            </li>
            @endforeach
        </ul>

        Que, mediante Testimonio Nº {{ $testimonio_numero }} de fecha {{ $testimonio_fecha_formato }}, se procedió a la Fusión y Anexión de los citados inmuebles.
    </div>

    <div class="seccion">
        <span class="negrita">POR TANTO:</span><br>
        La Unidad de Catastro del Gobierno Autónomo Municipal de Ayo Ayo, en uso de sus atribuciones conferidas por ley:
    </div>

    <div class="seccion">
        <span class="negrita">RESUELVE:</span><br>
        <span class="negrita">PRIMERO.-</span> APROBAR EL PLANO DE FUSIÓN Y ANEXIÓN, dando lugar a un nuevo lote de terreno con las siguientes características técnicas consolidadas:
        
        <table class="tabla-datos">
            <tr>
                <td colspan="2" class="negrita" style="background-color: #eee;">DATOS DEL LOTE RESULTANTE (CONSOLIDADO)</td>
            </tr>
            <tr>
                <td class="negrita" style="width: 40%;">Código Catastral</td>
                <td>{{ $predioResultante->codigo_catastral }}</td>
            </tr>
            <tr>
                <td class="negrita">Lote Nº</td>
                <td>{{ $predioResultante->lote }}</td>
            </tr>
            <tr>
                <td class="negrita">Manzano</td>
                <td>{{ $predioResultante->manzano }}</td>
            </tr>
            <tr>
                <td class="negrita">Superficie Total</td>
                <td>{{ $predioResultante->sup_levantamiento }} m²</td>
            </tr>
            <tr>
                <td class="negrita">Ubicación</td>
                <td>{{ $predioResultante->zona ?? 'Zona Urbana' }}</td>
            </tr>
            <tr>
                <td class="negrita">Propietario(s)</td>
                <td>
                    @foreach($predioResultante->propietarios as $prop)
                        {{ $prop->persona->nombre_completo }}@if(!$loop->last), @endif
                    @endforeach
                </td>
            </tr>
        </table>

        <span class="negrita">SEGUNDO.-</span> Registrar la presente Resolución en el sistema de Catastro Municipal, procediendo a la actualización de la información cartográfica y alfanumérica correspondiente, dando de baja los códigos catastrales antecedentes.
    </div>

    <div class="seccion">
        Es dada en las oficinas de Catastro del Gobierno Autónomo Municipal de Ayo Ayo, a los {{ $fecha_actual_larga }}.
    </div>

    <div class="firma">
        <br><br><br>
        ____________________________________<br>
        Responsable de Catastro<br>
        G.A.M. AYO AYO
    </div>

</body>
</html>
