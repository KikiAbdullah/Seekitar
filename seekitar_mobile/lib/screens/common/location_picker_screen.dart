import 'package:flutter/material.dart';
import 'package:flutter_osm_plugin/flutter_osm_plugin.dart';
import '../../services/location_service.dart';

/// Layar pemilih titik lokasi berbasis OpenStreetMap.
///
/// Memakai tile server Carto (`basemaps.cartocdn.com`) — gratis dan TIDAK
/// memblokir permintaan, tidak seperti tile.openstreetmap.org yang memakai
/// Tile Usage Policy ketat (error "Access blocked").
///
/// Cara pakai: user geser peta hingga penanda tengah berada di lokasi yang
/// diinginkan, lalu tekan "Pilih Lokasi Ini". Koordinat tengah peta yang
/// dikembalikan.
class LocationPickerScreen extends StatefulWidget {
  const LocationPickerScreen({super.key, this.initial, this.title = 'Pilih Titik Lokasi', this.readOnly = false});

  /// Titik awal peta (bila sudah pernah pilih sebelumnya).
  final GeoPoint? initial;

  /// Judul layar.
  final String title;

  /// Mode hanya-lihat: peta + penanda tanpa tombol konfirmasi (dipakai
  /// halaman "sudah terverifikasi" untuk menampilkan titik lokasi).
  final bool readOnly;

  @override
  State<LocationPickerScreen> createState() => _LocationPickerScreenState();
}

class _LocationPickerScreenState extends State<LocationPickerScreen> {
  late final MapController _controller;
  bool _mapReady = false;

  @override
  void initState() {
    super.initState();
    // Tile Carto Voyager — bebas dipakai tanpa API key & tanpa blokir.
    _controller = MapController.customLayer(
      initPosition: widget.initial ??
          // Pandangan awal peta = pusat Kab. Pasuruan (bukan data tersimpan).
          GeoPoint(latitude: kDefaultLat, longitude: kDefaultLng),
      customTile: CustomTile(
        urlsServers: [
          TileURLs(
            url: "https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png",
            subdomains: ["a", "b", "c", "d"],
          ),
        ],
        tileExtension: ".png",
        sourceName: "cartoVoyager",
        tileSize: 256,
        minZoomLevel: 2,
        maxZoomLevel: 19,
      ),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _confirm() async {
    if (!_mapReady) return;
    try {
      final center = await _controller.centerMap;
      if (mounted) Navigator.pop(context, center);
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Gagal mengambil titik lokasi. Coba lagi.')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: Stack(children: [
        Positioned.fill(
          child: OSMFlutter(
            controller: _controller,
            osmOption: OSMOption(
              showZoomController: true,
              showContributorBadgeForOSM: true,
              zoomOption: const ZoomOption(
                initZoom: 14,
                minZoomLevel: 2,
                maxZoomLevel: 19,
              ),
            ),
            onMapIsReady: (ready) {
              if (mounted) setState(() => _mapReady = ready);
            },
            onMapMoved: (moving) {
              // Peta bergerak — koordinat pusat diambil saat konfirmasi.
            },
          ),
        ),
        // Penanda tengah — titik yang akan dipilih adalah pusat peta.
        IgnorePointer(
          child: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.location_on, size: 44, color: t.colorScheme.primary),
                Transform.translate(
                  offset: const Offset(0, 20),
                  child: Text(
                    'Geser peta hingga penanda di lokasi kamu',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      backgroundColor: Colors.black.withOpacity(0.5),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
        // Tombol konfirmasi (disembunyikan pada mode hanya-lihat).
        if (!widget.readOnly)
          Positioned(
            left: 20,
            right: 20,
            bottom: 24,
            child: SafeArea(
              child: SizedBox(
                height: 54,
                child: ElevatedButton.icon(
                  onPressed: _mapReady ? _confirm : null,
                  icon: const Icon(Icons.check_circle_outline),
                  label: const Text('Pilih Lokasi Ini', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                ),
              ),
            ),
          ),
      ]),
    );
  }
}
