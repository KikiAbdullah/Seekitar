@extends('admin.layouts.admin')

@section('title', 'Peta Sebaran Toko — Seekitar')

@push('styles')
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <style>
    #store-map {
      height: 600px;
      width: 100%;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      z-index: 1;
    }
    .leaflet-popup-content-value {
      font-size: 0.9rem;
      line-height: 1.4;
    }
  </style>
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <!-- Filter Card -->
      <div class="card mb-4 shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-3">
            <div>
              <h4 class="card-title">Peta Sebaran Toko</h4>
              <p class="card-subtitle mb-0">Visualisasi sebaran spasial seluruh toko aktif di wilayah {{ config('seekitar.regency') }}.</p>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Saring Status Toko</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                @foreach ($status as $st)
                  <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Map Card -->
      <div class="card shadow-sm">
        <div class="card-body p-3">
          <div id="store-map"></div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
    $(function () {
      // 1. Initialize Map
      var centerLat = {{ $pusat[0] }};
      var centerLng = {{ $pusat[1] }};
      var map = L.map('store-map', {
        center: [centerLat, centerLng],
        zoom: 11,
        minZoom: 10,
        maxZoom: 18
      });

      // 2. Set Max Bounds (Batas Kabupaten Pasuruan)
      var sw = L.latLng({{ $batas['sw'][0] }}, {{ $batas['sw'][1] }});
      var ne = L.latLng({{ $batas['ne'][0] }}, {{ $batas['ne'][1] }});
      var bounds = L.latLngBounds(sw, ne);
      map.setMaxBounds(bounds);
      map.on('drag', function() {
        map.panInsideBounds(bounds, { animate: false });
      });

      // 3. Add OpenStreetMap Tiles (dengan atribusi yang disyaratkan)
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      // 4. Marker Layer Group
      var markersLayer = L.layerGroup().addTo(map);

      // Custom colored icons using SVG markers (Spatie User/Store colors)
      function getMarkerSvg(statusColor) {
        var hexColor = '#6c757d'; // secondary (grey)
        if (statusColor === 'success') hexColor = '#168a4a'; // green
        else if (statusColor === 'warning') hexColor = '#ffc107'; // yellow
        else if (statusColor === 'danger') hexColor = '#dc3545'; // red
        else if (statusColor === 'dark') hexColor = '#212529'; // black
        
        return L.divIcon({
          html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${hexColor}" width="32" height="32">` +
                '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>' +
                '</svg>',
          className: 'custom-svg-marker',
          iconSize: [32, 32],
          iconAnchor: [16, 32],
          popupAnchor: [0, -32]
        });
      }

      // 5. Load GeoJSON Data
      function loadMapData() {
        var status = $('#filter-status').val();
        markersLayer.clearLayers();

        $.ajax({
          url: "{{ route('admin.maps.stores.data') }}",
          method: 'GET',
          data: { status: status },
          success: function (res) {
            if (res.features && res.features.length > 0) {
              L.geoJSON(res, {
                pointToLayer: function (feature, latlng) {
                  var statusColor = 'secondary';
                  if (feature.properties.status === 'verified') statusColor = 'success';
                  else if (feature.properties.status === 'pending') statusColor = 'warning';
                  else if (feature.properties.status === 'rejected') statusColor = 'danger';
                  else if (feature.properties.status === 'blocked') statusColor = 'dark';

                  return L.marker(latlng, { icon: getMarkerSvg(statusColor) });
                },
                onEachFeature: function (feature, layer) {
                  var p = feature.properties;
                  
                  // URL ke detail toko admin.stores.show
                  var detailUrl = "{{ route('admin.stores.show', ':id') }}".replace(':id', p.id);
                  
                  var popupContent = `<div class="leaflet-popup-content-value">` +
                                     `<h6 class="fw-bold mb-1 text-dark">${p.nama}</h6>` +
                                     `<p class="text-muted mb-2">${p.alamat || 'tanpa alamat'}</p>` +
                                     `<div class="mb-2">` +
                                     `<span class="badge bg-light-${p.status === 'verified' ? 'success' : (p.status === 'pending' ? 'warning' : (p.status === 'blocked' ? 'dark' : 'danger'))} text-${p.status === 'verified' ? 'success' : (p.status === 'pending' ? 'warning' : (p.status === 'blocked' ? 'dark' : 'danger'))} fw-semibold fs-1 me-2">${p.label}</span>` +
                                     `<span class="badge bg-${p.aktif ? 'success' : 'secondary'} text-white fw-semibold fs-1">${p.aktif ? 'Aktif' : 'Nonaktif'}</span>` +
                                     `</div>` +
                                     `<a href="${detailUrl}" class="btn btn-xs btn-primary text-white w-100 py-1"><i class="ti ti-store me-1"></i> Tinjau Toko</a>` +
                                     `</div>`;
                                     
                  layer.bindPopup(popupContent);
                }
              }).addTo(markersLayer);
            }
          }
        });
      }

      // Initial load
      loadMapData();

      // Dropdown filter change listener
      $('#filter-status').change(function() {
        loadMapData();
      });
    });
  </script>
@endpush
