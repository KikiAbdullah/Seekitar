import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../models/category.dart';
import '../services/api_compat.dart';

class CategoryBrowseScreen extends StatefulWidget {
  const CategoryBrowseScreen({super.key});
  @override State<CategoryBrowseScreen> createState() => _CategoryBrowseScreenState();
}

class _CategoryBrowseScreenState extends State<CategoryBrowseScreen> {
  final _api = ApiProvider();
  List<Category> _items = [];
  bool _loading = true;
  Category? _selected;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final c = await _api.getCategories(); if (mounted) setState(() { _items = c; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  static const _icons = [Icons.home, Icons.shopping_bag, Icons.build, Icons.local_shipping, Icons.phone_android, Icons.chair, Icons.restaurant, Icons.brush, Icons.pets, Icons.sports_esports, Icons.music_note, Icons.camera_alt, Icons.book, Icons.medical_services, Icons.more_horiz];

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: Text(_selected != null ? _selected!.name : 'Kategori')),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _selected != null ? _subCategoryView(ctx) : _mainGrid(ctx),
  );

  Widget _mainGrid(BuildContext ctx) => RefreshIndicator(onRefresh: _load, child: GridView.builder(padding: const EdgeInsets.all(16), gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 3, crossAxisSpacing: 12, mainAxisSpacing: 12, childAspectRatio: 0.9), itemCount: _items.length, itemBuilder: (_, i) {
    final c = _items[i];
    return GestureDetector(
      onTap: () => setState(() => _selected = c),
      child: Container(decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8)]), child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
        Container(width: 56, height: 56, decoration: BoxDecoration(color: Theme.of(ctx).colorScheme.primary.withOpacity(0.1), borderRadius: BorderRadius.circular(16)), child: Icon(_icons[i % _icons.length], color: Theme.of(ctx).colorScheme.primary, size: 28)),
        const SizedBox(height: 10),
        Text(c.name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13), textAlign: TextAlign.center, maxLines: 2),
        if (c.children.isNotEmpty) Text('${c.children.length} sub', style: TextStyle(fontSize: 10, color: Colors.grey.shade500)),
      ])),
    );
  }));

  Widget _subCategoryView(BuildContext ctx) {
    final children = _selected!.children;
    if (children.isEmpty) return const Center(child: Text('Tidak ada subkategori'));
    return ListView(padding: const EdgeInsets.all(16), children: [
      Container(padding: const EdgeInsets.all(16), margin: const EdgeInsets.only(bottom: 12), decoration: BoxDecoration(color: Theme.of(ctx).colorScheme.primary.withOpacity(0.08), borderRadius: BorderRadius.circular(16)), child: Row(children: [TextButton.icon(onPressed: () => setState(() => _selected = null), icon: const Icon(Icons.arrow_back, size: 18), label: const Text('Kembali')), const Spacer(), Text('${children.length} subkategori', style: TextStyle(color: Colors.grey.shade600, fontSize: 12))])),
      ...children.map((sub) => ListTile(
        leading: Container(width: 44, height: 44, decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(12)), child: const Icon(Icons.label, color: Colors.green, size: 22)),
        title: Text(sub.name, style: const TextStyle(fontWeight: FontWeight.w600)),
        trailing: const Icon(Icons.chevron_right, size: 18),
        onTap: () => ctx.push('/category-search?id=${sub.id}&label=${Uri.encodeComponent(sub.name)}'),
      )),
    ]);
  }
}
