<?php

namespace App\Http\Requests\Api;

use App\Enums\StoreType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Pembuatan toko (API §3.1). Syarat verification_level >= 2 ditegakkan
 * Policy, bukan di sini — validasi hanya mengurus bentuk data.
 */
class StoreStoreRequest extends FormRequest
{
    /** Hari dalam operating_hours; nama Indonesia sesuai kontrak API §3.1. */
    private const DAYS = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'min:3', 'max:100'],

            // SELALU array, meski satu nilai. Model yang mengubahnya jadi
            // SET comma-separated; klien tidak pernah melihat bentuk itu.
            'store_type'   => ['required', 'array', 'min:1'],
            'store_type.*' => ['required', Rule::enum(StoreType::class)],

            'category_ids'   => ['required', 'array', 'min:1', 'max:10'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address'   => ['sometimes', 'nullable', 'string', 'max:255'],

            // Radius toko, BUKAN radius permintaan. Default 5 km
            // (DATABASE.md §4.2) — jangan tertukar dengan 15 km milik request.
            'service_radius_km' => ['sometimes', 'numeric', 'min:0.1', 'max:50'],

            'operating_hours' => ['sometimes', 'nullable', 'array'],
            'bank_account'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'npwp'            => ['sometimes', 'nullable', 'string', 'max:20'],

            'accepts_cod'     => ['sometimes', 'boolean'],
            'offers_delivery' => ['sometimes', 'boolean'],
            'allows_pickup'   => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $this->validateOperatingHours($v);
            $this->validateFulfilment($v);
        });
    }

    /**
     * `operating_hours` berupa objek per hari, atau `null` untuk hari libur.
     * Divalidasi manual karena strukturnya bersarang dan kuncinya tetap.
     */
    private function validateOperatingHours(Validator $v): void
    {
        $hours = $this->input('operating_hours');

        if (! is_array($hours)) {
            return;
        }

        foreach ($hours as $day => $range) {
            if (! in_array($day, self::DAYS, true)) {
                $v->errors()->add("operating_hours.$day", "Hari '$day' tidak dikenal.");
                continue;
            }

            if ($range === null) {
                continue;   // hari libur — sah
            }

            foreach (['open', 'close'] as $bound) {
                $value = $range[$bound] ?? null;
                // Jam dinding lokal "HH:MM", tanpa tanggal (API §12.1).
                if (! is_string($value) || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
                    $v->errors()->add("operating_hours.$day.$bound", 'Format jam harus HH:MM.');
                }
            }

            if (isset($range['open'], $range['close']) && $range['close'] <= $range['open']) {
                $v->errors()->add("operating_hours.$day.close", 'Jam tutup harus setelah jam buka.');
            }
        }
    }

    /**
     * Toko wajib bisa dijangkau lewat minimal satu cara.
     * Sama dengan CHECK `stores_fulfilment_chk` di database — divalidasi di
     * sini juga supaya pengguna mendapat 422 yang jelas, bukan 500 dari engine.
     */
    private function validateFulfilment(Validator $v): void
    {
        $delivery = $this->boolean('offers_delivery');
        $pickup   = $this->has('allows_pickup') ? $this->boolean('allows_pickup') : true;

        if (! $delivery && ! $pickup) {
            $v->errors()->add('allows_pickup', 'Toko harus melayani antar atau ambil di tempat.');
        }
    }
}
