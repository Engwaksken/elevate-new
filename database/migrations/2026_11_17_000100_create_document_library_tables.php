<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SharePoint-style department document libraries: each department is a
 * "site" holding a folder tree of files with version history. Access comes
 * from department membership and from explicit shares on the site, a folder
 * or a single file (view / edit / full control), inherited down the tree.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('document_folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['department_id', 'parent_id']);
        });

        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('extension', 20)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['department_id', 'folder_id']);
        });

        Schema::create('document_file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_file_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime_type', 150)->nullable();
            $table->string('comment', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['document_file_id', 'version']);
        });

        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            // department (whole site), folder or file
            $table->string('shareable_type', 20);
            $table->unsignedBigInteger('shareable_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10); // view | edit | full
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['shareable_type', 'shareable_id', 'user_id']);
            $table->index('user_id');
        });

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'documents.manage'],
            [
                'name' => 'Manage All Department Documents',
                'module' => 'documents',
                'description' => 'Full control over every department document library.',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
        $permissionId = DB::table('permissions')->where('slug', 'documents.manage')->value('id');
        foreach (DB::table('roles')->whereIn('slug', ['administrator', 'super-administrator', 'super-admin'])->pluck('id') as $roleId) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
        Schema::dropIfExists('document_file_versions');
        Schema::dropIfExists('document_files');
        Schema::dropIfExists('document_folders');

        $permissionId = DB::table('permissions')->where('slug', 'documents.manage')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
