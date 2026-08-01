import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:geolocator/geolocator.dart';
import '../models/address.dart';
import '../services/api_compat.dart';

class AddressScreen extends StatefulWidget {
  const AddressScreen({super.key});
  @override State<AddressScreen> createState() => _AddressScreenState();
}

class _AddressScreenState extends State<AddressScreen> {
  final _api = ApiProvider();
  List<UserAddress> _items = [];
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final a = await _api.getAddresses(); if (mounted) setState(() { _items = a; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _add() async {
    final labelCtrl = TextEditingController(), addrCtrl = TextEditingController();
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
      title: const Text('Alamat Baru'),
      content: Column(mainAxisSize: MainAxisSize.min, children: [
        TextField(controller: labelCtrl, decoration: const InputDecoration(labelText: 'Label (contoh: Rumah)')),
        const SizedBox(height: 12),
        TextField(controller: addrCtrl, maxLines: 3, decoration: const InputDecoration(labelText: 'Alamat Lengkap')),
      ]),
      actions: [TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')), ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Simpan'))],
    ));
    if (ok == true) {
      try {
        Position pos = const Position(latitude: -7.5, longitude: 112.0, timestamp: null, accuracy: 0, altitude: 0, altitudeAccuracy: 0, heading: 0, headingAccuracy: 0, speed: 0, speedAccuracy: 0);
        try { pos = await Geolocator.getCurrentPosition(); } catch (_) {}
        await _api.createAddress({'label': labelCtrl.text, 'address': addrCtrl.text, 'latitude': pos.latitude, 'longitude': pos.longitude});
        _load();
      } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
    }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Alamat')),
    floatingActionButton: FloatingActionButton(onPressed: _add, child: const Icon(Icons.add)),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _items.isEmpty ? const Center(child: Text('Belum ada alamat')) : ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
      final a = _items[i];
      return Card(child: ListTile(
        leading: Icon(a.isDefault ? Icons.location_on : Icons.location_on_outlined, color: a.isDefault ? Colors.green : Colors.grey),
        title: Row(children: [Text(a.label, style: const TextStyle(fontWeight: FontWeight.w600)), if (a.isDefault) const SizedBox(width: 8), if (a.isDefault) Container(padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2), decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(6)), child: const Text('Utama', style: TextStyle(fontSize: 10, color: Colors.green)))]),
        subtitle: Text(a.address, maxLines: 2),
        trailing: PopupMenuButton<String>(onSelected: (v) async {
          if (v == 'default') { await _api.setDefaultAddress(a.id); _load(); }
          if (v == 'delete') { await _api.deleteAddress(a.id); _load(); }
        }, itemBuilder: (_) => [
          if (!a.isDefault) const PopupMenuItem(value: 'default', child: Text('Jadikan Utama')),
          const PopupMenuItem(value: 'delete', child: Text('Hapus', style: TextStyle(color: Colors.red))),
        ]),
      ));
    }),
  );
}
