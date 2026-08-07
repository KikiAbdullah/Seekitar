import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/app_state.dart';
import '../../services/dio_client.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  final _phoneCtrl = TextEditingController(), _otpCtrl = TextEditingController(), _nameCtrl = TextEditingController();
  bool _otpSent = false, _loading = false;
  bool _isRegister = false;
  String? _error;
  String? _debugOtp;
  int _countdown = 0;
  /// Nomor HP yang SUDAH dinormalisasi (62xxx) — dipakai saat verifikasi OTP
  /// agar selalu sama dengan nomor yang menerima OTP, meski field diedit.
  String? _normalizedPhone;
  late final AnimationController _anim;
  late final Animation<double> _slide;

  @override void initState() {
    super.initState();
    _anim = AnimationController(vsync: this, duration: const Duration(milliseconds: 400));
    _slide = Tween<double>(begin: 0, end: 1).animate(CurvedAnimation(parent: _anim, curve: Curves.easeOutCubic));
    _anim.forward();
  }

  @override void dispose() { _phoneCtrl.dispose(); _otpCtrl.dispose(); _nameCtrl.dispose(); _anim.dispose(); super.dispose(); }

  void _setMode(bool register) {
    if (_isRegister == register) return;
    setState(() {
      _isRegister = register;
      _otpSent = false;
      _otpCtrl.clear();
      _error = null;
      _debugOtp = null;
      _countdown = 0;
      _normalizedPhone = null;
    });
  }

  /// Normalisasi nomor HP Indonesia ke format E.164 (`62xxx`) — aturannya
  /// SAMA dengan `App\Support\PhoneNumber` di server. Jadi user bebas
  /// mengetik `0812…`, `812…`, `62812…`, atau `+62812…`; yang dikirim &
  /// tersimpan di DB selalu `62812…`.
  String normalizePhone(String input) {
    final digits = input.replaceAll(RegExp(r'\D'), '');
    if (digits.isEmpty) return '';
    if (digits.startsWith('0')) return '62${digits.substring(1)}';
    if (digits.startsWith('62')) return digits;
    return '62$digits';
  }

  bool _isValidPhone(String phone) => RegExp(r'^62[0-9]{8,13}$').hasMatch(phone);

  void _startCountdown() { _countdown = 60; Future.doWhile(() async { await Future.delayed(const Duration(seconds: 1)); if (!mounted) return false; setState(() => _countdown--); return _countdown > 0; }); }

  Future<void> _sendOtp() async {
    final p = normalizePhone(_phoneCtrl.text);
    if (!_isValidPhone(p)) { setState(() => _error = 'Nomor WhatsApp tidak valid. Contoh: 081234567890'); return; }
    if (_isRegister && _nameCtrl.text.trim().isEmpty) { setState(() => _error = 'Masukkan nama lengkapmu dulu'); return; }
    setState(() { _loading = true; _error = null; _debugOtp = null; });
    try {
      final res = await context.read<AppState>().requestOtp(p);
      if (mounted) {
        final registered = res['is_registered'];
        // Server baru mengirim `is_registered`; kalau null (server lama),
        // lanjutkan alur lama tanpa pengecekan.
        if (registered != null) {
          final isRegistered = registered == true;
          if (_isRegister && isRegistered) {
            setState(() { _error = 'Nomor WhatsApp sudah terdaftar. Silakan masuk dengan nomor ini.'; _loading = false; });
            _showSuggestion('Nomor sudah terdaftar. Masuk dengan nomor ini?', 'Masuk', () => _setMode(false));
            return;
          }
          if (!_isRegister && !isRegistered) {
            setState(() { _error = 'Nomor WhatsApp belum terdaftar. Daftar dulu untuk membuat akun.'; _loading = false; });
            _showSuggestion('Nomor belum terdaftar. Buat akun baru?', 'Daftar', () => _setMode(true));
            return;
          }
        }
        final debugOtp = res['debug_otp']?.toString();
        setState(() {
          _otpSent = true;
          _normalizedPhone = p;   // kunci verifikasi = nomor yang menerima OTP
          _debugOtp = debugOtp;
        });
        _startCountdown();
      }
    }
    catch (e) { if (mounted) setState(() => _error = _friendlyError(e)); }
    if (mounted) setState(() => _loading = false);
  }

  /// Notifikasi sementara dengan tombol aksi (Masuk/Daftar) — dipakai saat
  /// nomor tidak cocok dengan mode yang dipilih user.
  void _showSuggestion(String message, String actionLabel, VoidCallback onAction) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(message),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 5),
        action: SnackBarAction(label: actionLabel, onPressed: onAction),
      ));
  }

  /// Ubah error apa pun jadi pesan ramah. Untuk 429 (rate limit OTP) tampilkan
  /// hitung mundur dari header `Retry-After`; selain itu ambil `message` dari
  /// amplop respons server (DioClient.getMessage).
  String _friendlyError(Object e) {
    if (e is DioException) {
      final status = e.response?.statusCode;
      if (status == 429) {
        final retry = e.response?.headers.value('retry-after');
        if (retry != null && int.tryParse(retry) != null) {
          return 'Terlalu banyak percobaan. Coba lagi dalam $retry detik.';
        }
        return 'Terlalu banyak percobaan. Tunggu beberapa saat lalu coba lagi.';
      }
      return DioClient().getMessage(e);
    }
    return e.toString().replaceAll('Exception: ', '');
  }

  Future<void> _verifyOtp() async {
    final o = _otpCtrl.text.trim();
    if (o.length < 4) { setState(() => _error = 'Masukkan kode OTP'); return; }
    setState(() { _loading = true; _error = null; });
    try {
      // Pakai nomor ternormalisasi yang sama dengan saat OTP dikirim,
      // bukan membaca ulang field (yang bisa saja sudah diedit user).
      await context.read<AppState>().verifyOtp(_normalizedPhone ?? normalizePhone(_phoneCtrl.text), o);
      if (_isRegister && _nameCtrl.text.trim().isNotEmpty) {
        await context.read<AppState>().updateProfile(name: _nameCtrl.text.trim());
      }
      if (mounted) context.go('/home');
    }
    catch (e) { if (mounted) setState(() => _error = _friendlyError(e)); }
    if (mounted) setState(() => _loading = false);
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFFE9FAF1), Color(0xFFF8FAF9)])),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 28),
              child: FadeTransition(
                opacity: _slide,
                child: SlideTransition(
                  position: Tween<Offset>(begin: const Offset(0, 0.08), end: Offset.zero).animate(_anim),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 420),
                    child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                      const SizedBox(height: 48),
                      Center(child: Container(
                        width: 100, height: 100,
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(32), boxShadow: [BoxShadow(color: t.colorScheme.primary.withOpacity(0.12), blurRadius: 30, offset: const Offset(0, 12))]),
                        child: Image.asset('assets/images/logo.png', width: 56, height: 56),
                      )),
                      const SizedBox(height: 28),
                      Text(_isRegister ? 'Buat akun\nSeekitar' : 'Selamat datang\ndi Seekitar', textAlign: TextAlign.center, style: t.textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900, height: 1.15, letterSpacing: -0.5)),
                      const SizedBox(height: 8),
                      Text(_isRegister ? 'Daftar dengan nomor WhatsApp.\nKode OTP akan dikirim lewat WhatsApp.' : 'Masuk dengan nomor WhatsApp.\nKode OTP akan dikirim lewat WhatsApp.', textAlign: TextAlign.center, style: TextStyle(fontSize: 14, color: Colors.grey.shade500, height: 1.4)),
                      const SizedBox(height: 32),

                      // Mode: Masuk / Daftar
                      Container(
                        padding: const EdgeInsets.all(5),
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(22), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 12)]),
                        child: Row(children: [
                          _modeBtn(false, 'Masuk'),
                          _modeBtn(true, 'Daftar'),
                        ]),
                      ),
                      const SizedBox(height: 20),

                      // Name field (only when registering)
                      if (_isRegister) ...[
                        Container(
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 12)]),
                          child: TextField(
                            controller: _nameCtrl, textCapitalization: TextCapitalization.words,
                            enabled: !_otpSent, onSubmitted: (_) => _sendOtp(),
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
                            decoration: InputDecoration(
                              labelText: 'Nama Lengkap',
                              hintText: 'Cth: Budi Santoso',
                              hintStyle: TextStyle(color: Colors.grey.shade500, fontSize: 18),
                              prefixIcon: const Icon(Icons.badge_outlined, color: Color(0xFF168A4A), size: 22),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // Phone field — tanpa prefix "+62" supaya user bebas
                      // mengetik 0812… (0 di awal). Dinormalisasi ke 628…
                      // otomatis saat dikirim.
                      Container(
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 12)]),
                        child: TextField(
                          controller: _phoneCtrl, keyboardType: TextInputType.phone,
                          enabled: !_otpSent, onSubmitted: (_) => _sendOtp(),
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600, letterSpacing: 0.5),
                          decoration: InputDecoration(
                            labelText: 'Nomor WhatsApp',
                            hintText: '0812-3456-789',
                            hintStyle: TextStyle(color: Colors.grey.shade500, fontSize: 18),
                            prefixIcon: const Icon(Icons.phone_outlined, color: Color(0xFF168A4A), size: 22),
                            prefixIconConstraints: const BoxConstraints(minWidth: 48),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
                          ),
                        ),
                      ),

                      if (_otpSent) ...[
                        const SizedBox(height: 16),
                        Container(
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 12)]),
                          child: TextField(
                            controller: _otpCtrl, keyboardType: TextInputType.number, maxLength: 6,
                            onSubmitted: (_) => _verifyOtp(),
                            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w800, letterSpacing: 8),
                            textAlign: TextAlign.center,
                            decoration: InputDecoration(
                              labelText: 'Kode OTP',
                              counterText: '',
                              labelStyle: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
                            ),
                          ),
                        ),
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(color: const Color(0xFFE9FAF1), borderRadius: BorderRadius.circular(16)),
                          child: const Row(children: [
                            Icon(Icons.mark_chat_read_outlined, size: 20, color: Color(0xFF168A4A)),
                            SizedBox(width: 10),
                            Expanded(child: Text('Kode OTP telah dikirim ke WhatsApp Anda. Silakan cek pesan WhatsApp.', style: TextStyle(color: Color(0xFF168A4A), fontSize: 13, fontWeight: FontWeight.w600))),
                          ]),
                        ),
                        const SizedBox(height: 8),
                        Align(alignment: Alignment.centerRight, child: TextButton(
                          onPressed: _countdown > 0 ? null : () { setState(() => _otpSent = false); _otpCtrl.clear(); _sendOtp(); },
                          child: Text(_countdown > 0 ? 'Kirim ulang dalam ${_countdown}s' : 'Kirim ulang OTP', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _countdown > 0 ? Colors.grey : t.colorScheme.primary)),
                        )),
                      ],

                      if (_error != null) ...[
                        const SizedBox(height: 12),
                        Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(16)), child: Row(children: [
                          Icon(Icons.error_outline, size: 18, color: Colors.red.shade500),
                          const SizedBox(width: 10),
                          Expanded(child: Text(_error!, style: TextStyle(color: Colors.red.shade600, fontSize: 13))),
                        ])),
                      ],

                      const SizedBox(height: 24),
                      SizedBox(
                        height: 56,
                        child: ElevatedButton(
                          onPressed: _loading ? null : (_otpSent ? _verifyOtp : _sendOtp),
                          style: ElevatedButton.styleFrom(backgroundColor: t.colorScheme.primary, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), elevation: 0),
                          child: _loading ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : Text(_otpSent ? (_isRegister ? 'Verifikasi & Daftar' : 'Verifikasi & Masuk') : 'Kirim Kode OTP', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                        ),
                      ),
                      if (_otpSent) ...[
                        const SizedBox(height: 12),
                        TextButton(onPressed: () { setState(() { _otpSent = false; _otpCtrl.clear(); _error = null; }); _anim.forward(); }, child: const Text('← Ganti nomor WhatsApp')),
                      ],
                      const SizedBox(height: 28),
                      TextButton.icon(
                        onPressed: _loading || _otpSent ? null : () => _setMode(!_isRegister),
                        icon: Icon(_isRegister ? Icons.login : Icons.person_add_alt_1_outlined, size: 18),
                        label: Text(_isRegister ? 'Sudah punya akun? Masuk' : 'Belum punya akun? Daftar'),
                        style: TextButton.styleFrom(foregroundColor: t.colorScheme.primary),
                      ),
                      const SizedBox(height: 16),
                      Text(_isRegister ? 'Dengan mendaftar, kamu menyetujui Syarat & Ketentuan dan Kebijakan Privasi.' : 'Dengan masuk, kamu menyetujui Syarat & Ketentuan dan Kebijakan Privasi.', textAlign: TextAlign.center, style: TextStyle(fontSize: 11, color: Colors.grey.shade500, height: 1.4)),
                    ]),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _modeBtn(bool register, String label) {
    final selected = _isRegister == register;
    final t = Theme.of(context);
    return Expanded(
      child: GestureDetector(
        onTap: _loading || _otpSent ? null : () => _setMode(register),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: selected ? t.colorScheme.primary : Colors.transparent,
            borderRadius: BorderRadius.circular(18),
          ),
          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            Icon(register ? Icons.person_add_alt_1_outlined : Icons.login, size: 18, color: selected ? Colors.white : Colors.grey.shade500),
            const SizedBox(width: 6),
            Text(label, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15, color: selected ? Colors.white : Colors.grey.shade600)),
          ]),
        ),
      ),
    );
  }
}
