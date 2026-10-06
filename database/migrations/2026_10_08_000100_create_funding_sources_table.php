<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master list of funding sources so staff pick one instead of typing it.
 * Records keep storing the chosen name as text (purchase_requests, assets,
 * activities .funding_source), so older free-text values stay readable.
 *
 * Seeds the list only when it is empty: from the distinct values already
 * typed on those records, otherwise "Core funding" and "Unrestricted".
 */
return new class extends Migration
{
    /** Tables that store a funding source name as text. */
    private const SOURCE_TABLES = ['purchase_requests', 'assets', 'activities'];

    private const DEFAULTS = ['Core funding', 'Unrestricted'];

    public function up(): void
    {
        if (! Schema::hasTable('funding_sources')) {
            Schema::create('funding_sources', function (Blueprint $table) {
                $table->id();
                $table->string('name', 190)->unique();
                $table->string('code', 50)->nullable()->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (DB::table('funding_sources')->exists()) {
            return;
        }

        $names = [];
        foreach (self::SOURCE_TABLES as $table) {
            if (! Schema::hasColumn($table, 'funding_source')) {
                continue;
            }
            foreach (DB::table($table)->whereNotNull('funding_source')->distinct()->pluck('funding_source') as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $names[mb_strtolower($value)] ??= mb_substr($value, 0, 190);
                }
            }
        }

        if ($names === []) {
            $names = self::DEFAULTS;
        }

        $now = now();
        DB::table('funding_sources')->insert(array_map(
            fn (string $name) => ['name' => $name, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            array_values($names)
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('funding_sources');
    }
};
