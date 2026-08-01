import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:geolocator/geolocator.dart';
import '../models/listing.dart';
import '../services/api_compat.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});
  @override State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final _api = ApiProvider();
  final _searchCtrl = TextEditingController();
  List<Listing> _listings = [], _suggestions = [];
  String? _filter;
  bool _loading = false;
  double? _lat, _lng;

  @override void initState() { super.initState(); _getLoc(); }
  @override void dispose() { _searchCtrl.dispose(); super.dispose(); }

  Future<void> _getLoc() async {
    try { final p = await Geolocator.getCurrentPosition(); setState(() { _lat = p.latitude; _lng = p.longitude; }); _search(); }
    catch (_) { setState(() { _lat = -7.5; _lng = 112.0; }); _search(); }
  }

  Future<void> _search() async {
    if (_lat == null) return;
    setState(() => _loading = true);
    try { final items = await _api.getListings(lat: _lat!, lng: _lng!, keyword: _searchCtrl.text.isEmpty ? null : _searchCtrl.text, type: _filter); if (mounted) setState(() => _listings = items); }
    catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _onChanged(String q) async {
    if (q.length >= 2) { try { final s = await _api.searchSuggestions(q, type: _filter); if (mounted) setState(() => _suggestions = s); } catch (_) {} }
    else { if (mounted) setState(() => _suggestions = []); }
  }

  void _clear() { _searchCtrl.clear(); setState(() => _suggestions = []); _search(); }

  @override Widget build(BuildContext ctx) => Scaffold(
    body: SafeArea(child: Column(children: [
      Padding(padding: const EdgeInsets.fromLTRB(16, 8, 8, 8), child: Row(children: [
        Expanded(child: Container(decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(16)), child: TextField(controller: _searchCtrl, onChanged: _onChanged, onSubmitted: (_) { _search(); setState(() => _suggestions = []); }, decoration: const InputDecoration(hintText: 'Cari barang, jasa, sewa...', prefixIcon: Icon(Icons.search, size: 22), suffixIcon: _searchCtrl.text.isNotEmpty ? Icon(Icons.close, size: 18) : null, border: InputBorder.none, filled: false, fillColor: Colors.transparent)))),
      ])),
      SingleChildScrollView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), child: Row(children: [
        _chip(null, 'Semua'), const SizedBox(width: 8),
        _chip('product', 'Barang'), const SizedBox(width: 8),
        _chip('service', 'Jasa'), const SizedBox(width: 8),
        _chip('rental', 'Sewa'),
      ])),
      Expanded(child: _suggestions.isNotEmpty
        ? ListView.separated(padding: const EdgeInsets.symmetric(vertical: 4), itemCount: _suggestions.length, separatorBuilder: (_,__) => const Divider(height: 1, indent: 72), itemBuilder: (_, i) => ListTile(
            leading: ClipRRect(borderRadius: BorderRadius.circular(12), child: _suggestions[i].images.isNotEmpty ? Image.network(_suggestions[i].images.first, width: 48, height: 48, fit: BoxFit.cover) : Container(width: 48, height: 48, color: Colors.green.shade50)),
            title: Text(_suggestions[i].title, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
            subtitle: Text(_suggestions[i].priceDisplay),
            onTap: () { ctx.push('/listing/${_suggestions[i].id}', extra: _suggestions[i]); setState(() => _suggestions = []); _searchCtrl.clear(); },
          ))
        : _loading ? const Center(child: CircularProgressIndicator())
        : _listings.isEmpty ? const Center(child: Text('Tidak ada hasil', style: TextStyle(color: Colors.grey)))
        : ListView.builder(itemCount: _listings.length, itemBuilder: (_, i) { final l = _listings[i];
            return Card(margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4), child: ListTile(
              contentPadding: const EdgeInsets.all(12),
              leading: ClipRRect(borderRadius: BorderRadius.circular(14), child: l.images.isNotEmpty ? Image.network(l.images.first, width: 64, height: 64, fit: BoxFit.cover, errorBuilder: (_,__,___) => Container(width: 64, height: 64, color: Colors.green.shade50, child: const Icon(Icons.image, color: Colors.green))) : Container(width: 64, height: 64, color: Colors.green.shade50, child: const Icon(Icons.image, color: Colors.green))),
              title: Text(l.title, maxLines: 1, style: const TextStyle(fontWeight: FontWeight.w700)),
              subtitle: Text('${l.storeName ?? ''} · ${l.priceDisplay}', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
              onTap: () => ctx.push('/listing/${l.id}', extra: l),
            ));
          }),
    ])),
  );

  Widget _chip(String? type, String label) => GestureDetector(
    onTap: () => setState(() { _filter = type; _search(); }),
    child: Container(padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10), decoration: BoxDecoration(color: _filter == type ? Theme.of(context).colorScheme.primary : Colors.grey.shade100, borderRadius: BorderRadius.circular(20)), child: Text(label, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _filter == type ? Colors.white : Colors.grey.shade600))),
  );
}
