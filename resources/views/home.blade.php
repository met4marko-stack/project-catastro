@extends('adminlte::page')

@section('title', 'Sistema de Administracion')

@section('css')
    {{-- Importar CSS de Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        
    {{-- Estilos para el mapa del dashboard y su leyenda --}}
    <style>
        /* Definir la altura del mapa */
        #mapHome {
            height: 450px;
        }

        /* Estilos para la leyenda del mapa */
        .legend {
            padding: 6px 8px;
            font: 14px/16px Arial, Helvetica, sans-serif;
            background: white;
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
            line-height: 24px;
            color: #555;
        }
        .legend i {
            width: 18px;
            height: 18px;
            float: left;
            margin-right: 8px;
            opacity: 0.7;
        }
    </style>
@stop

@section('content_header')
    <h1>Dashboard Principal</h1>
@stop

@section('content')
    {{-- Fila de Gráfico de Tendencia --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Tendencia de Ingresos y Proyección Futura</h3>
                </div>
                <div class="card-body">
                    <canvas id="trendChart"
                        style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Fila para el Mapa de Estado de Aprobación --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Mapa de Estado de Aprobación de Planos</h3>
                </div>
                <div class="card-body p-0">
                    {{-- Este es el div donde se renderizará el mapa --}}
                    <div id="mapHome"></div>
                </div>
            </div>
        </div>
    </div>
@stop


@section('js')
    {{-- Scripts de Leaflet y Chart.js --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        $(function() {
            
            // ====================================================================
            // ▼▼▼ GRÁFICO DE TENDENCIA CON DATOS DINÁMICOS ▼▼▼
            // ====================================================================
            var trendCtx = document.getElementById('trendChart').getContext('2d');
            var chartData = @json($chartData);
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                            label: 'Ingresos Históricos (Bs)',
                            data: chartData.historical,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.1,
                            fill: true,
                        },
                        {
                            label: 'Proyección (Bs)',
                            data: chartData.projection,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderDash: [5, 5], // Línea punteada para la proyección
                            tension: 0.1,
                            fill: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value, index, values) {
                                    return 'Bs. ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // ====================================================================
            // ▼▼▼ CÓDIGO PARA EL MAPA DEL DASHBOARD ▼▼▼
            // ====================================================================

            // 1. Inicializar el mapa en el div 'mapHome'
            // Ajusta el centro [-16.5, -68.15] y el zoom (13) a la ubicación de Ayo Ayo
            var mapHome = L.map('mapHome').setView([-17.006974, -68.064169], 15);

            // 2. Añadir capa base de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mapHome);

            // 3. Añadir tu capa de GeoServer con el estilo de aprobación
            // !!! REEMPLAZA 'PROJECTCATASTRO:predios' con el nombre real de tu capa
            // !!! REEMPLAZA 'http://localhost:8080' si tu GeoServer está en otra URL
            var wmsLayer = L.tileLayer.wms('http://localhost:8080/geoserver/wms', {
                layers: 'projectcatastro:predios', 
                format: 'image/png',
                transparent: true,
                version: '1.1.0',
                styles: 'estilo_estado_aprobacion' // <-- Este es el estilo SLD que creamos
            }).addTo(mapHome);

            // 4. Añadir la leyenda de colores
            var legend = L.control({position: 'bottomright'});
            legend.onAdd = function (map) {
                var div = L.DomUtil.create('div', 'info legend');
                div.innerHTML +=
                    '<h4>Estado del Plano</h4>' +
                    '<i style="background: #33a02c"></i> Plano Aprobado<br>' +
                    '<i style="background: #e31a1c"></i> Plano Pendiente o No Aprobado';
                return div;
            };
            legend.addTo(mapHome);

        });
    </script>
@stop
