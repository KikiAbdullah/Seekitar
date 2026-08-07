import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../models/listing.dart';
import '../../services/api_compat.dart';

class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});
  @override State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  final _api = ApiProvider();
  List<Listing> _items = [];
  bool _loading = true;
  bool _removing = false;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final f = await _api.getFavorites(); if (mounted) setState(() { _items = f; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _remove(Listing l) async {
    setState(() => _removing = true);
    await _api.unfavoriteListing(l.id);
    _load();
    if (mounted) setState(() => _removing = false);
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Wishlist')),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _items.isEmpty
      ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.favorite_border, size: 64, color: Colors.grey.shade300), const SizedBox(height: 16), const Text('Belum ada favorit', style: TextStyle(color: Colors.grey))]))
      : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
        final l = _items[i];
        return Card(child: ListTile(
          leading: l.images.isNotEmpty ? ClipRRect(borderRadius: BorderRadius.circular(8), child: Image.network(l.images.first, width: 56, height: 56, fit: BoxFit.cover, errorBuilder: (_,__,___) => Icon(Icons.image, color: Colors.green.shade300))) : Icon(Icons.image, color: Colors.green.shade300),
          title: Text(l.title, maxLines: 1),
          subtitle: Text(l.priceDisplay),
          trailing: _removing ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2)) : IconButton(icon: const Icon(Icons.delete_outline, color: Colors.red), onPressed: () => _remove(l)),
          onTap: () => ctx.push('/listing/${l.id}', extra: l),
        ));
      })),
  );
}
