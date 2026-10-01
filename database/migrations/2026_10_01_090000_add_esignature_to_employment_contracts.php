<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Electronic signature workflow for employment contracts. The signature state lives in its own
     * nullable column so the existing MySQL `status` enum (draft/active/expired/terminated) is untouched:
     * null = not sent, pending_signature = waiting on the employee, signed = returned signed.
     */
    private array $columns = [
        'document_name' => 'string',
        'signature_status' => 'string',
        'sent_for_signature_at' => 'timestamp',
        'sent_by' => 'user',
        'signed_at' => 'timestamp',
        'signed_by' => 'user',
        'signature_path' => 'string',
        'signature_method' => 'string',
        'signer_ip' => 'ip',
        'signer_user_agent' => 'string',
        'signer_comment' => 'text',
        'certificate_path' => 'string',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('employment_contracts')) {
            return;
        }

        Schema::table('employment_contracts', function (Blueprint $table) {
            foreach ($this->columns as $column => $type) {
                if (Schema::hasColumn('employment_contracts', $column)) {
                    continue;
                }

                match ($type) {
                    'timestamp' => $table->timestamp($column)->nullable(),
                    'user' => $table->unsignedBigInteger($column)->nullable()->index(),
                    'ip' => $table->string($column, 45)->nullable(),
                    'text' => $table->text($column)->nullable(),
                    default => $table->string($column)->nullable(),
                };
            }
        });

        if (! Schema::hasIndex('employment_contracts', ['signature_status'])) {
            Schema::table('employment_contracts', function (Blueprint $table) {
                $table->index('signature_status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employment_contracts')) {
            return;
        }

        if (Schema::hasIndex('employment_contracts', ['signature_status'])) {
            Schema::table('employment_contracts', function (Blueprint $table) {
                $table->dropIndex(['signature_status']);
            });
        }

        foreach (['sent_by', 'signed_by'] as $column) {
            if (Schema::hasIndex('employment_contracts', [$column])) {
                Schema::table('employment_contracts', function (Blueprint $table) use ($column) {
                    $table->dropIndex([$column]);
                });
            }
        }

        $existing = array_values(array_filter(
            array_keys($this->columns),
            fn ($column) => Schema::hasColumn('employment_contracts', $column)
        ));

        if ($existing !== []) {
            Schema::table('employment_contracts', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
