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
        
        {{-- Contenedor de Información del Predio (Inicialmente oculto) --}}
        <div id="predio-info" class="card-footer bg-white" style="display: none;">
            <h5 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Detalles del Predio Encontrado</h5>
            <div class="row">
                <div class="col-md-3">
                    <strong>Código Catastral:</strong>
                    <p id="info-codigo" class="text-muted"></p>
                </div>
                <div class="col-md-4">
                    <strong>Propietario(s):</strong>
                    <p id="info-propietario" class="text-muted"></p>
                </div>
                <div class="col-md-3">
                    <strong>Ubicación (Zona/Mz/Lote):</strong>
                    <p id="info-ubicacion" class="text-muted"></p>
                </div>
                <div class="col-md-2">
                    <strong>Sup. Levantamiento:</strong>
                    <p id="info-superficie" class="text-muted"></p>
                </div>
            </div>
        </div>
    </div>
@stop

{{-- Importante: Incluir los estilos y scripts de Leaflet --}}
@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Esto hará que el fondo vacío sea blanco y no gris */
        #map { background-color: #ffffff; }
    </style>
@stop


@section('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    {{-- SweetAlert2 para las alertas --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ---------------------------------------------------------
            // 1. CONFIGURACIÓN INICIAL
            // ---------------------------------------------------------
            const geoserverUrl = 'http://localhost:8080/geoserver/wms'; 
            const workspace = 'projectcatastro'; 

            // Inicializar mapa
            const map = L.map('map', {
                maxZoom: 22 
            }).setView([-17.006974, -68.064169], 14);

            // ---------------------------------------------------------
            // 2. DEFINICIÓN DE CAPAS BASE
            // ---------------------------------------------------------

            // Opción A: Mapa Callejero (OpenStreetMap - El gris que tenías)
            const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 22
            });

            // Opción B: Google Satélite Híbrido (NUEVO - Fotos + Calles)
            const googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
                maxZoom: 20,
                subdomains:['mt0','mt1','mt2','mt3'],
                attribution: 'Google Maps'
            });

            // Opción C: Tu Ortofoto Local (GeoServer)
            const ortofotoLayer = L.tileLayer.wms(geoserverUrl, {
                layers: `${workspace}:ortofoto_oficial_uno`, 
                format: 'image/png', 
                transparent: true,   
                version: '1.1.0',
                attribution: "Ortofoto Municipal - GAM",
                maxZoom: 22
            });

            // ---------------------------------------------------------
            // 3. CAPAS SUPERPUESTAS (Overlays)
            // ---------------------------------------------------------
            
            // Capa de Predios
            const prediosLayer = L.tileLayer.wms(geoserverUrl, {
                layers: `${workspace}:v_predios_map`,
                format: 'image/png',
                transparent: true,
                maxZoom: 22
            });

            // Capa de Vías
            const ejesVialesLayer = L.tileLayer.wms(geoserverUrl, {
                layers: `${workspace}:v_eje_via_map`,
                format: 'image/png',
                transparent: true,
                maxZoom: 22
            });

            // ---------------------------------------------------------
            // 4. CONTROL DE CAPAS (Menú)
            // ---------------------------------------------------------
            
            // Mapas Base (Radio Buttons - Elige uno)
            const baseMaps = {
                "Mapa Callejero (OSM)": osmLayer,
                "Satélite (Google)": googleHybrid, // <--- Aquí aparece la nueva opción
                "Ortofoto Aérea (Local)": ortofotoLayer
            };

            // Capas superpuestas (Checkboxes)
            const overlayMaps = {
                "Predios Catastrales": prediosLayer,
                "Ejes de Vías": ejesVialesLayer,
            };

            // Agregar el control al mapa
            L.control.layers(baseMaps, overlayMaps).addTo(map);

            // ---------------------------------------------------------
            // 5. INICIALIZACIÓN DE VISTA
            // ---------------------------------------------------------
            
            // AQUÍ ELIGES CON QUÉ MAPA INICIAR:
            // Si quieres que inicie con Satélite, usa googleHybrid.addTo(map);
            // Si quieres el callejero gris, usa osmLayer.addTo(map);
            
            googleHybrid.addTo(map); // <--- Inicia directo con Satélite
            
            prediosLayer.addTo(map); // Capas vectoriales encima

            // ---------------------------------------------------------
            // 6. LÓGICA DE BÚSQUEDA (Sin cambios)
            // ---------------------------------------------------------
            const searchInput = document.getElementById('search-catastral-input');
            const searchBtn = document.getElementById('search-catastral-btn');
            const infoContainer = document.getElementById('predio-info');
            let highlightLayer = null; 

            const buscarPredio = () => {
                const codigoCatastral = searchInput.value.trim();
                
                if (!codigoCatastral) {
                    Swal.fire('Atención', 'Por favor, ingrese un Código Catastral.', 'warning');
                    return;
                }

                if (highlightLayer) map.removeLayer(highlightLayer);
                infoContainer.style.display = 'none';

                fetch(`{{ route('admin.predios.buscar') }}?codigo_catastral=${codigoCatastral}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Predio no encontrado');
                        return response.json();
                    })
                    .then(response => {
                        const { geometry, data } = response;

                        const shapeLayer = L.geoJSON(geometry);
                        map.fitBounds(shapeLayer.getBounds(), { maxZoom: 19 });

                        highlightLayer = L.geoJSON(geometry, {
                            style: { 
                                color: '#FFFF00', 
                                weight: 4, 
                                fillOpacity: 0.1,
                                opacity: 0.8
                            }
                        }).addTo(map);

                        document.getElementById('info-codigo').textContent = data.codigo_catastral || 'S/D';
                        document.getElementById('info-propietario').textContent = data.propietarios || 'Sin registro';
                        document.getElementById('info-ubicacion').textContent = 
                            `${data.zona || ''} / Mz: ${data.manzano || '-'} / Lt: ${data.lote || '-'}`;
                        document.getElementById('info-superficie').textContent = 
                            (data.sup_levantamiento ? data.sup_levantamiento + ' m²' : 'S/D');
                        
                        infoContainer.style.display = 'block';

                        setTimeout(() => {
                            if (highlightLayer) map.removeLayer(highlightLayer);
                        }, 10000);
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire('No encontrado', 'No existe un predio con ese código catastral.', 'error');
                    });
            };

            searchBtn.addEventListener('click', buscarPredio);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') buscarPredio();
            });
        });
    </script>
@stop
