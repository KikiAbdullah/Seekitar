<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 *
 * ⚠️ Kategori PRODUKSI dibuat `CategorySeeder` (24 baris tetap, dengan slug
 * dan ikon yang sudah disepakati BRANDING §3.7). Factory ini HANYA untuk test
 * yang butuh kategori sembarang — jangan dipakai mengisi data contoh, karena
 * slug acak tidak akan cocok dengan referensi di seeder lain.
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $nama = fake()->unique()->words(2, true);

        return [
            'name'       => Str::title($nama),
            // Slug diberi akhiran acak: kolomnya UNIQUE dan words() bisa
            // mengulang kombinasi yang sama pada jumlah besar.
            'slug'       => Str::slug($nama).'-'.Str::lower(Str::random(4)),
            'parent_id'  => null,
            'icon'       => 'squares-2x2',
            'sort_order' => fake()->numberBetween(0, 99),
        ];
    }

    /** Subkategori. Taksonomi DIBATASI dua level (PRD §5.1). */
    public function anakDari(Category $induk): static
    {
        return $this->state(['parent_id' => $induk->id]);
    }
}
