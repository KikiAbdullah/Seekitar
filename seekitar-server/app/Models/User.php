<?php

namespace App\Models;

use App\Enums\VerificationLevel;
use App\Enums\VerificationStatus;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\SerializesDatesAsUtc;
use App\Support\PlaceholderImg;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasLocation, HasRoles, HasUuids, SerializesDatesAsUtc, SoftDeletes;

    protected $fillable = [
        'phone', 'email', 'password', 'name', 'avatar_url', 'address',
        'ktp_image', 'selfie_image', 'ktp_submitted_at', 'ktp_rejected_reason',
        'verified1_by', 'verified1_at', 'verified2_by', 'verified2_at',
        'nik', 'nik_hash', 'is_blocked', 'blocked_reason', 'blocked_at',
    ];

    /** Data pribadi tidak boleh bocor lewat response API (UU PDP). */
    protected $hidden = [
        'ktp_image', 'selfie_image', 'nik', 'nik_hash',
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'nik'                => 'encrypted',
            'is_blocked'         => 'boolean',
            'rating_avg'         => 'decimal:2',
            'ktp_submitted_at'   => 'datetime',
            'verified1_at'       => 'datetime',
            'verified2_at'       => 'datetime',
            'blocked_at'         => 'datetime',
            'email_verified_at'  => 'datetime',
            // Cast 'hashed' membuat password otomatis di-hash saat diisi,
            // sehingga tidak ada jalur yang bisa menyimpannya plaintext.
            'password'           => 'hashed',
        ];
    }

    /*
     * Avatar selalu punya URL tampilan: tanpa unggahan, jatuh ke
     * placeholder berseed id pengguna (lihat PlaceholderImg). Catatan:
     * dokumen identitas (KTP/selfie) SENGAJA tidak diberi fallback seperti
     * ini — foto acak bukanlah bukti verifikasi, jadi pemeriksaan
     * `$user->ktp_image` di blade dibiarkan menampilkan "Belum diunggah".
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(
            fn (?string $v) => $v ?: PlaceholderImg::url('pengguna-'.$this->getKey(), 240, 240)
        );
    }

    /*
     * Inisial untuk avatar teks: DUA huruf — huruf pertama kata pertama dan
     * kata terakhir ("Sinta Wijaya" -> "SW"), SATU huruf hanya bila nama
     * memang satu kata. Versi lama mengambil huruf pertama saja sehingga
     * semua rekan yang awalan namanya sama menjadi tidak bisa dibedakan.
     * mb_* dipakai agar nama beraksen tidak menghasilkan inisial rusak.
     *
     * Nama BOLEH kosong: warga mendaftar lewat OTP dengan nomor HP saja dan
     * baru mengisi nama saat melengkapi profil. Tanpa cadangan, lingkaran
     * avatar tampil HAMPA — maka jatuh ke huruf pertama email/telepon.
     */
    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            $words = array_values(array_filter(
                preg_split('/\s+/u', trim((string) $this->name)) ?: []
            ));

            if ($words === []) {
                $seed = (string) ($this->email ?: $this->phone ?: 'A');

                return mb_strtoupper(mb_substr($seed, 0, 1));
            }

            $first = mb_substr($words[0], 0, 1);
            $last  = count($words) > 1 ? mb_substr((string) end($words), 0, 1) : '';

            return mb_strtoupper($first.$last);
        });
    }

    /**
     * Bisa masuk panel admin?
     *
     * Pengguna biasa tidak punya kata sandi sama sekali — mereka masuk lewat
     * OTP di aplikasi. Hanya akun dengan kredensial DAN peran admin yang
     * boleh melewati halaman login web.
     */
    public function canAccessAdminPanel(): bool
    {
        // getAttributes(), bukan $this->password: strict mode aktif di luar
        // produksi dan melempar MissingAttributeException bila kolomnya
        // tidak ikut ter-SELECT. Login tidak boleh gagal karena bentuk query.
        $hasPassword = ($this->getAttributes()['password'] ?? null) !== null;

        return $hasPassword
            && ! $this->is_blocked
            && $this->hasAnyRole(['admin', 'super-admin']);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /** Admin yang memverifikasi tahap 1 (nomor HP). */
    public function verified1By(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified1_by');
    }

    /** Admin yang memverifikasi tahap 2 (KTP + NIK). */
    public function verified2By(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified2_by');
    }

    /*
     * Nama penyetuju tahap 1 untuk DITAMPILKAN. Stempel yang ditulis sistem
     * saat OTP cocok sengaja punya verified1_by NULL — merendernya sebagai
     * '—' akan membuat jejak audit tampak cacat, padahal NULL justru bukti
     * TERCATAT bahwa nomornya diverifikasi kode OTP, bukan mata manusia.
     */
    protected function verified1ByLabel(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->verified1_at === null) {
                return null;
            }

            return $this->verified1_by === null
                ? 'Sistem (OTP)'
                : ($this->verified1By?->name ?? '—');
        });
    }

    public function customerRequests(): HasMany
    {
        return $this->hasMany(CustomerRequest::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /*
     * Level verifikasi sebagai TURUNAN MURNI, bukan kolom tersimpan
     * (kolomnya sengaja dihapus — lihat migrasi users):
     *
     *   1 · Nomor Terverifikasi     — setiap pengguna terdaftar (masuk OTP).
     *   2 · Identitas Terverifikasi — verified2_at terisi (KTP disetujui).
     *   3 · Usaha Terverifikasi     — memiliki minimal 1 toko berstatus
     *                                 verified; stempelnya ada di toko,
     *                                 bukan duplikat di users.
     *
     * has_verified_store diisi via withExists oleh query pemanggil; bila
     * belum dimuat, jatuh ke SATU EXISTS kecil — tidak pernah dibiarkan
     * diam-diam salah membaca "belum punya toko". Model yang BELUM tersimpan
     * (factory->make()) diskip total dari query: mustahil punya toko, dan
     * kontrak factory memang diuji tanpa basis data.
     */
    protected function verificationLevel(): Attribute
    {
        return Attribute::get(function (): VerificationLevel {
            $punyaTokoTerverifikasi = array_key_exists('has_verified_store', $this->attributes)
                ? (bool) $this->attributes['has_verified_store']
                : $this->exists
                    && $this->stores()
                        ->where('verification_status', VerificationStatus::Verified)
                        ->exists();

            return match (true) {
                $punyaTokoTerverifikasi      => VerificationLevel::Pro,
                $this->verified2_at !== null => VerificationLevel::Verified,
                default                      => VerificationLevel::Basic,
            };
        });
    }

    /**
     * Filter level TURUNAN — dipakai panel admin (DataTable) maupun API
     * admin. Dua definisi terpisah pasti berbeda pendapat di kasus tepi:
     * pengguna ber-KTP yang tokonya disetujui adalah level 3, bukan 2.
     *
     * @param  int|null  $level  1/2/3; nilai lain berarti tanpa filter.
     */
    public function scopeWhereVerificationLevel(Builder $query, ?int $level): Builder
    {
        $tokoTerverifikasi = fn (Builder $q) => $q
            ->where('verification_status', VerificationStatus::Verified);

        return match ($level) {
            VerificationLevel::Pro->value => $query
                ->whereHas('stores', $tokoTerverifikasi),
            VerificationLevel::Verified->value => $query
                ->whereNotNull('verified2_at')
                ->whereDoesntHave('stores', $tokoTerverifikasi),
            VerificationLevel::Basic->value => $query
                ->whereNull('verified2_at')
                ->whereDoesntHave('stores', $tokoTerverifikasi),
            default => $query,
        };
    }

    /**
     * Boleh membuka toko — aturan 2: pemilik toko harus SUDAH terverifikasi,
     * yaitu nomor HP (bawaan OTP) + KTP disetujui admin. Dibaca langsung
     * dari stempelnya, BUKAN dari level turunan: toko verified warisan dengan
     * stempel KTP kosong tidak boleh membuat pemiliknya tampak layak.
     */
    public function canOpenStore(): bool
    {
        return $this->verified2_at !== null;
    }

    /**
     * Antrian verifikasi admin — pengajuan KTP yang belum selesai dua tahap.
     *
     * SATU-SATUNYA definisi antrian: dipakai halaman antrian (VerificationController)
     * dan lencana sidebar (SidebarComposer). Dua definisi terpisah akan
     * membuat angka lencana tidak cocok dengan isi halaman yang dibukanya.
     */
    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->whereNotNull('ktp_submitted_at')
            ->whereNull('verified2_at');
    }

    /**
     * Tahap verifikasi berikutnya yang belum berstempel: 1, 2, atau null
     * bila tidak ada yang bisa dikerjakan.
     *
     * Hasilnya dipakai antrian untuk MENENTUKAN isi checklist SOP modal
     * (tahap yang masih kosong = yang harus diperiksa). Satu persetujuan
     * admin menyelesaikan SEMUA tahap kosong sekaligus — periksa-dulu
     * dijaga checklist, bukan lagi dengan memecah persetujuan per tahap.
     */
    public function nextVerificationStep(): ?int
    {
        // Kedua tahap hanya ada selama pengajuan KTP masih terbuka.
        if ($this->ktp_submitted_at === null) {
            return null;
        }

        if ($this->verified1_at === null) {
            return 1;   // nomor HP
        }

        return $this->verified2_at === null ? 2 : null;   // KTP & NIK
    }

    /** Label singkat tiap tahap — untuk tombol dan pesan sukses. */
    public static function verificationStepLabel(int $step): string
    {
        return match ($step) {
            1       => 'Nomor HP',
            2       => 'KTP & NIK',
            default => 'Tidak diketahui',
        };
    }

    /**
     * Profil lengkap = syarat bertransaksi (middleware EnsureProfileComplete).
     *
     * `location` bertipe POINT, jadi kelengkapannya diperiksa dari kolom itu
     * sendiri — bukan dari `latitude`, yang sudah tidak ada sejak skema
     * memakai POINT MySQL sepenuhnya.
     */
    public function isProfileComplete(): bool
    {
        return filled($this->name) && $this->location !== null;
    }

    /**
     * Nama yang boleh dilihat penyedia sebelum penawaran diterima
     * (PRD §5.2.3): "Budi Santoso" -> "Budi S."
     */
    public function displayName(): string
    {
        $parts = preg_split('/\s+/', trim($this->name), -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) < 2) {
            return $parts[0] ?? '';
        }
        return $parts[0] . ' ' . mb_strtoupper(mb_substr($parts[1], 0, 1)) . '.';
    }
}
