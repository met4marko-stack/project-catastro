@extends('adminlte::page')

@section('title', 'Sistema de Administracion')

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        #map {
            height: 450px;
        }

        .legend {
            line-height: 18px;
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
    <h1>Administración Central - Dashboard de Analítica Catastral</h1>
@stop



@section('content')
    {{-- Fila de KPIs  --}}
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-building"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total de Inmuebles</span>
                    <span class="info-box-number">1,250</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-chart-pie"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Tasa de Regularización</span>
                    <span class="info-box-number">78.4<small>%</small></span>
                </div>
            </div>
        </div>

        {{-- fix for small devices only --}}
        <div class="clearfix hidden-md-up"></div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign text-white"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Valor Catastral Total</span>
                    <span class="info-box-number">$ 85.2M</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Crecimiento Trámites</span>
                    <span class="info-box-number">+15%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Fila Principal con Mapa y Gráfico de Burbujas --}}
    <div class="row">
        <div class="col-md-8">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Análisis Geoespacial de Regularización por Zona</h3>
                </div>
                <div class="card-body p-0">
                    <div id="map"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-purple">
                <div class="card-header">
                    <h3 class="card-title">Valor vs. Superficie por Zona</h3>
                </div>
                <div class="card-body">
                    <canvas id="bubbleChart"
                        style="min-height: 450px; height: 450px; max-height: 450px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Fila de Gráfico de Tendencia --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Tendencia de Registros y Trámites (Últimos 6 Meses)</h3>
                </div>
                <div class="card-body">
                    <canvas id="trendChart"
                        style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Sección para OCR --}}
    <!--<div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Procesar Documento Catastral (OCR)</h3>
                                    </div>
                                    <div class="card-body">
                                        <form id="ocr-form" enctype="multipart/form-data">
                                            @csrf
                                            <div class="form-group">
                                                <label for="documento">Cargar Plano Catastral (PDF o Imagen)</label>
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="documento" name="documento" accept=".pdf,.jpg,.jpeg,.png">
                                                        <label class="custom-file-label" for="documento">Seleccionar archivo</label>
                                                    </div>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-primary" type="submit" id="submit-btn">
                                                            <span id="spinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                                                            Procesar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div id="resultado-ocr" class="mt-4" style="display: none;">
                                            <h4>Resultado del Procesamiento:</h4>
                                            <pre id="json-result" class="p-3 bg-light border rounded"></pre>
                                        </div>

                                        <div id="error-ocr" class="alert alert-danger mt-4" style="display: none;">
                                            <h4>Error:</h4>
                                            <p id="error-message"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>-->
@stop


@section('js')
    {{-- Scripts de Leaflet y Chart.js --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        $(function() {
            // 1. ========= MAPA DE COROPLETAS =========
            var map = L.map('map').setView([-16.5, -68.15], 13); // Centrado en La Paz

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // --- DATOS GEOJSON DE EJEMPLO (Esto vendría de tu controlador) ---
            var geojsonZonas = {
                "type": "FeatureCollection",
                "features": [{
                        "type": "Feature",
                        "properties": {
                            "nombre": "Planimetría A (Sopocachi)",
                            "no_regularizados": 15,
                            "total": 100,
                            "valor_promedio": 120000
                        },
                        "geometry": {
                            "type": "Polygon",
                            "coordinates": [
                                [
                                    [-68.14, -16.51],
                                    [-68.13, -16.51],
                                    [-68.13, -16.50],
                                    [-68.14, -16.50],
                                    [-68.14, -16.51]
                                ]
                            ]
                        }
                    },
                    {
                        "type": "Feature",
                        "properties": {
                            "nombre": "Planimetría B (Miraflores)",
                            "no_regularizados": 35,
                            "total": 120,
                            "valor_promedio": 95000
                        },
                        "geometry": {
                            "type": "Polygon",
                            "coordinates": [
                                [
                                    [-68.12, -16.51],
                                    [-68.11, -16.51],
                                    [-68.11, -16.50],
                                    [-68.12, -16.50],
                                    [-68.12, -16.51]
                                ]
                            ]
                        }
                    },
                    {
                        "type": "Feature",
                        "properties": {
                            "nombre": "Planimetría C (Calacoto)",
                            "no_regularizados": 8,
                            "total": 150,
                            "valor_promedio": 250000
                        },
                        "geometry": {
                            "type": "Polygon",
                            "coordinates": [
                                [
                                    [-68.10, -16.54],
                                    [-68.09, -16.54],
                                    [-68.09, -16.53],
                                    [-68.10, -16.53],
                                    [-68.10, -16.54]
                                ]
                            ]
                        }
                    }
                ]
            };

            function getColor(porcentaje) {
                return porcentaje > 30 ? '#d9534f' :
                    porcentaje > 20 ? '#f0ad4e' :
                    porcentaje > 10 ? '#f0e68c' :
                    '#5cb85c';
            }

            function style(feature) {
                var tasa = (feature.properties.no_regularizados / feature.properties.total) * 100;
                return {
                    fillColor: getColor(tasa),
                    weight: 2,
                    opacity: 1,
                    color: 'white',
                    dashArray: '3',
                    fillOpacity: 0.7
                };
            }

            L.geoJson(geojsonZonas, {
                style: style,
                onEachFeature: function(feature, layer) {
                    var props = feature.properties;
                    var tasa = ((props.no_regularizados / props.total) * 100).toFixed(2);
                    layer.bindPopup(
                        `<b>${props.nombre}</b><br/>` +
                        `Inmuebles: ${props.total}<br/>` +
                        `No Regularizados: ${props.no_regularizados} (${tasa}%)<br/>` +
                        `Valor Promedio: $${props.valor_promedio.toLocaleString()}`
                    );
                }
            }).addTo(map);

            // --- Leyenda del Mapa ---
            var legend = L.control({
                position: 'bottomright'
            });
            legend.onAdd = function(map) {
                var div = L.DomUtil.create('div', 'info legend'),
                    grades = [0, 10, 20, 30],
                    labels = ['<strong>Tasa No Regularizados</strong>'];
                for (var i = 0; i < grades.length; i++) {
                    div.innerHTML +=
                        '<i style="background:' + getColor(grades[i] + 1) + '"></i> ' +
                        grades[i] + (grades[i + 1] ? '&ndash;' + grades[i + 1] + '%<br>' : '+%');
                }
                return div;
            };
            legend.addTo(map);


            // 2. ========= GRÁFICO DE BURBUJAS =========
            var bubbleCtx = document.getElementById('bubbleChart').getContext('2d');
            new Chart(bubbleCtx, {
                type: 'bubble',
                data: {
                    datasets: [{
                        label: 'Planimetría A',
                        data: [{
                            x: 150,
                            y: 120000,
                            r: 25
                        }], // r = cantidad de propiedades / 4
                        backgroundColor: 'rgba(54, 162, 235, 0.6)'
                    }, {
                        label: 'Planimetría B',
                        data: [{
                            x: 120,
                            y: 95000,
                            r: 30
                        }],
                        backgroundColor: 'rgba(255, 99, 132, 0.6)'
                    }, {
                        label: 'Planimetría C',
                        data: [{
                            x: 300,
                            y: 250000,
                            r: 38
                        }],
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            title: {
                                display: true,
                                text: 'Valor Promedio ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Superficie Promedio (m²)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    var rValue = context.raw.r * 4; // Revertir el cálculo
                                    return `${label}: (${context.parsed.x} m², $${context.parsed.y.toLocaleString()}) - ${rValue} propiedades`;
                                }
                            }
                        }
                    }
                }
            });

            // 3. ========= GRÁFICO DE TENDENCIA =========
            var trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: ['Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre'],
                    datasets: [{
                        label: 'Nuevos Inmuebles',
                        data: [12, 19, 25, 30, 28, 35],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        tension: 0.1
                    }, {
                        label: 'Trámites Iniciados',
                        data: [22, 28, 35, 41, 39, 48],
                        borderColor: 'rgba(255, 99, 132, 1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
    </script>

    <!--<script>
        // Mostrar el nombre del archivo seleccionado en el input
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            var fileName = document.getElementById("documento").files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });

        document.getElementById('ocr-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-btn');
            const spinner = document.getElementById('spinner');
            const resultadoDiv = document.getElementById('resultado-ocr');
            const jsonResult = document.getElementById('json-result');
            const errorDiv = document.getElementById('error-ocr');
            const errorMessage = document.getElementById('error-message');

            // Ocultar resultados anteriores y mostrar spinner
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            resultadoDiv.style.display = 'none';
            errorDiv.style.display = 'none';

            const formData = new FormData(this);

            fetch('{{ route('ocr.procesar') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw err;
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // Mostrar resultado
                    jsonResult.textContent = JSON.stringify(data, null, 2);
                    resultadoDiv.style.display = 'block';
                })
                .catch(error => {
                    // Mostrar error
                    let errorText = 'Ocurrió un error inesperado.';
                    if (error.error) {
                        errorText = error.error;
                        if (error.details) {
                            errorText +=
                                ` Detalles: ${typeof error.details === 'object' ? JSON.stringify(error.details) : error.details}`;
                        }
                    } else if (error.message) {
                        errorText = error.message;
                    }
                    errorMessage.textContent = errorText;
                    errorDiv.style.display = 'block';
                })
                .finally(() => {
                    // Ocultar spinner y reactivar botón
                    spinner.style.display = 'none';
                    submitBtn.disabled = false;
                });
        });
    </script>-->
@stop
