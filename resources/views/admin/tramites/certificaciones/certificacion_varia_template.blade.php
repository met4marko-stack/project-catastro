<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $input_titulo ?? 'Certificado' }}</title>
    <style>
        body { 
            font-family: sans-serif; 
            font-size: 11pt; 
            margin-left: 1cm;
            margin-right: 1cm; 
            margin-top: 1cm;
        }

        h1 { 
            font-size: 14pt; 
            text-align: center; 
            font-weight: bold; 
            text-decoration: underline;
            border: 1px solid #000;
            padding: 0.5em;
        }

        .codigo-certificado-wrapper {
            text-align: right;
            margin-top: 1em;
        }

        .codigo-certificado { 
            font-size: 12pt; 
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
            padding: 0.25em 0.5em;
            display: inline-block;
        }

        .certifico { 
            text-align: center; 
            font-weight: bold; 
            margin-bottom: 1.5em; 
            margin-top: 1.5em; 
        }

        .parrafo { 
            text-align: justify; 
            line-height: 1.5; 
        }

        .main-content-box {
            border: 1px solid #000;
            padding: 1em;
            margin-top: 1.5em;
        }

        .fecha { 
            margin-top: 3em; /* Espacio antes de "Es cuanto tenemos..." */
        }

        /* ESTE ES EL ESTILO IMPORTANTE
           Posiciona la fecha en la parte inferior FIJA de la página.
        */
        .footer-fijo {
            position: fixed; 
            bottom: 1cm; /* Distancia desde el borde inferior */
            left: 1cm;   /* Margen izquierdo del body */
            right: 1cm;  /* Margen derecho del body */
            
            /* Alineamos la fecha a la derecha */
            text-align: right;
            font-size: 11pt; /* Que coincida con el resto del body */
        }
    </style>
</head>
<body>
    <header>
    </header>

    <main>
        <h1>{{ $input_titulo }}</h1>

        <div class="codigo-certificado-wrapper">
            <div class="codigo-certificado">{{ $codigo_certificado }}</div>
        </div>
        
        <div class="main-content-box">
            <p class="parrafo">
                EL SUSCRITO RESPONSABLE DE LA UNIDAD DE CATASTRO LIMITES, A SOLICITUD FORMULADA POR EL SEÑOR/A:
                **{{ $tramite->solicitante->nombre_completo }}**, DE ACUERDO A SUS ATRIBUCIONES QUE ME COMPETEN Y PARA USO EXCLUSIVO
                DEL INTERESADO:
            </p>

            <h2 class="certifico">CERTIFICO:</h2>

            <p class="parrafo">
                {{ $input_parrafo_1 }}
            </p>
            
            <p class="parrafo">
                A efectos de coadyuvar con el propietario, la Unidad de Catastro Limites en representación del Gobierno
                Autónomo Municipal de Ayo Ayo, como órgano gestor extiende la presente <strong>{{ $input_parrafo_2_negrita }}</strong>,
                la cual es de uso estricto para tramites en las Oficinas de Derechos Reales, NO CONSTITUYENDOSE ACREDITACIÓN
                DEL DERECHO PROPIETARIO.
            </p>
            
            <p class="fecha">
                Es cuanto tenemos a bien certificar para fines consiguientes.
            </p>

        </div> </main>

    <footer>
        <p class="footer-fijo">
            Ayo Ayo, {{ $fecha_actual['dia'] }} de {{ $fecha_actual['mes'] }} del {{ $fecha_actual['ano'] }}
        </p>
    </footer>
</body>
</html>