# Seekitar Mobile

Aplikasi Flutter **Seekitar** (Android & iOS) — klien dari
[`seekitar-server`](../seekitar-server/README.md) (`/api/v1`).

## Menjalankan

Kebutuhan: Flutter 3.44+ (Dart 3.12+).

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

> ⚠️ Model JSON ditulis manual (`fromJson`), tanpa codegen — **tidak ada**
> `build_runner`. Base URL dibaca dari `--dart-define=API_BASE_URL` dengan
> default di `lib/core/constants.dart`; emulator Android memetakan host ke
> `10.0.2.2`, bukan `localhost`.

## Kontrak yang perlu diingat

- Masuk tanpa kata sandi: `POST /auth/request-otp` → `POST /auth/verify-otp`
  → simpan Bearer JWT di secure storage (`flutter_secure_storage`, kunci
  `jwt_token`). Token stateless — tidak ada baris token di database.
- Token kedaluwarsa 30 hari; interceptor Dio otomatis memanggil
  `POST /auth/refresh` saat `401`, lalu mengulang request (`dio_client.dart`).
- `verification_level` pada JSON pengguna adalah **turunan baca-saja**
  (1 = masuk OTP, 2 = KTP disetujui, 3 = punya toko terverifikasi) — tidak
  ada endpoint untuk mengubahnya.
- Ganti nomor HP lewat dua langkah OTP (`/auth/phone/request-otp` →
  `verify-otp`); unggah ulang berkas KTP membuka peninjauan admin ulang.

Panduan lengkap arsitektur (provider/ChangeNotifier, GoRouter, FCM, dsb.) ada
di [`Mobile_Implementation_Guide.md`](../Mobile_Implementation_Guide.md);
kontrak endpoint di [`API_DOCUMENTATION.md`](../API_DOCUMENTATION.md).
