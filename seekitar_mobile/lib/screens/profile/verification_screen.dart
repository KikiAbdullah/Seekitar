import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter_osm_plugin/flutter_osm_plugin.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:provider/provider.dart';
import '../../models/user.dart';
import '../../providers/app_state.dart';
import '../../services/api_compat.dart';
import '../../services/dio_client.dart';
import '../common/location_picker_screen.dart';

class VerificationScreen extends StatefulWidget {
  const VerificationScreen({super.key});
  @override State<VerificationScreen> createState() => _VerificationScreenState();
}

class _VerificationScreenState extends State<VerificationScreen> {
  final _api = ApiProvider();
  final _nikCtrl = TextEditingController();
  final _addrCtrl = TextEditingController();
  File? _ktp, _selfie;
  double? _lat, _lng;
  bool _gettingLocation = false;
  bool _loading = false;
  bool _agreed = false;
  String? _status;

  // Foto identitas milik sendiri untuk halaman "sudah terverifikasi".
  List<int>? _ktpBytes;
  List<int>? _selfieBytes;

  @override void dispose() { _nikCtrl.dispose(); _addrCtrl.dispose(); super.dispose(); }

  @override
  void initState() {
    super.initState();
    // Kalau sudah terverifikasi, jangan tampilkan form/popup — tampilkan
    // informasi data yang dikirim + aturan. Popup aturan hanya untuk yang
    // belum verifikasi.
    final u = context.read<AppState>().user;
    if (u?.isVerified == true) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _loadMyPhotos());
    } else {
      WidgetsBinding.instance.addPostFrameCallback((_) => _showAgreementDialog());
    }
  }

  /// Ambil foto KTP & selfie milik sendiri agar bisa ditampilkan di halaman
  /// "sudah terverifikasi". Gagal diabaikan (foto tetap opsional).
  Future<void> _loadMyPhotos() async {
    try {
      final k = await _api.verificationPhoto('ktp');
      if (mounted && k.isNotEmpty) setState(() => _ktpBytes = k);
    } catch (_) {}
    try {
      final s = await _api.verificationPhoto('selfie');
      if (mounted && s.isNotEmpty) setState(() => _selfieBytes = s);
    } catch (_) {}
  }

  /// Popup aturan verifikasi: aturan berkas KTP & keamanan data pribadi.
  Future<void> _showAgreementDialog() async {
    if (!mounted) return;
    final agreed = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        final t = Theme.of(ctx);
        return Dialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
          insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              Container(
                width: 64,
                height: 64,
                alignment: Alignment.center,
                decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(22)),
                child: const Icon(Icons.shield_outlined, size: 34, color: Color(0xFF168A4A)),
              ),
              const SizedBox(height: 16),
              Text('Peraturan Verifikasi KTP', textAlign: TextAlign.center, style: t.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
              const SizedBox(height: 6),
              Text('Baca dengan saksama sebelum melanjutkan.', textAlign: TextAlign.center, style: TextStyle(fontSize: 13, color: Colors.grey.shade600)),
              const SizedBox(height: 20),
              _ruleItem(Icons.face_retouching_natural, 'Foto wajah (selfie) harus cocok dengan foto di KTP.'),
              _ruleItem(Icons.badge_outlined, 'Foto KTP asli (bukan fotokopi/scan), utuh, dan terbaca jelas.'),
              _ruleItem(Icons.pin_outlined, 'NIK yang diisi harus 16 digit dan sesuai NIK di KTP.'),
              _ruleItem(Icons.location_on_outlined, 'Alamat domisili & titik peta harus sesuai dengan KTP.'),
              _ruleItem(Icons.verified_user_outlined, 'Berkas yang tidak sesuai akan ditolak dan harus dikirim ulang.'),
              _ruleItem(Icons.security_outlined, 'Data KTP Anda dienkripsi & hanya dipakai untuk verifikasi identitas.'),
              const SizedBox(height: 24),
              SizedBox(
                height: 54,
                child: ElevatedButton.icon(
                  onPressed: () => Navigator.pop(ctx, true),
                  icon: const Icon(Icons.check_circle_outline),
                  label: const Text('Saya Setuju, Lanjutkan', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: t.colorScheme.primary,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () => Navigator.pop(ctx, false),
                child: const Text('Batal', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w600)),
              ),
            ]),
          ),
        );
      },
    );
    if (!mounted) return;
    if (agreed == true) {
      setState(() => _agreed = true);
    } else {
      Navigator.pop(context); // batal — kembali ke halaman sebelumnya
    }
  }

  Widget _ruleItem(IconData icon, String text) => Padding(
    padding: const EdgeInsets.only(bottom: 14),
    child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(10)),
        child: Icon(icon, size: 17, color: const Color(0xFF168A4A)),
      ),
      const SizedBox(width: 12),
      Expanded(child: Text(text, style: TextStyle(fontSize: 13.5, height: 1.4, color: Colors.grey.shade800))),
    ]),
  );

  Future<void> _pick(bool isKtp) async {
    final picker = ImagePicker();
    final x = await picker.pickImage(source: ImageSource.camera, maxWidth: 1280);
    if (x != null) {
      final compressed = await FlutterImageCompress.compressAndGetFile(x.path, '${x.path}_comp.jpg', quality: 75);
      if (compressed != null) setState(() { if (isKtp) _ktp = File(compressed.path); else _selfie = File(compressed.path); });
    }
  }

  /// Buka peta OpenStreetMap (flutter_osm_plugin, gratis tanpa API key) —
  /// user memilih titik domisili di peta, lalu koordinatnya dikembalikan.
  Future<void> _pickLocation() async {
    setState(() => _gettingLocation = true);
    try {
      final point = await Navigator.push<GeoPoint>(
        context,
        MaterialPageRoute(
          builder: (_) => LocationPickerScreen(
            initial: _lat != null && _lng != null
                ? GeoPoint(latitude: _lat!, longitude: _lng!)
                : null,
          ),
        ),
      );
      if (point != null && mounted) {
        setState(() {
          _lat = point.latitude;
          _lng = point.longitude;
        });
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal membuka peta: ${DioClient.friendly(e)}')));
    } finally {
      if (mounted) setState(() => _gettingLocation = false);
    }
  }

  Future<void> _submit() async {
    if (_ktp == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Foto KTP wajib diisi'))); return; }
    if (_selfie == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Selfie wajib diisi'))); return; }
    final nik = _nikCtrl.text.trim();
    if (nik.length != 16 || int.tryParse(nik) == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('NIK harus 16 digit angka'))); return; }
    if (_addrCtrl.text.trim().isEmpty) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Alamat domisili wajib diisi'))); return; }
    if (_lat == null || _lng == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pilih titik lokasi di peta dulu'))); return; }
    setState(() => _loading = true);
    try {
      final res = await _api.uploadKtp(
        _ktp!,
        _selfie!,
        nik: nik,
        address: _addrCtrl.text.trim(),
        latitude: _lat,
        longitude: _lng,
      );
      final userMap = res['user'];
      if (userMap is Map<String, dynamic> && mounted) {
        context.read<AppState>().applyUser(User.fromJson(userMap));
      } else if (mounted) {
        await context.read<AppState>().refreshUser();
      }
      if (mounted) setState(() => _status = 'submitted');
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Berkas terkirim! Ditinjau maksimal 1x24 jam.')));
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: ${DioClient.friendly(e)}'))); }
    if (mounted) setState(() => _loading = false);
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    final u = context.watch<AppState>().user;

    final Widget body;
    if (u?.isVerified == true) {
      body = _buildVerified(t, u);
    } else if (_status == 'submitted') {
      body = Container(
        padding: const EdgeInsets.all(32),
        decoration: BoxDecoration(color: t.colorScheme.primary.withOpacity(0.08), borderRadius: BorderRadius.circular(28)),
        child: Column(children: [
          const Icon(Icons.check_circle, size: 72, color: Color(0xFF168A4A)),
          const SizedBox(height: 20),
          const Text('Berkas Terkirim!', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          Text('Admin akan meninjau identitasmu dalam 1x24 jam. Kamu akan mendapat notifikasi setelah selesai.', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600, height: 1.5)),
          const SizedBox(height: 24),
          OutlinedButton(onPressed: () => Navigator.pop(context), child: const Text('Kembali')),
        ]),
      );
    } else {
      body = !_agreed ? const SizedBox(height: 400) : _buildForm(t);
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Verifikasi Identitas')),
      body: ListView(padding: const EdgeInsets.all(20), children: [body]),
    );
  }

  /// Halaman yang tampil bila identitas SUDAH terverifikasi: informasi data
  /// yang dikirim & aturannya, bukan form unggah lagi.
  Widget _buildVerified(ThemeData t, dynamic u) => Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
    Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.green.shade50,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.green.shade200),
      ),
      child: Column(children: [
        const Icon(Icons.verified, size: 64, color: Color(0xFF168A4A)),
        const SizedBox(height: 12),
        const Text('Identitas Terverifikasi', textAlign: TextAlign.center, style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
        const SizedBox(height: 6),
        Text(
          u?.verifiedAt != null
              ? 'Terverifikasi pada ${u.verifiedAt.day}/${u.verifiedAt.month}/${u.verifiedAt.year}'
              : 'Status: Terverifikasi',
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.green.shade800, fontSize: 13),
        ),
      ]),
    ),
    const SizedBox(height: 20),
    Text('Berkas Identitas', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 10),
    Row(children: [
      Expanded(
        child: _verifiedPhotoCard('Foto KTP', _ktpBytes, Icons.badge_outlined),
      ),
      const SizedBox(width: 12),
      Expanded(
        child: _verifiedPhotoCard('Foto Wajah', _selfieBytes, Icons.face_retouching_natural),
      ),
    ]),
    const SizedBox(height: 24),
    Text('Data yang Dikirim', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 10),
    Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(children: [
          _verifiedRow(Icons.badge_outlined, 'NIK KTP', 'Terverifikasi (16 digit)'),
          const Divider(height: 20),
          _verifiedRow(Icons.home_outlined, 'Alamat Domisili', u?.address ?? '—'),
          const Divider(height: 20),
          _verifiedRow(
            Icons.location_on_outlined,
            'Titik Lokasi',
            u?.latitude != null && u?.longitude != null
                ? '${u.latitude.toStringAsFixed(6)}, ${u.longitude.toStringAsFixed(6)}'
                : '—',
          ),
          const Divider(height: 20),
          _verifiedRow(Icons.person_outline, 'Nama', u?.name ?? '—'),
          const Divider(height: 20),
          _verifiedRow(Icons.phone_outlined, 'Nomor WhatsApp', u?.phone ?? '—'),
        ]),
      ),
    ),
    const SizedBox(height: 24),
    Text('Lokasi Domisili', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 10),
    if (u?.latitude != null && u?.longitude != null)
      Card(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        clipBehavior: Clip.antiAlias,
        child: Column(children: [
          SizedBox(
            height: 160,
            child: _VerifiedMapPreview(
              latitude: u.latitude,
              longitude: u.longitude,
            ),
          ),
          ListTile(
            dense: true,
            leading: const Icon(Icons.fullscreen, size: 18),
            title: const Text('Buka di peta'),
            trailing: const Icon(Icons.chevron_right, size: 20, color: Colors.grey),
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => LocationPickerScreen(
                  initial: GeoPoint(latitude: u.latitude, longitude: u.longitude),
                  title: 'Lokasi Domisili',
                  readOnly: true,
                ),
              ),
            ),
          ),
        ]),
      )
    else
      Card(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Row(children: [
            Icon(Icons.map_outlined, color: Colors.grey.shade400),
            const SizedBox(width: 12),
            Text('Titik lokasi belum tersimpan', style: TextStyle(color: Colors.grey.shade600)),
          ]),
        ),
      ),
    const SizedBox(height: 24),
    Text('Aturan & Keamanan', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 4),
    Text('Aturan yang tetap berlaku untuk akun Anda.', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
    const SizedBox(height: 12),
    Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(20)),
      child: Column(children: [
        _ruleItem(Icons.verified_user_outlined, 'Verifikasi berlaku selama akun Anda aktif.'),
        _ruleItem(Icons.swap_horiz_outlined, 'Mengganti berkas membuka peninjauan ulang oleh admin.'),
        _ruleItem(Icons.security_outlined, 'Data KTP Anda dienkripsi & hanya dipakai untuk verifikasi identitas.'),
        _ruleItem(Icons.block_outlined, 'Berkas palsu / data tidak sesuai dapat berakibat akun diblokir.'),
      ]),
    ),
    const SizedBox(height: 20),
    OutlinedButton(
      onPressed: () => Navigator.pop(context),
      child: const Text('Kembali'),
    ),
  ]);

  Widget _verifiedRow(IconData icon, String label, String value) => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Icon(icon, size: 20, color: const Color(0xFF168A4A)),
      const SizedBox(width: 12),
      SizedBox(width: 110, child: Text(label, style: TextStyle(fontSize: 13, color: Colors.grey.shade600))),
      Expanded(child: Text(value, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600))),
    ],
  );

  Widget _verifiedPhotoCard(String label, List<int>? bytes, IconData placeholderIcon) => Card(
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
    clipBehavior: Clip.antiAlias,
    child: Column(children: [
      SizedBox(
        height: 130,
        child: bytes != null && bytes.isNotEmpty
            ? Image.memory(Uint8List.fromList(bytes), fit: BoxFit.cover, width: double.infinity, errorBuilder: (_, __, ___) => _photoPlaceholder(placeholderIcon))
            : _photoPlaceholder(placeholderIcon),
      ),
      Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade700, fontWeight: FontWeight.w600)),
      ),
    ]),
  );

  Widget _photoPlaceholder(IconData icon) => Container(
    color: Colors.grey.shade100,
    child: Center(child: Icon(icon, size: 40, color: Colors.grey.shade400)),
  );

  Widget _buildForm(ThemeData t) => Column(children: [    Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(16)), child: Row(children: [Icon(Icons.info_outline, color: Colors.blue.shade700), const SizedBox(width: 10), const Expanded(child: Text('Verifikasi diperlukan untuk membuka toko. Data KTP dienkripsi.', style: TextStyle(fontSize: 13)))])),
    const SizedBox(height: 20),
    Text('Foto KTP', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
    const SizedBox(height: 10),
    GestureDetector(
      onTap: () => _pick(true),
      child: Container(
        height: 180, decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(20), border: Border.all(color: Colors.grey.shade300, width: 1.5)),
        child: _ktp != null ? ClipRRect(borderRadius: BorderRadius.circular(18), child: Image.file(_ktp!, fit: BoxFit.cover, width: double.infinity))
            : const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.camera_alt, size: 36, color: Colors.grey), SizedBox(height: 8), Text('Ketuk untuk foto KTP', style: TextStyle(color: Colors.grey))])),
      ),
    ),
    const SizedBox(height: 20),
    Text('Selfie dengan KTP', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
    const SizedBox(height: 10),
    GestureDetector(
      onTap: () => _pick(false),
      child: Container(
        height: 180, decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(20), border: Border.all(color: Colors.grey.shade300, width: 1.5)),
        child: _selfie != null ? ClipRRect(borderRadius: BorderRadius.circular(18), child: Image.file(_selfie!, fit: BoxFit.cover, width: double.infinity))
            : const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.face, size: 36, color: Colors.grey), SizedBox(height: 8), Text('Ketuk untuk selfie', style: TextStyle(color: Colors.grey))])),
      ),
    ),
    const SizedBox(height: 24),
    Text('Data Identitas', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
    const SizedBox(height: 4),
    Text('Isi sesuai yang tertulis di KTP.', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
    const SizedBox(height: 12),
    TextField(controller: _nikCtrl, keyboardType: TextInputType.number, maxLength: 16, decoration: const InputDecoration(labelText: 'NIK (16 digit)', hintText: 'NIK sesuai KTP', counterText: '')),
    const SizedBox(height: 12),
    TextField(controller: _addrCtrl, maxLines: 2, decoration: const InputDecoration(labelText: 'Alamat domisili', hintText: 'Alamat sesuai KTP & domisili (Kabupaten Pasuruan)')),
    const SizedBox(height: 20),
    Text('Titik Lokasi (Peta)', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
    const SizedBox(height: 4),
    Text('Pilih titik lokasi domisili di peta — admin mencocokkan dengan alamat.', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
    const SizedBox(height: 10),
    Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _lat != null ? Colors.green.shade50 : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _lat != null ? Colors.green.shade300 : Colors.grey.shade300),
      ),
      child: Row(children: [
        Icon(_lat != null ? Icons.location_on : Icons.map_outlined, color: _lat != null ? Colors.green : Colors.grey),
        const SizedBox(width: 10),
        Expanded(
          child: _lat != null
              ? Text('${_lat!.toStringAsFixed(6)}, ${_lng!.toStringAsFixed(6)}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14))
              : Text('Belum ada titik lokasi', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
        ),
        TextButton.icon(
          onPressed: _gettingLocation ? null : _pickLocation,
          icon: _gettingLocation
              ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
              : const Icon(Icons.add_location_alt_outlined, size: 18),
          label: Text(_gettingLocation ? 'Membuka…' : 'Pilih di Peta'),
        ),
      ]),
    ),
    const SizedBox(height: 24),
    SizedBox(height: 56, child: ElevatedButton(onPressed: _loading ? null : _submit, child: _loading ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : const Text('Kirim Verifikasi', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)))),
  ]);
}

/// Pratinjau peta titik lokasi pada halaman "sudah terverifikasi".
///
/// Controller dibuat SEKALI di initState — membuatnya di dalam `build`
/// (seperti semula) akan membuat controller baru tiap rebuild sehingga peta
/// tidak pernah tampil. Tile memakai Carto Voyager (gratis, tidak memblokir).
class _VerifiedMapPreview extends StatefulWidget {
  const _VerifiedMapPreview({required this.latitude, required this.longitude});
  final double latitude;
  final double longitude;

  @override
  State<_VerifiedMapPreview> createState() => _VerifiedMapPreviewState();
}

class _VerifiedMapPreviewState extends State<_VerifiedMapPreview> {
  late final MapController _controller;

  @override
  void initState() {
    super.initState();
    _controller = MapController.customLayer(
      initPosition: GeoPoint(latitude: widget.latitude, longitude: widget.longitude),
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

  @override
  Widget build(BuildContext context) {
    return Stack(fit: StackFit.expand, children: [
      OSMFlutter(
        controller: _controller,
        osmOption: const OSMOption(
          showZoomController: false,
          zoomOption: ZoomOption(initZoom: 16, minZoomLevel: 2, maxZoomLevel: 19),
        ),
      ),
      // Penanda pusat peta — posisi domisili.
      IgnorePointer(
        child: Center(child: Icon(Icons.location_on, size: 40, color: const Color(0xFF168A4A))),
      ),
    ]);
  }
}
