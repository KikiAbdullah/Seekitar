<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel role & permission Spatie.
 *
 * KENAPA DITULIS SENDIRI, BUKAN MEMAKAI STUB BAWAAN
 * -------------------------------------------------
 * Stub `create_permission_tables.php.stub` mendeklarasikan kolom morph
 * sebagai `unsignedBigInteger`. `users.id` di Seekitar adalah UUID
 * (`CHAR(36)`), sehingga stub itu akan membuat FK bertipe salah dan
 * penetapan role ke pengguna gagal.
 *
 * `config/permission.php` juga sudah disetel `model_morph_key => 'model_uuid'`
 * sesuai anjuran Spatie untuk primary key UUID.
 *
 * Fitur `teams` tidak dipakai (satu tenant, satu kabupaten), jadi cabangnya
 * sengaja tidak disalin agar migrasi ini tetap mudah dibaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableNames  = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole       = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $morphKey        = $columnNames['model_morph_key'];   // 'model_uuid'

        throw_if(empty($tableNames), 'config/permission.php tidak termuat. Jalankan: php artisan config:clear');

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->id();
            // 125 karakter, bukan 255 bawaan: utf8mb4 memakai 4 byte/karakter,
            // dan UNIQUE(name, guard_name) melewati batas 3072 byte indeks
            // InnoDB bila keduanya VARCHAR(255) — error 1071.
            $table->string('name', 125);
            $table->string('guard_name', 125);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) {
            $table->id();
            $table->string('name', 125);
            $table->string('guard_name', 125);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotPermission, $morphKey) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type', 125);
            $table->uuid($morphKey);                      // CHAR(36), cocok dengan users.id

            $table->index([$morphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $morphKey, 'model_type'],
                'model_has_permissions_permission_model_type_primary');
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $pivotRole, $morphKey) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type', 125);
            $table->uuid($morphKey);

            $table->index([$morphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotRole, $morphKey, 'model_type'],
                'model_has_roles_role_model_type_primary');
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id')->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole],
                'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(empty($tableNames), 'config/permission.php tidak termuat. Jalankan: php artisan config:clear');

        // Urutan terbalik: tabel pivot lebih dulu karena memegang FK.
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
