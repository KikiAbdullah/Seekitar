# Seekitar Mobile

Klien Flutter Seekitar untuk **Android**, terhubung ke REST API server pada
prefix `/api/v1`.

## Menjalankan

Kebutuhan: Flutter 3.44+ dengan Dart 3.12+.

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

Model JSON ditulis manual melalui `fromJson`; proyek tidak menggunakan
`build_runner`. Untuk Android emulator, `10.0.2.2` menunjuk host lokal.

## Kontrak utama

- Autentikasi: `POST /auth/request-otp`, lalu `POST /auth/verify-otp`.
- JWT disimpan pada `flutter_secure_storage` dengan kunci `jwt_token`; Dio
  mencoba refresh melalui `POST /auth/refresh` pada respons 401.
- `verification_level` dari API adalah nilai turunan baca-saja.
- Ganti nomor dan verifikasi KTP memakai alur OTP/unggahan yang disediakan API.

Lihat [FLOWS.md](FLOWS.md) untuk alur layar,
[Mobile_Implementation_Guide.md](../Mobile_Implementation_Guide.md) untuk
arsitektur, dan [API_DOCUMENTATION.md](../API_DOCUMENTATION.md) untuk kontrak.

> Target iOS belum tersedia: source tree ini tidak memiliki direktori `ios/`.
> Lihat [IMPLEMENTATION_GAPS.md](../IMPLEMENTATION_GAPS.md) sebelum menjanjikan
> distribusi iOS.
