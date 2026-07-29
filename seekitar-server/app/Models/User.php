<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Enums\VerificationLevel;
use App\Enums\StoreStatus;
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
        'ktp_image', 'selfie_image', 'ktp_submitted_at',
        'status', 'verified_by', 'verified_at',
        'rejected_by', 'rejected_at', 'rejected_reason',
        'blocked_by', 'blocked_at', 'blocked_reason',
        'nik', 'nik_hash',
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
            'status'             => UserStatus::class,
            'rating_avg'         => 'decimal:2',
            'ktp_submitted_at'   => 'datetime',
            'verified_at'        => 'datetime',
            'rejected_at'        => 'datetime',
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
            && ! $this->isBlocked()
            && $this->hasAnyRole(['admin', 'super-admin']);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /** Admin yang menyetujui berkas identitasnya. */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Admin yang menolak berkas identitasnya. */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /** Admin yang memblokir akunnya. */
    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /*
     * "Diblokir" dibaca dari kolom status, bukan lagi boolean sendiri.
     * Dulu ada kolom terpisah (is_blocked + blocked_*): dua sumber yang
     * bisa bertentangan ("is_blocked=false tapi blocked_at terisi").
     * Method — bukan aksesor — supaya tidak menyaru kolom dan selalu
     * kebagian cast enum-nya.
     */
    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Diblokir;
    }

    /**
     * Kedudukan SEBELUM diblokir, untuk memulihkan saat blokir dicabut:
     * stempel verified_at yang masih utuh berarti identitasnya pernah
     * disetujui; blokir tidak pernah mencabut fakta audit itu.
     */
    public function statusSebelumDiblokir(): UserStatus
    {
        return $this->verified_at !== null
            ? UserStatus::Terverifikasi
            : UserStatus::Menunggu;
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
     * Level verifikasi sebagai TURUNAN MURNI untuk kontrak API (kolomnya
     * tidak ada di database — ia dihitung):
     *
     *   1 · Nomor Terverifikasi     — setiap akun (nomornya dibuktikan OTP).
     *   2 · Identitas Terverifikasi — verified_at terisi (berkas disetujui).
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
                        ->where('status', StoreStatus::Verified)
                        ->exists();

            return match (true) {
                $punyaTokoTerverifikasi     => VerificationLevel::Pro,
                $this->verified_at !== null => VerificationLevel::Verified,
                default                     => VerificationLevel::Basic,
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
            ->where('status', StoreStatus::Verified);

        return match ($level) {
            VerificationLevel::Pro->value => $query
                ->whereHas('stores', $tokoTerverifikasi),
            VerificationLevel::Verified->value => $query
                ->whereNotNull('verified_at')
                ->whereDoesntHave('stores', $tokoTerverifikasi),
            VerificationLevel::Basic->value => $query
                ->whereNull('verified_at')
                ->whereDoesntHave('stores', $tokoTerverifikasi),
            default => $query,
        };
    }

    /**
     * Boleh membuka toko — aturan 2: pemilik toko harus SUDAH terverifikasi:
     * berkas identitasnya disetujui admin (verified_at terisi). Dibaca
     * langsung dari stempelnya, BUKAN dari status: akun terverifikasi yang
     * kemudian diblokir tetap pernah lolos identitas, tetapi tokonya pun
     * ikut dinonaktifkan selama blokirnya berlangsung.
     */
    public function canOpenStore(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Antrian verifikasi admin — pengajuan identitas yang menunggu tinjauan.
     *
     * status=menunggu SAJA tidak cukup: itu juga kedudukan setiap akun baru
     * yang belum pernah mengirim berkas. Yang membuatnya ANTRE adalah
     * ktp_submitted_at — berkas benar-benar sudah dikirim.
     *
     * SATU-SATUNYA definisi antrian: dipakai halaman antrian (VerificationController)
     * dan lencana sidebar (SidebarComposer). Dua definisi terpisah akan
     * membuat angka lencana tidak cocok dengan isi halaman yang dibukanya.
     */
    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Menunggu)
            ->whereNotNull('ktp_submitted_at');
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
