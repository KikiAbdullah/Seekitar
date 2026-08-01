import 'package:flutter/material.dart';
import '../models/listing.dart';
import '../services/api_compat.dart';
import 'listing_detail_screen.dart';

class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});
  @override State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  final _api = ApiProvider();
  List<Listing> _items = [];
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final f = await _api.getFavorites(); if (mounted) setState(() { _items = f; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Wishlist')),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _items.isEmpty ? const Center(child: Text('Belum ada favorit')) : ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
      final l = _items[i];
      return Card(
        child: ListTile(
          leading: l.images.isNotEmpty ? ClipRRect(borderRadius: BorderRadius.circular(8), child: Image.network(l.images.first, width: 56, height: 56, fit: BoxFit.cover, errorBuilder: (_,__,___) => Icon(Icons.image, color: Colors.green.shade300))) : Icon(Icons.image, color: Colors.green.shade300),
          title: Text(l.title, maxLines: 1),
          subtitle: Text(l.priceDisplay),
          trailing: IconButton(icon: const Icon(Icons.delete_outline, color: Colors.red), onPressed: () async { await _api.unfavoriteListing(l.id); _load(); }),
          onTap: () => Navigator.push(ctx, MaterialPageRoute(builder: (_) => ListingDetailScreen(listing: l))),
        ),
      );
    }),
  );
}
