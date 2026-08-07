import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_osm_plugin/flutter_osm_plugin.dart';
import '../../models/category.dart';
import '../../models/listing.dart';
import '../../models/store.dart';
import '../../models/dashboard.dart';
import '../../services/api_compat.dart';
import '../../providers/app_state.dart';
import 'package:provider/provider.dart';
import '../common/location_picker_screen.dart';

class CreateStoreScreen extends StatefulWidget { const CreateStoreScreen({super.key}); @override State<CreateStoreScreen> createState() => _CreateStoreScreenState(); }

class _CreateStoreScreenState extends State<CreateStoreScreen> {
  final _api = ApiProvider();
  final _nameCtrl = TextEditingController(), _addrCtrl = TextEditingController();
  final _bankCtrl = TextEditingController(), _bankNameCtrl = TextEditingController(), _npwpCtrl = TextEditingController();
  File? _photo;
  List<String> _types = ['goods'];
  final Set<int> _cats = {};
  List<Category> _categories = [];
  double _radiusKm = 5;
  bool _acceptsCod = true, _offersDelivery = false, _allowsPickup = true;
  bool _loading = false, _loadingCats = true;
  double? _lat, _lng;
  bool _gettingLocation = false;

  final PageController _pageCtrl = PageController();
  int _step = 0;
  static const _totalSteps = 3;

  // Jam operasional — default Senin–Jumat 08:00–17:00.
  final Map<String, Map<String, String>> _hours = {};
  final List<String> _days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
  bool _openAllDay = true;

  @override void initState() { super.initState(); _loadCategories(); }

  @override void dispose() {
    _pageCtrl.dispose();
    _nameCtrl.dispose(); _addrCtrl.dispose(); _bankCtrl.dispose(); _bankNameCtrl.dispose(); _npwpCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadCategories() async {
    setState(() => _loadingCats = true);
    try { final c = await _api.getCategories(); if (mounted) setState(() { _categories = c; _loadingCats = false; }); }
    catch (_) { if (mounted) setState(() => _loadingCats = false); }
  }

  /// Ambil semua kategori (induk + anak) sebagai pilihan flat untuk toko.
  List<Category> _allCats() {
    final out = <Category>[];
    void walk(List<Category> list) { for (final c in list) { out.add(c); walk(c.children); } }
    walk(_categories);
    return out;
  }

  Future<void> _pickPhoto() async {
    final x = await ImagePicker().pickImage(source: ImageSource.camera, maxWidth: 1280);
    if (x != null) {
      final compressed = await FlutterImageCompress.compressAndGetFile(x.path, '${x.path}_comp.jpg', quality: 75);
      if (compressed != null) setState(() => _photo = File(compressed.path));
    }
  }

  Future<void> _pickLocation() async {
    setState(() => _gettingLocation = true);
    try {
      final point = await Navigator.push<GeoPoint>(
        context,
        MaterialPageRoute(builder: (_) => LocationPickerScreen(
          initial: _lat != null && _lng != null ? GeoPoint(latitude: _lat!, longitude: _lng!) : null,
        )),
      );
      if (point != null && mounted) setState(() { _lat = point.latitude; _lng = point.longitude; });
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal membuka peta: $e')));
    } finally { if (mounted) setState(() => _gettingLocation = false); }
  }

  void _toggleType(String t) => setState(() { if (_types.contains(t) && _types.length > 1) _types.remove(t); else if (!_types.contains(t)) _types.add(t); });

  /// Validasi langkah sebelum pindah ke berikutnya.
  String? _validateStep(int step) {
    switch (step) {
      case 0:
        if (_nameCtrl.text.trim().length < 3) return 'Nama toko minimal 3 huruf';
        if (_lat == null || _lng == null) return 'Pilih titik lokasi toko di peta dulu';
        return null;
      case 1:
        if (_cats.isEmpty) return 'Pilih minimal 1 kategori usaha';
        return null;
      case 2:
        if (!_offersDelivery && !_allowsPickup) return 'Toko harus melayani antar atau ambil di tempat';
        return null;
    }
    return null;
  }

  void _next() {
    final err = _validateStep(_step);
    if (err != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(err)));
      return;
    }
    if (_step < _totalSteps - 1) {
      _pageCtrl.nextPage(duration: const Duration(milliseconds: 300), curve: Curves.easeOut);
    } else {
      _submit();
    }
  }

  Future<void> _submit() async {
    final u = context.read<AppState>().user;
    if (u == null || !u.isVerified) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text('Verifikasi KTP dulu untuk buka toko.'),
        action: SnackBarAction(label: 'Verifikasi', onPressed: () => context.push('/verification')),
      ));
      return;
    }

    setState(() => _loading = true);
    try {
      // Jam operasional: bila "buka setiap hari", semua hari memakai jam yang sama.
      final hours = _openAllDay && _hours['senin'] != null
          ? {for (final d in _days) d: Map<String, String>.from(_hours['senin']!)}
          : _hours;

      await _api.createStore({
        'name': _nameCtrl.text.trim(),
        'address': _addrCtrl.text.trim(),
        'latitude': _lat,
        'longitude': _lng,
        'store_type': _types,
        'category_ids': _cats.toList(),
        'service_radius_km': _radiusKm,
        'accepts_cod': _acceptsCod,
        'offers_delivery': _offersDelivery,
        'allows_pickup': _allowsPickup,
        if (hours.isNotEmpty) 'operating_hours': hours,
        if (_bankCtrl.text.trim().isNotEmpty) 'bank_account': _bankCtrl.text.trim(),
        if (_bankNameCtrl.text.trim().isNotEmpty) 'bank_account_name': _bankNameCtrl.text.trim(),
        if (_npwpCtrl.text.trim().isNotEmpty) 'npwp': _npwpCtrl.text.trim(),
      }, photo: _photo);
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Toko berhasil dibuka! Menunggu verifikasi admin.'))); Navigator.pop(context, true); }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))));
    }
    if (mounted) setState(() => _loading = false);
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    return Scaffold(
      appBar: AppBar(title: const Text('Buka Toko')),
      body: Column(children: [
        _stepIndicator(t),
        Expanded(
          child: PageView(
            controller: _pageCtrl,
            physics: const NeverScrollableScrollPhysics(),
            onPageChanged: (i) => setState(() => _step = i),
            children: [
              _stepInfo(t, Icons.storefront_outlined, 'Informasi Toko', 'Lengkapi nama, alamat, dan titik lokasi toko kamu.'),
              _stepUsaha(t),
              _stepLayanan(t),
            ],
          ),
        ),
        _navBar(t),
      ]),
    );
  }

  // ── Indikator progres ──────────────────────────────
  Widget _stepIndicator(ThemeData t) => Padding(
    padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
    child: Column(children: [
      Row(children: [
        for (var i = 0; i < _totalSteps; i++) ...[
          Expanded(
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              height: 5,
              decoration: BoxDecoration(
                color: i <= _step ? t.colorScheme.primary : Colors.grey.shade200,
                borderRadius: BorderRadius.circular(3),
              ),
            ),
          ),
          if (i < _totalSteps - 1) const SizedBox(width: 8),
        ],
      ]),
      const SizedBox(height: 8),
      Row(children: [
        for (var i = 0; i < _totalSteps; i++) ...[
          Text(
            ['Informasi', 'Usaha', 'Layanan'][i],
            style: TextStyle(
              fontSize: 11,
              fontWeight: i == _step ? FontWeight.w700 : FontWeight.w500,
              color: i == _step ? t.colorScheme.primary : Colors.grey.shade400,
            ),
          ),
          if (i < _totalSteps - 1)
            Expanded(child: Divider(color: Colors.grey.shade200, indent: 8, endIndent: 8)),
        ],
      ]),
    ]),
  );

  // ── Bungkus isi langkah dengan judul ───────────────
  Widget _stepInfo(ThemeData t, IconData icon, String title, String desc) => SingleChildScrollView(
    padding: const EdgeInsets.all(20),
    child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Row(children: [
        Container(width: 48, height: 48, decoration: BoxDecoration(color: t.colorScheme.primary.withOpacity(0.12), borderRadius: BorderRadius.circular(16)), child: Icon(icon, size: 26, color: t.colorScheme.primary)),
        const SizedBox(width: 14),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: t.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
          Text(desc, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
        ])),
      ]),
      const SizedBox(height: 20),
      _stepContent(t, _step),
    ]),
  );

  Widget _stepContent(ThemeData t, int step) {
    switch (step) {
      case 0: return _contentInfo(t);
      case 1: return _contentUsaha(t);
      default: return _contentLayanan(t);
    }
  }

  Widget _stepUsaha(ThemeData t) => _stepInfo(t, Icons.sell_outlined, 'Jenis Usaha', 'Tentukan jenis & kategori yang kamu jual.');
  Widget _stepLayanan(ThemeData t) => _stepInfo(t, Icons.support_agent_outlined, 'Layanan & Pembayaran', 'Atur jangkauan layanan dan rekening pembayaran.');

  // ── Langkah 1: Informasi & Lokasi ──────────────────
  Widget _contentInfo(ThemeData t) => Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
    GestureDetector(
      onTap: _pickPhoto,
      child: Container(
        height: 150,
        decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(20), border: Border.all(color: Colors.grey.shade300, width: 1.5)),
        child: _photo != null ? ClipRRect(borderRadius: BorderRadius.circular(18), child: Image.file(_photo!, fit: BoxFit.cover, width: double.infinity))
            : const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.add_a_photo_outlined, size: 34, color: Colors.grey), SizedBox(height: 6), Text('Foto Toko (opsional)', style: TextStyle(color: Colors.grey))])),
      ),
    ),
    const SizedBox(height: 20),
    TextField(controller: _nameCtrl, decoration: const InputDecoration(labelText: 'Nama Toko', hintText: 'Cth: Warung Mbak Sari', prefixIcon: Icon(Icons.store_outlined))),
    const SizedBox(height: 12),
    TextField(controller: _addrCtrl, maxLines: 2, decoration: const InputDecoration(labelText: 'Alamat Toko', hintText: 'Alamat lengkap toko Anda', prefixIcon: Icon(Icons.home_outlined))),
    const SizedBox(height: 20),
    Text('Titik Lokasi Toko', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 4),
    Text('Pilih lokasi toko di peta — admin mencocokkan dengan kondisi asli.', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
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
          icon: _gettingLocation ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.add_location_alt_outlined, size: 18),
          label: Text(_gettingLocation ? 'Membuka…' : 'Pilih di Peta'),
        ),
      ]),
    ),
  ]);

  // ── Langkah 2: Jenis & Kategori ────────────────────
  Widget _contentUsaha(ThemeData t) => Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
    Text('Jenis Usaha', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 10),
    Wrap(spacing: 8, runSpacing: 8, children: [
      for (final o in [['goods', 'Barang'], ['services', 'Jasa'], ['rental', 'Sewa']])
        FilterChip(selected: _types.contains(o[0]), label: Text(o[1]), onSelected: (_) => _toggleType(o[0]), selectedColor: t.colorScheme.primary.withOpacity(0.15), checkmarkColor: t.colorScheme.primary),
    ]),
    const SizedBox(height: 24),
    Text('Kategori Usaha', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 4),
    Text('Pilih minimal 1 kategori yang dijual.', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
    const SizedBox(height: 10),
    _loadingCats
        ? const Center(child: Padding(padding: EdgeInsets.all(12), child: CircularProgressIndicator(strokeWidth: 2)))
        : _allCats().isEmpty
            ? Text('Tidak ada kategori tersedia.', style: TextStyle(color: Colors.grey.shade600))
            : Wrap(spacing: 8, runSpacing: 8, children: [
                for (final c in _allCats())
                  FilterChip(
                    selected: _cats.contains(c.id),
                    label: Text(c.name),
                    onSelected: (sel) => setState(() { if (sel) _cats.add(c.id); else _cats.remove(c.id); }),
                    selectedColor: t.colorScheme.primary.withOpacity(0.15),
                    checkmarkColor: t.colorScheme.primary,
                  ),
              ]),
  ]);

  // ── Langkah 3: Layanan, Jam, Rekening ──────────────
  Widget _contentLayanan(ThemeData t) => Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
    Text('Jangkauan & Layanan', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 10),
    Container(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(16)),
      child: Column(children: [
        Row(children: [const Icon(Icons.radar, size: 20, color: Color(0xFF168A4A)), const SizedBox(width: 10), const Expanded(child: Text('Radius layanan (km)')), Text('${_radiusKm.toStringAsFixed(0)} km', style: const TextStyle(fontWeight: FontWeight.w700))]),
        Slider(value: _radiusKm, min: 1, max: 50, divisions: 49, label: '${_radiusKm.toStringAsFixed(0)} km', onChanged: (v) => setState(() => _radiusKm = v)),
      ]),
    ),
    SwitchListTile(
      value: _acceptsCod,
      onChanged: (v) => setState(() => _acceptsCod = v),
      title: const Text('Terima COD (Bayar di Tempat)'),
      secondary: const Icon(Icons.payments_outlined, color: Color(0xFF168A4A)),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    ),
    SwitchListTile(
      value: _offersDelivery,
      onChanged: (v) => setState(() => _offersDelivery = v),
      title: const Text('Layanan Antar (Delivery)'),
      secondary: const Icon(Icons.delivery_dining_outlined, color: Color(0xFF168A4A)),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    ),
    SwitchListTile(
      value: _allowsPickup,
      onChanged: (v) => setState(() => _allowsPickup = v),
      title: const Text('Layanan Ambil di Tempat (Pickup)'),
      secondary: const Icon(Icons.store_mall_directory_outlined, color: Color(0xFF168A4A)),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    ),
    const SizedBox(height: 16),
    Text('Jam Operasional', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 4),
    Text('Jam buka & tutup untuk seluruh hari.', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
    const SizedBox(height: 10),
    Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(16)),
      child: Column(children: [
        SwitchListTile(
          value: _openAllDay,
          onChanged: (v) => setState(() => _openAllDay = v),
          title: const Text('Buka setiap hari dengan jam yang sama'),
          contentPadding: EdgeInsets.zero,
        ),
        Row(children: [
          Expanded(child: TextField(
            decoration: const InputDecoration(labelText: 'Jam Buka', hintText: '08:00'),
            keyboardType: TextInputType.datetime,
            onChanged: (v) => setState(() => _hours['senin'] = {...?_hours['senin'], 'open': v}),
          )),
          const SizedBox(width: 12),
          Expanded(child: TextField(
            decoration: const InputDecoration(labelText: 'Jam Tutup', hintText: '17:00'),
            keyboardType: TextInputType.datetime,
            onChanged: (v) => setState(() => _hours['senin'] = {...?_hours['senin'], 'close': v}),
          )),
        ]),
      ]),
    ),
    const SizedBox(height: 16),
    Text('Rekening & NPWP (opsional)', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
    const SizedBox(height: 12),
    TextField(controller: _bankNameCtrl, decoration: const InputDecoration(labelText: 'Nama Bank', hintText: 'Cth: BCA', prefixIcon: Icon(Icons.account_balance_outlined))),
    const SizedBox(height: 12),
    TextField(controller: _bankCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Nomor Rekening', prefixIcon: Icon(Icons.pin_outlined))),
    const SizedBox(height: 12),
    TextField(controller: _npwpCtrl, decoration: const InputDecoration(labelText: 'NPWP (opsional)', prefixIcon: Icon(Icons.numbers_outlined))),
  ]);

  // ── Navigasi bawah ─────────────────────────────────
  Widget _navBar(ThemeData t) => SafeArea(
    child: Container(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
      decoration: BoxDecoration(color: Colors.white, boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 12, offset: const Offset(0, -2))]),
      child: Row(children: [
        if (_step > 0)
          Expanded(
            child: OutlinedButton.icon(
              onPressed: _loading ? null : () => _pageCtrl.previousPage(duration: const Duration(milliseconds: 300), curve: Curves.easeOut),
              icon: const Icon(Icons.arrow_back, size: 18),
              label: const Text('Kembali'),
              style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(54)),
            ),
          )
        else
          const Spacer(),
        if (_step > 0) const SizedBox(width: 12),
        Expanded(
          child: ElevatedButton.icon(
            onPressed: _loading ? null : _next,
            icon: _loading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : Icon(_step < _totalSteps - 1 ? Icons.arrow_forward : Icons.check, size: 18),
            label: Text(_loading ? 'Mengirim…' : (_step < _totalSteps - 1 ? 'Lanjut' : 'Buka Toko'), style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
            style: ElevatedButton.styleFrom(minimumSize: const Size.fromHeight(54)),
          ),
        ),
      ]),
    ),
  );
}

class StoreDashboardScreen extends StatefulWidget { final String storeId; final Store? store; const StoreDashboardScreen({super.key, this.storeId = '', this.store}); @override State<StoreDashboardScreen> createState() => _StoreDashboardScreenState(); }
class _StoreDashboardScreenState extends State<StoreDashboardScreen> {
  final _api = ApiProvider(); StoreDashboard? _dash; Store? _store; bool _loading = true;
  @override void initState() { super.initState(); _load(); }
  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final sid = widget.store?.id ?? widget.storeId;
      // Bila hanya storeId yang diteruskan (mis. dari Profil), ambil objek
      // toko dari API — FAB "Pasang Listing" dan nama toko butuh store != null.
      var s = widget.store;
      if (s == null) s = await _api.getStore(sid);
      final d = await _api.getStoreDashboard(sid);
      if (mounted) setState(() { _dash = d; _store = s; _loading = false; });
    }
    catch (_) { if (mounted) setState(() { _store = widget.store; _loading = false; }); }
  }
  Future<void> _deleteListing(Listing l) async {
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(title: const Text('Hapus Listing?'), content: Text('Hapus "${l.title}"?'), actions: [TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')), ElevatedButton(onPressed: () => Navigator.pop(ctx, true), style: ElevatedButton.styleFrom(backgroundColor: Colors.red), child: const Text('Hapus'))]));
    if (ok == true) { await _api.deleteListing(l.id); _load(); }
  }
  @override Widget build(BuildContext ctx) {
    if (_loading) return Scaffold(appBar: AppBar(), body: const Center(child: CircularProgressIndicator()));
    final d = _dash; final t = Theme.of(ctx); final name = _store?.name ?? 'Toko'; final sid = _store?.id ?? widget.storeId;
    final isOwner = _store?.ownerId == context.read<AppState>().user?.id;
    // Hanya toko yang SUDAH diverifikasi admin yang boleh pasang listing.
    final canAdd = isOwner && _store?.isVerified == true;
    return Scaffold(
      appBar: AppBar(title: Text(name)),
      floatingActionButton: canAdd ? FloatingActionButton.extended(onPressed: () => ctx.push('/create-listing', extra: _store).then((_) => _load()), icon: const Icon(Icons.add), label: const Text('Pasang Listing'), backgroundColor: t.colorScheme.primary) : null,
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Card(child: Padding(padding: const EdgeInsets.all(16), child: Row(children: [
          CircleAvatar(radius: 30, backgroundColor: Colors.green.shade50, child: Text(name.substring(0, 2).toUpperCase(), style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.bold))),
          const SizedBox(width: 12), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)), if (_store?.isVerified == true) const Icon(Icons.verified, size: 18, color: Colors.green)]),
            GestureDetector(onTap: () => ctx.push('/store/$sid/reviews', extra: name), child: Text('⭐ ${d?.avgRating.toStringAsFixed(1) ?? "0"} · ${d?.totalReviews ?? 0} ulasan  ›', style: TextStyle(fontSize: 13, color: t.colorScheme.primary))),
          ])),
        ]))),
        if (d != null) ...[
          const SizedBox(height: 16),
          Row(children: [_stat('Listing', '${d.totalListings}', Icons.inventory, t), _stat('Pesanan', '${d.totalOrders}', Icons.receipt, t), _stat('Menunggu', '${d.pendingOrders}', Icons.hourglass_empty, t)]),
          const SizedBox(height: 10),
          Row(children: [_stat('Omzet', 'Rp ${d.totalRevenue.toStringAsFixed(0)}', Icons.attach_money, t), _stat('Bln Ini', '${d.ordersThisMonth} psn', Icons.trending_up, t)]),
        ],
        // Status toko + tombol pasang listing hanya bila terverifikasi.
        if (isOwner && !(_store?.isVerified == true)) ...[
          const SizedBox(height: 16),
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            child: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
              Icon(Icons.hourglass_top_outlined, size: 34, color: Colors.orange.shade700),
              const SizedBox(height: 10),
              Text(_store?.status == 'rejected'
                  ? 'Toko Anda ditolak admin.'
                  : _store?.status == 'blocked'
                      ? 'Toko Anda diblokir.'
                      : 'Toko masih menunggu verifikasi admin.'),
              const SizedBox(height: 4),
              Text('Listing hanya bisa dipasang setelah toko terverifikasi.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
            ])),
          ),
        ],
        const SizedBox(height: 80),
      ]),
    );
  }
  Widget _stat(String label, String value, IconData icon, ThemeData t) => Expanded(child: Card(child: Padding(padding: const EdgeInsets.all(10), child: Column(children: [Icon(icon, size: 22, color: t.colorScheme.primary), const SizedBox(height: 4), Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: t.colorScheme.primary)), Text(label, style: TextStyle(fontSize: 10, color: Colors.grey.shade600))]))));
}
