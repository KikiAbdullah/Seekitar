import 'package:flutter/material.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import '../../models/review.dart';
import '../../services/api_compat.dart';

class StoreReviewsScreen extends StatefulWidget {
  final String storeId; final String storeName;
  const StoreReviewsScreen({super.key, required this.storeId, required this.storeName});
  @override State<StoreReviewsScreen> createState() => _StoreReviewsScreenState();
}

class _StoreReviewsScreenState extends State<StoreReviewsScreen> {
  final _api = ApiProvider(); List<Review> _reviews = []; bool _loading = true;

  @override void initState() { super.initState(); _load(); }
  Future<void> _load() async {
    setState(() => _loading = true);
    try { final r = await _api.getStoreReviews(widget.storeId); if (mounted) setState(() { _reviews = r; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: Text('Ulasan ${widget.storeName}')),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _reviews.isEmpty ? const Center(child: Text('Belum ada ulasan')) : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: _reviews.length, itemBuilder: (_, i) {
      final r = _reviews[i];
      return Card(margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 5), child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          CircleAvatar(radius: 20, backgroundColor: Colors.green.shade50, child: Text(r.reviewerInitials ?? '?', style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.w700))),
          const SizedBox(width: 10),
          Expanded(child: Text(r.reviewerName ?? 'Pengguna', style: const TextStyle(fontWeight: FontWeight.w700))),
          RatingBarIndicator(rating: r.rating.toDouble(), itemSize: 16, itemBuilder: (_,__) => const Icon(Icons.star, color: Colors.amber)),
        ]),
        if (r.comment?.isNotEmpty == true) ...[const SizedBox(height: 8), Text(r.comment!, style: TextStyle(color: Colors.grey.shade700, fontSize: 14))],
      ])));
    })),
  );
}
