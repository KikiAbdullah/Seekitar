import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:geolocator/geolocator.dart';
import '../models/category.dart';
import '../models/customer_request.dart';
import '../services/api_compat.dart';

class RequestsScreen extends StatefulWidget {
  const RequestsScreen({super.key});
  @override State<RequestsScreen> createState() => _RequestsScreenState();
}

class _RequestsScreenState extends State<RequestsScreen> with SingleTickerProviderStateMixin {
  final _api = ApiProvider();
  late TabController _tabCtrl;
  List<CustomerRequest> _nearby = [], _mine = [];
  bool _loading = true;
  BuildContext get ctx => context;

  @override void initState() { super.initState(); _tabCtrl = TabController(length: 2, vsync: this); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final pos = await Geolocator.getCurrentPosition();
      final results = await Future.wait<Object>([_api.getRequests(lat: pos.latitude, lng: pos.longitude), _api.myRequests()]);
      if (!mounted) return;
      setState(() { _nearby = results[0] as List<CustomerRequest>; _mine = results[1] as List<CustomerRequest>; _loading = false; });
    } catch (_) { setState(() => _loading = false); }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Kebutuhan Sekitar'), bottom: TabBar(controller: _tabCtrl, tabs: const [Tab(text: 'Terdekat'), Tab(text: 'Saya')])),
    body: _loading ? const Center(child: CircularProgressIndicator()) : TabBarView(controller: _tabCtrl, children: [
      RefreshIndicator(onRefresh: _load, child: _nearby.isEmpty ? const Center(child: Text('Belum ada permintaan')) : ListView.builder(itemCount: _nearby.length, itemBuilder: (_, i) => _card(_nearby[i]))),
      RefreshIndicator(onRefresh: _load, child: _mine.isEmpty ? const Center(child: Text('Belum ada permintaan')) : ListView.builder(itemCount: _mine.length, itemBuilder: (_, i) => _card(_mine[i]))),
    ]),
    floatingActionButton: FloatingActionButton(onPressed: () => ctx.push('/create-request').then((_) => _load()), child: const Icon(Icons.add)),
  );

  Widget _card(CustomerRequest r) => Card(child: ListTile(
    leading: CircleAvatar(backgroundColor: Colors.orange.shade50, child: Text(r.userInitials ?? '?', style: TextStyle(color: Colors.orange.shade700, fontWeight: FontWeight.w600))),
    title: Text(r.title, maxLines: 1),
    subtitle: Text(r.timeLeft, style: TextStyle(color: r.isExpired ? Colors.red : Colors.green.shade700, fontSize: 12)),
    trailing: Chip(label: Text('${r.offersCount} tawaran', style: const TextStyle(fontSize: 11))),
    onTap: () => ctx.push('/request/${r.id}', extra: r),
  ));
}

class CreateRequestScreen extends StatefulWidget {
  const CreateRequestScreen({super.key});
  @override State<CreateRequestScreen> createState() => _CreateRequestScreenState();
}

class _CreateRequestScreenState extends State<CreateRequestScreen> {
  final _api = ApiProvider();
  final _titleCtrl = TextEditingController(), _descCtrl = TextEditingController();
  bool _loading = false;
  List<Category> _cats = [];
  int? _catId;
  bool _catsLoading = true;

  @override void initState() { super.initState(); _loadCats(); }

  Future<void> _loadCats() async {
    try {
      final cats = await _api.getCategories();
      if (mounted) setState(() { _cats = cats; _catId = cats.isNotEmpty ? cats.first.id : null; _catsLoading = false; });
    } catch (_) { if (mounted) setState(() => _catsLoading = false); }
  }

  List<(int, String)> get _catOptions {
    final out = <(int, String)>[];
    for (final c in _cats) {
      out.add((c.id, c.name));
      for (final ch in c.children) out.add((ch.id, '  ${ch.name}'));
    }
    return out;
  }

  Future<void> _submit() async {
    if (_catId == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pilih kategori terlebih dahulu'))); return; }
    setState(() => _loading = true);
    try {
      final pos = await Geolocator.getCurrentPosition();
      await _api.createRequest({'title': _titleCtrl.text, 'description': _descCtrl.text, 'category_id': _catId, 'latitude': pos.latitude, 'longitude': pos.longitude, 'radius_km': 15});
      if (mounted) Navigator.pop(context);
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString()))); }
    setState(() => _loading = false);
  }

  @override Widget build(BuildContext ctx) => Scaffold(appBar: AppBar(title: const Text('Pasang Kebutuhan')), body: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
    _catsLoading
      ? const LinearProgressIndicator()
      : DropdownButtonFormField<int>(
          initialValue: _catId,
          items: _catOptions.map((o) => DropdownMenuItem<int>(value: o.$1, child: Text(o.$2))).toList(),
          onChanged: (v) => setState(() => _catId = v),
          decoration: const InputDecoration(labelText: 'Kategori'),
        ),
    const SizedBox(height: 16),
    TextField(controller: _titleCtrl, decoration: const InputDecoration(labelText: 'Judul Kebutuhan', hintText: 'Cth: Cari tukang cat dinding')),
    const SizedBox(height: 16),
    TextField(controller: _descCtrl, maxLines: 4, decoration: const InputDecoration(labelText: 'Deskripsi', hintText: 'Jelaskan detail kebutuhanmu...')),
    const SizedBox(height: 24),
    SizedBox(width: double.infinity, child: ElevatedButton(onPressed: _loading ? null : _submit, child: Text(_loading ? 'Mengirim...' : 'Pasang'))),
  ])));
  @override void dispose() { _titleCtrl.dispose(); _descCtrl.dispose(); super.dispose(); }
}
