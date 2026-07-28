<?php

namespace App\Models;

use App\Enums\VerificationLevel;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasLocation, HasRoles, HasUuids, SerializesDatesAsUtc, SoftDeletes;

    protected $fillable = [
        'phone', 'email', 'password', 'name', 'avatar_url', 'address', 'verification_level',
        'ktp_image', 'selfie_image', 'ktp_submitted_at', 'ktp_rejected_reason',
        'nik', 'nik_hash', 'is_blocked', 'blocked_reason', 'blocked_at',
    ];

    /** Data pribadi tidak boleh bocor lewat response API (UU PDP). */
    protected $hidden = [
        'ktp_image', 'selfie_image', 'nik', 'nik_hash', 'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'verification_level' => VerificationLevel::class,
            'nik'                => 'encrypted',
            'is_blocked'         => 'boolean',
            'ktp_submitted_at'   => 'datetime',
            'blocked_at'         => 'datetime',
            'email_verified_at'  => 'datetime',
            // Cast 'hashed' membuat password otomatis di-hash saat diisi,
            // sehingga tidak ada jalur yang bisa menyimpannya plaintext.
            'password'           => 'hashed',
        ];
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
        return $this->password !== null
            && ! $this->is_blocked
            && $this->hasAnyRole(['admin', 'super-admin']);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
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

    public function canOpenStore(): bool
    {
        return $this->verification_level->canOpenStore();
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
