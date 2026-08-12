import 'package:geolocator/geolocator.dart';

/// Pusat Kabupaten Pasuruan — kabupaten fokus Seekitar.
/// Dipakai sebagai pandangan awal peta & fallback pencarian "terdekat".
/// JANGAN dipakai untuk MENYIMPAN koordinat pengguna (lihat [locate]).
const double kDefaultLat = -7.5994;
const double kDefaultLng = 112.8189;

/// Ambil posisi GPS dengan timeout (default 15 detik).
///
/// Mengembalikan `null` bila GPS mati, izin ditolak, atau melebihi timeout —
/// TIDAK pernah menulis koordinat tebakan. Pemanggil yang MENYIMPAN koordinat
/// (alamat, listing, request, dsb.) WAJIB menangani `null` (mis. minta user
/// mengaktifkan GPS), jangan menyimpan [kDefaultLat]/[kDefaultLng].
Future<Position?> locate({Duration timeout = const Duration(seconds: 15)}) async {
  try {
    if (!await Geolocator.isLocationServiceEnabled()) return null;
    var p = await Geolocator.checkPermission();
    if (p == LocationPermission.denied) p = await Geolocator.requestPermission();
    if (p == LocationPermission.denied || p == LocationPermission.deniedForever) return null;
    return await Geolocator.getCurrentPosition().timeout(timeout);
  } catch (_) {
    return null;
  }
}

/// Posisi default (pusat kabupaten) — HANYA untuk tampilan/pencarian
/// "terdekat", BUKAN untuk menyimpan data pengguna.
Position defaultPosition() => Position(
      latitude: kDefaultLat,
      longitude: kDefaultLng,
      timestamp: DateTime.now(),
      accuracy: 0,
      altitude: 0,
      altitudeAccuracy: 0,
      heading: 0,
      headingAccuracy: 0,
      speed: 0,
      speedAccuracy: 0,
    );
