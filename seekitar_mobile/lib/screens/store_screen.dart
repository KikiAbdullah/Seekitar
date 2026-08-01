import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:geolocator/geolocator.dart';
import '../models/store.dart';
import '../models/dashboard.dart';
import '../services/api_compat.dart';

class CreateStoreScreen extends StatefulWidget {
  const CreateStoreScreen({super.key});
  @override State<CreateStoreScreen> createState() => _CreateStoreScreenState();
}

class _CreateStoreScreenState extends State<CreateStoreScreen> {
  final _api = ApiProvider();
  final _nameCtrl = TextEditingController(), _addrCtrl = TextEditingController();
  bool _loading = false;

  Future<void> _submit() async {
    if (_nameCtrl.text.isEmpty) return;
    setState(() => _loading = true);
    try {
      final pos = await Geolocator.getCurrentPosition();
      await _api.createStore({'name': _nameCtrl.text, 'address': _addrCtrl.text, 'latitude': pos.latitude, 'longitude': pos.longitude, 'service_radius_km': 10, 'accepts_cod': true});
      if (mounted) Navigator.pop(context);
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString()))); }
    setState(() => _loading = false);
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Buka Toko')),
    body: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
      TextField(controller: _nameCtrl, decoration: const InputDecoration(labelText: 'Nama Toko')),
      const SizedBox(height: 16),
      TextField(controller: _addrCtrl, maxLines: 3, decoration: const InputDecoration(labelText: 'Alamat Toko')),
      const SizedBox(height: 24),
      SizedBox(width: double.infinity, child: ElevatedButton(onPressed: _loading ? null : _submit, child: Text(_loading ? 'Membuka...' : 'Buka Toko'))),
    ])),
  );
  @override void dispose() { _nameCtrl.dispose(); _addrCtrl.dispose(); super.dispose(); }
}

class StoreDashboardScreen extends StatefulWidget {
  final String storeId;
  final Store? store;
  const StoreDashboardScreen({super.key, this.storeId = '', this.store});

  @override State<StoreDashboardScreen> createState() => _StoreDashboardScreenState();
}

class _StoreDashboardScreenState extends State<StoreDashboardScreen> {
  final _api = ApiProvider();
  StoreDashboard? _dash;
  Store? _store;
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final sid = widget.store?.id ?? widget.storeId;
      final d = await _api.getStoreDashboard(sid);
      if (mounted) setState(() { _dash = d; _store = widget.store; _loading = false; });
    } catch (_) { if (mounted) setState(() => _loading = false); }
  }

  @override Widget build(BuildContext ctx) {
    if (_loading) return Scaffold(appBar: AppBar(), body: const Center(child: CircularProgressIndicator()));
    final d = _dash;
    final t = Theme.of(ctx);
    final name = _store?.name ?? 'Toko';
    final sid = _store?.id ?? widget.storeId;
    return Scaffold(
      appBar: AppBar(title: Text(name)),
      floatingActionButton: _store != null ? FloatingActionButton.extended(
        onPressed: () => ctx.push('/create-listing', extra: _store).then((_) => _load()),
        icon: const Icon(Icons.add), label: const Text('Pasang Listing'),
        backgroundColor: t.colorScheme.primary,
      ) : null,
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Card(child: Padding(padding: const EdgeInsets.all(16), child: Row(children: [
          CircleAvatar(radius: 30, backgroundColor: Colors.green.shade50, child: Text(name.substring(0, 2).toUpperCase(), style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.bold))),
          const SizedBox(width: 12),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)), if (_store?.isVerified == true) const Icon(Icons.verified, size: 18, color: Colors.green)]),
            GestureDetector(
              onTap: () => ctx.push('/store/$sid/reviews', extra: name),
              child: Text('⭐ ${d?.avgRating.toStringAsFixed(1) ?? "0"} · ${d?.totalReviews ?? 0} ulasan  ›', style: TextStyle(fontSize: 13, color: t.colorScheme.primary)),
            ),
          ])),
        ]))),
        if (d != null) ...[
          const SizedBox(height: 16),
          Row(children: [_stat('Listing', '${d.totalListings}', Icons.inventory, t), _stat('Pesanan', '${d.totalOrders}', Icons.receipt, t), _stat('Menunggu', '${d.pendingOrders}', Icons.hourglass_empty, t)]),
          const SizedBox(height: 10),
          Row(children: [_stat('Omzet', 'Rp ${d.totalRevenue.toStringAsFixed(0)}', Icons.attach_money, t), _stat('Bln Ini', '${d.ordersThisMonth} psn', Icons.trending_up, t)]),
        ],
        const SizedBox(height: 80),
      ]),
    );
  }

  Widget _stat(String label, String value, IconData icon, ThemeData t) => Expanded(child: Card(child: Padding(padding: const EdgeInsets.all(10), child: Column(children: [
    Icon(icon, size: 22, color: t.colorScheme.primary),
    const SizedBox(height: 4),
    Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: t.colorScheme.primary)),
    Text(label, style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
  ]))));
}
