import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import '../models/store.dart';
import '../services/api_compat.dart';

class CreateListingScreen extends StatefulWidget {
  final Store store;
  const CreateListingScreen({super.key, required this.store});
  @override State<CreateListingScreen> createState() => _CreateListingScreenState();
}

class _CreateListingScreenState extends State<CreateListingScreen> {
  final _api = ApiProvider();
  final _title = TextEditingController(), _desc = TextEditingController(), _price = TextEditingController();
  String _type = 'product';
  String? _stock, _slot;
  List<File> _images = [];
  bool _loading = false;
  bool _uploading = false;

  Future<void> _addImage() async {
    if (_images.length >= 5) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Maksimal 5 foto')));
      return;
    }
    final picker = ImagePicker();
    final x = await picker.pickImage(source: ImageSource.gallery, maxWidth: 1200);
    if (x != null) {
      final c = await FlutterImageCompress.compressAndGetFile(x.path, '${x.path}_comp.jpg', quality: 70);
      if (c != null) setState(() => _images.add(File(c.path)));
    }
  }

  Future<void> _submit() async {
    if (_title.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Judul wajib diisi')));
      return;
    }
    setState(() => _loading = true);
    try {
      final uploaded = <String>[];
      if (_images.isNotEmpty) {
        setState(() => _uploading = true);
        for (final f in _images) {
          final r = await _api.uploadImage(f, purpose: 'listing');
          final url = r['url']?.toString() ?? r['path']?.toString() ?? '';
          if (url.isNotEmpty) uploaded.add(url);
        }
        setState(() => _uploading = false);
      }
      await _api.createListing({
        'title': _title.text,
        'description': _desc.text,
        'listing_type': _type,
        'price': _price.text.isNotEmpty ? double.parse(_price.text) : null,
        'store_id': widget.store.id,
        'images': uploaded,
        if (_stock != null && _stock!.isNotEmpty) 'stock_qty': int.parse(_stock!),
        if (_slot != null && _slot!.isNotEmpty) 'slot': int.parse(_slot!),
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Listing berhasil dipasang!')));
        Navigator.pop(context, true);
      }
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
    if (mounted) setState(() { _loading = false; _uploading = false; });
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Pasang Listing')),
    body: ListView(padding: const EdgeInsets.all(20), children: [
      Text('Tipe', style: Theme.of(ctx).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
      const SizedBox(height: 10),
      Row(children: [
        for (final t in [['product', 'Barang'], ['service', 'Jasa'], ['rental', 'Sewa']])
          ...[Expanded(child: GestureDetector(onTap: () => setState(() => _type = t[0]), child: Container(padding: const EdgeInsets.symmetric(vertical: 12), decoration: BoxDecoration(color: _type == t[0] ? Theme.of(ctx).colorScheme.primary : Colors.grey.shade100, borderRadius: BorderRadius.circular(14)), child: Text(t[1], textAlign: TextAlign.center, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: _type == t[0] ? Colors.white : Colors.grey.shade600))))), if (t[0] != 'rental') const SizedBox(width: 10)],
      ]),
      const SizedBox(height: 20),
      TextField(controller: _title, decoration: const InputDecoration(labelText: 'Judul Listing', hintText: 'Nama barang atau jasa')),
      const SizedBox(height: 16),
      TextField(controller: _desc, maxLines: 4, decoration: const InputDecoration(labelText: 'Deskripsi')),
      const SizedBox(height: 16),
      TextField(controller: _price, keyboardType: TextInputType.number, decoration: InputDecoration(labelText: _type == 'service' ? 'Harga (kosongkan jika bisa nego)' : 'Harga (Rp)')),
      const SizedBox(height: 16),
      if (_type != 'service') TextField(decoration: const InputDecoration(labelText: 'Stok (opsional)'), keyboardType: TextInputType.number, onChanged: (v) => _stock = v),
      if (_type == 'service') TextField(decoration: const InputDecoration(labelText: 'Slot (opsional)'), keyboardType: TextInputType.number, onChanged: (v) => _slot = v),
      const SizedBox(height: 20),
      Text('Foto (${_images.length}/5)', style: Theme.of(ctx).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
      const SizedBox(height: 10),
      Wrap(spacing: 10, runSpacing: 10, children: [
        ..._images.map((f) => ClipRRect(borderRadius: BorderRadius.circular(14), child: Stack(children: [
          Image.file(f, width: 90, height: 90, fit: BoxFit.cover),
          Positioned(top: 4, right: 4, child: GestureDetector(onTap: () => setState(() => _images.remove(f)), child: Container(width: 22, height: 22, decoration: const BoxDecoration(color: Colors.black54, shape: BoxShape.circle), child: const Icon(Icons.close, size: 14, color: Colors.white)))),
        ]))),
        if (_images.length < 5) GestureDetector(onTap: _addImage, child: Container(width: 90, height: 90, decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(14), border: Border.all(color: Colors.grey.shade300, width: 1.5)), child: const Icon(Icons.add_photo_alternate, color: Colors.grey, size: 32))),
      ]),
      const SizedBox(height: 32),
      SizedBox(height: 56, child: ElevatedButton(
        onPressed: _loading ? null : _submit,
        child: _uploading ? const Row(mainAxisAlignment: MainAxisAlignment.center, children: [SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)), SizedBox(width: 10), Text('Upload foto...')])
        : _loading ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
        : const Text('Pasang Listing', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
      )),
    ]),
  );
  @override void dispose() { _title.dispose(); _desc.dispose(); _price.dispose(); super.dispose(); }
}
