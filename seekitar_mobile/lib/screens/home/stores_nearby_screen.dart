import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:geolocator/geolocator.dart';
import '../../models/store.dart';
import '../../services/api_compat.dart';

class StoresNearbyScreen extends StatefulWidget {
  const StoresNearbyScreen({super.key});
  @override State<StoresNearbyScreen> createState() => _StoresNearbyScreenState();
}

class _StoresNearbyScreenState extends State<StoresNearbyScreen> {
  final _api = ApiProvider();
  List<Store> _items = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final pos = await Geolocator.getCurrentPosition();
      final s = await _api.nearbyStores(lat: pos.latitude, lng: pos.longitude);
      if (mounted) setState(() { _items = s; _loading = false; });
    } catch (_) {
      try {
        final s = await _api.nearbyStores(lat: -7.5, lng: 112.0);
        if (mounted) setState(() { _items = s; _loading = false; });
      } catch (e) { if (mounted) setState(() { _error = e.toString(); _loading = false; }); }
    }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Toko Terdekat')),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _error != null ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.cloud_off, size: 48, color: Colors.grey), const SizedBox(height: 12), Text(_error!, style: const TextStyle(color: Colors.grey)), const SizedBox(height: 16), ElevatedButton.icon(onPressed: _load, icon: const Icon(Icons.refresh), label: const Text('Coba Lagi'))]))
      : _items.isEmpty ? const Center(child: Text('Tidak ada toko terdekat'))
      : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
        final s = _items[i];
        return Card(child: ListTile(
          leading: CircleAvatar(radius: 26, backgroundColor: Colors.green.shade50, child: Text(s.name[0].toUpperCase(), style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.w800, fontSize: 16))),
          title: Row(children: [Text(s.name, style: const TextStyle(fontWeight: FontWeight.w700)), if (s.isVerified) const Icon(Icons.verified, size: 16, color: Colors.green)]),
          subtitle: Text('⭐ ${s.ratingAvg.toStringAsFixed(1)} · ${s.reviewsCount} ulasan${s.distanceKm != null ? ' · ${s.distanceKm!.toStringAsFixed(1)} km' : ''}', style: const TextStyle(fontSize: 12)),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => ctx.push('/store/${s.id}/dashboard', extra: s),
        ));
      })),
  );
}
