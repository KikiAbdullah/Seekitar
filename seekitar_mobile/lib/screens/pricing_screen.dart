import 'package:flutter/material.dart';

class PricingScreen extends StatelessWidget {
  const PricingScreen({super.key});
  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Biaya & Harga')),
    body: ListView(padding: const EdgeInsets.all(16), children: [
      Container(padding: const EdgeInsets.all(20), decoration: BoxDecoration(gradient: LinearGradient(colors: [Theme.of(ctx).colorScheme.primary, Colors.green.shade700]), borderRadius: BorderRadius.circular(24)), child: const Column(children: [
        Icon(Icons.wallet, color: Colors.white, size: 48), SizedBox(height: 12),
        Text('100% Gratis', style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w900)),
        Text('Tidak ada biaya tersembunyi', style: TextStyle(color: Colors.white70, fontSize: 14)),
      ])),
      const SizedBox(height: 20),
      Text('Untuk Pembeli', style: Theme.of(ctx).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
      const SizedBox(height: 8),
      ..._pricingItems('pembeli'),
      const SizedBox(height: 20),
      Text('Untuk Penjual', style: Theme.of(ctx).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
      const SizedBox(height: 8),
      ..._pricingItems('penjual'),
      const SizedBox(height: 16),
      Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: Colors.orange.shade50, borderRadius: BorderRadius.circular(16)), child: Row(children: [Icon(Icons.info_outline, color: Colors.orange.shade700), const SizedBox(width: 10), const Expanded(child: Text('Satu-satunya biaya adalah biaya transfer bank — itu milik bank, bukan Seekitar.', style: TextStyle(fontSize: 13, color: Colors.orange)))]),),
      const SizedBox(height: 40),
    ]),
  );

  List<Widget> _pricingItems(String role) => [
    ['Membuat akun', 'Gratis'],
    ['Verifikasi identitas (KTP)', role == 'penjual' ? 'Gratis' : '—'],
    ['Mencari / menjelajah listing', 'Gratis'],
    ['Memasang listing', role == 'penjual' ? 'Gratis' : '—'],
    ['Memasang permintaan', role == 'pembeli' ? 'Gratis' : '—'],
    ['Komisi per transaksi', 'Gratis'],
    ['Chat dengan pengguna lain', 'Gratis'],
    ['Mediasi sengketa', 'Gratis'],
  ].where((i) => i[1] != '—').map((i) => Card(child: ListTile(title: Text(i[0], style: const TextStyle(fontSize: 14)), trailing: Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4), decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(10)), child: const Text('Gratis', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.green)))))).toList();
}
