@extends('adminlte::page')

@section('title', 'Visualización de Planimetrías')

@section('content_header')
    <h1>Visualización de Planimetría</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-end align-items-center">
            {{-- Leyenda o texto descriptivo --}}
            <span class="mr-2"><strong>Ingrese Código Catastral:</strong></span>

            {{-- Grupo del buscador --}}
            <div class="input-group" style="max-width: 300px;">
                <input type="text" id="search-catastral-input" class="form-control" placeholder="Ej: 011301">
                <div class="input-group-append">
                    <button id="search-catastral-btn" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0"> {{-- p-0 para que el mapa ocupe todo el espacio --}}
            {{-- El contenedor del mapa --}}
            <div id="map" style="height: 600px; width: 100%;"></div>
        </div>
    </div>
@stop

{{-- Importante: Incluir los estilos y scripts de Leaflet --}}
@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@stop

@section('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Opciones de GeoServer
            const geoserverUrl = 'http://localhost:8080/geoserver/wms'; // Reemplaza con la URL de tu GeoServer
            const workspace = 'projectcatastro'; // El workspace que creaste

            // 2. Inicializar el mapa centrado en La Paz
            const map = L.map('map', {
                maxZoom: 22 // <-- AÑADIR: Permite hacer zoom hasta el nivel 22
            }).setView([-17.006974, -68.064169], 14);

            // 3. Añadir una capa base (OpenStreetMap)
            const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 22
            }).addTo(map);

            // 4. Definir las capas de GeoServer (Overlays)
            const prediosLayer = L.tileLayer.wms(geoserverUrl, {
                layers: `${workspace}:v_predios_map`,
                format: 'image/png',
                transparent: true,
                maxZoom: 22
            });

            const ejesVialesLayer = L.tileLayer.wms(geoserverUrl, {
                layers: `${workspace}:v_eje_via_map`,
                format: 'image/png',
                transparent: true,
                maxZoom: 22
            });

            // 5. Crear objetos para el control de capas
            const baseMaps = {
                "OpenStreetMap": osmLayer
            };

            const overlayMaps = {
                "Predios": prediosLayer,
                "Ejes Viales": ejesVialesLayer,
            };

            // 6. Añadir el control de capas al mapa
            L.control.layers(baseMaps, overlayMaps).addTo(map);

            // Opcional: Añadir una capa por defecto al cargar el mapa
            prediosLayer.addTo(map);

            ////////////////////////////////////////////////////////////
            const searchInput = document.getElementById('search-catastral-input');
            const searchBtn = document.getElementById('search-catastral-btn');
            let highlightLayer = null; // Variable para guardar la capa de resaltado

            const buscarPredio = () => {
                const codigoCatastral = searchInput.value;
                if (!codigoCatastral) {
                    Swal.fire('Atención', 'Por favor, ingrese un Código Catastral.', 'warning');
                    return;
                }

                // Remover resaltado anterior
                if (highlightLayer) {
                    map.removeLayer(highlightLayer);
                }

                fetch(`{{ route('admin.predios.buscar') }}?codigo_catastral=${codigoCatastral}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Código no encontrado');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Leaflet puede leer GeoJSON directamente
                        const shapeLayer = L.geoJSON(data.geometry);

                        // Centrar el mapa en los límites del predio
                        map.fitBounds(shapeLayer.getBounds(), {
                            maxZoom: 19
                        });

                        // Resaltar el predio encontrado en el mapa
                        highlightLayer = L.geoJSON(data.geometry, {
                            style: {
                                color: '#ff0000',
                                weight: 3,
                                fillOpacity: 0.3
                            }
                        }).addTo(map);

                        // Opcional: Remover el resaltado después de unos segundos
                        setTimeout(() => {
                            if (highlightLayer) {
                                map.removeLayer(highlightLayer);
                            }
                        }, 5000); // 5 segundos
                    })
                    .catch(error => {
                        Swal.fire('Error', 'No se pudo encontrar el predio con ese código.', 'error');
                    });
            };

            // Event Listeners
            searchBtn.addEventListener('click', buscarPredio);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    buscarPredio();
                }
            });
        });
    </script>
@stop
