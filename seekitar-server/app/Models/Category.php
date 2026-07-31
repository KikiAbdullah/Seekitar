<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, SerializesDatesAsUtc;

    protected $fillable = ['name', 'slug', 'parent_id', 'icon', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function customerRequests(): HasMany
    {
        return $this->hasMany(CustomerRequest::class);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Ikon Tabler untuk tampilan web.
     *
     * Kolom `icon` menyimpan nama ikon sistem (Heroicons v2 outline, lihat
     * `BRANDING-GUIDELINE.md` §3.7.1) dan dipakai apa adanya oleh API/mobile.
     * Situs web memakai Tabler Icons (`ti ti-*`), jadi nama itu dipetakan
     * ke padanan Tabler di sini — data tetap utuh untuk konsumen lain.
     */
    public function tablerIcon(): string
    {
        $map = [
            'shopping-bag'           => 'ti-shopping-bag',
            'archive-box'            => 'ti-archive',
            'cake'                   => 'ti-cake',
            'home-modern'            => 'ti-home',
            'sparkles'               => 'ti-sparkles',
            'wrench-screwdriver'     => 'ti-tool',
            'cog-6-tooth'            => 'ti-settings',
            'tv'                     => 'ti-device-tv',
            'truck'                  => 'ti-truck',
            'building-office-2'      => 'ti-building-warehouse',
            'cube'                   => 'ti-cube',
            'squares-2x2'            => 'ti-layout-grid',
            'sun'                    => 'ti-sun',
            'shopping-cart'          => 'ti-shopping-cart',
            'beaker'                 => 'ti-flask',
            'calendar-days'          => 'ti-calendar-month',
            'rectangle-group'        => 'ti-layout-2',
            'speaker-wave'           => 'ti-speakerphone',
            'arrow-path'             => 'ti-recycle',
            'device-phone-mobile'    => 'ti-device-mobile',
            'home'                   => 'ti-home',
            'gift'                   => 'ti-gift',
            'scissors'               => 'ti-scissors',
            'swatch'                 => 'ti-palette',
        ];

        return 'ti ' . ($map[$this->icon] ?? 'ti-tag');
    }
}
