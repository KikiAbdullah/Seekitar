<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Pembaca tabel `settings` dengan cache.
 *
 * Pengaturan dibaca hampir di setiap request (radius, masa berlaku, SLA),
 * jadi seluruh tabel di-cache sekaligus — bukan per kunci — supaya satu
 * request tidak menghasilkan delapan query.
 *
 * Lihat `Server_Implementation_Guide.md` §9.12 untuk daftar kunci baku.
 */
class SettingService
{
    public const CACHE_KEY = 'settings:all';

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** Pembungkus bertipe, supaya pemanggil tidak perlu cast sendiri. */
    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::all()
                ->mapWithKeys(static fn (Setting $s): array => [$s->key => $s->typedValue()])
                ->all();
        });
    }

    /**
     * Perbarui beberapa pengaturan sekaligus.
     *
     * @param  array<string, mixed>  $values
     */
    public function set(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                // Nilai boolean disimpan sebagai '1'/'0' agar typedValue()
                // bisa membacanya kembali; casting PHP mengubah false jadi ''
                // yang lolos filter_var sebagai... false, tapi juga NULL.
                $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

                Setting::where('key', $key)->update(['value' => $stored]);
            }
        });

        $this->flush();
    }

    /**
     * WAJIB dipanggil setiap kali tabel `settings` berubah dari jalur mana
     * pun — termasuk seeder dan tinker. Tanpa ini nilai lama bertahan sampai
     * cache kedaluwarsa sendiri, dan `rememberForever` tidak pernah begitu.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
