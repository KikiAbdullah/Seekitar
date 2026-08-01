import 'package:flutter/material.dart';

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});
  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Tentang')),
    body: ListView(padding: const EdgeInsets.all(20), children: [
      Center(child: Image.asset('assets/images/logo.png', width: 80, height: 80)),
      const SizedBox(height: 20),
      const Text('Seekitar', textAlign: TextAlign.center, style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900)),
      const SizedBox(height: 6),
      const Text('Pasar Lokal Satu Kabupaten', textAlign: TextAlign.center, style: TextStyle(fontSize: 14, color: Colors.grey)),
      const SizedBox(height: 40),
      Card(child: Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('Tentang', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)).paddingOnly(bottom: 8),
        Text('Seekitar adalah pasar lokal dua arah dalam satu kabupaten. Beli dan jual barang, jasa, serta sewa di sekitar Anda — gratis, tanpa komisi.', style: TextStyle(color: Colors.grey.shade700, height: 1.6)),
      ]))),
      const SizedBox(height: 12),
      Card(child: Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('Kontak', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)).paddingOnly(bottom: 8),
        _row('Email', 'pengaduan@seekitar.id'),
        _row('WhatsApp', '+62 812-3456-7890'),
        _row('Alamat', 'Bangil, Kabupaten Pasuruan, Jawa Timur'),
      ]))),
    ]),
  );
  Widget _row(String l, String v) => Padding(padding: const EdgeInsets.symmetric(vertical: 4), child: Row(children: [SizedBox(width: 90, child: Text(l, style: TextStyle(color: Colors.grey.shade500))), Expanded(child: Text(v, style: const TextStyle(fontWeight: FontWeight.w600)))]));
}
