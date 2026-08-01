import 'package:flutter/material.dart';

class AppConstants {
  static const String appName = 'Seekitar';
  static const String baseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2:8000/api/v1');
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 15);
  static const int maxRetries = 2;

  static const Color primaryColor = Color(0xFF168A4A);
  static const Color primarySubtle = Color(0xFFE7F6EC);
  static const Color heroGradientStart = Color(0xFFE9FAF1);
  static const Color heroBlob = Color(0xFFC9F2DD);

  static String formatRupiah(double value) {
    if (value == value.roundToDouble()) {
      return 'Rp ${value.toStringAsFixed(0).replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
    }
    return 'Rp ${value.toStringAsFixed(0).replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
  }

  static String typeLabel(String type) => {'product': 'Barang', 'service': 'Jasa', 'rental': 'Sewa'}[type] ?? type;

  static String orderStatusLabel(String s) {
    return {'menunggu_konfirmasi': 'Menunggu', 'diproses': 'Diproses', 'dikirim': 'Dikirim', 'selesai': 'Selesai', 'dibatalkan': 'Dibatalkan', 'dispute': 'Sengketa'}[s] ?? s;
  }

  static Color orderStatusColor(String s) {
    return {'menunggu_konfirmasi': Colors.orange, 'diproses': Colors.blue, 'selesai': Colors.green, 'dibatalkan': Colors.red, 'dispute': Colors.red.shade700}[s] ?? Colors.grey;
  }
}

class UiStrings {
  static const retry = 'Coba Lagi';
  static const reload = 'Muat Ulang';
  static const seeAll = 'Lihat Semua';
  static const loadFailed = 'Gagal memuat';
  static const pullToRefresh = 'Tarik ke bawah untuk memuat ulang';
  static const noListings = 'Belum ada listing di sekitarmu';
  static const noResults = 'Tidak ada hasil';
  static const noFavorites = 'Belum ada favorit';
  static const noNotifications = 'Tidak ada notifikasi';
  static const noOrders = 'Belum ada pesanan';
  static const noConversations = 'Belum ada percakapan';
  static const noAddresses = 'Belum ada alamat';
  static const noStore = 'Belum punya toko';
  static const noReviews = 'Belum ada ulasan';
  static const noBlocked = 'Tidak ada pengguna diblokir';
  static const noRequests = 'Belum ada permintaan';
  static const noOffers = 'Belum ada penawaran';
  static const noMessages = 'Belum ada pesan';
  static const noTransactions = 'Belum ada transaksi';
  static const contactSeller = 'Hubungi Penjual';
  static const listingNotFound = 'Listing tidak ditemukan';
  static const txInApp = 'Transaksi di Aplikasi';
  static const txInAppDesc = 'Untuk membeli atau chat penjual, gunakan aplikasi Seekitar di ponselmu. Gratis, tanpa komisi.';
}
