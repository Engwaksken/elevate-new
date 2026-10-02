<?php

use App\Services\CmsContentService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title', 190);
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->json('settings')->nullable();
            $table->string('image_path')->nullable();
            $table->json('published_data')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        foreach (CmsContentService::PAGES as $slug => [$title]) {
            $settings = ['links' => [], 'field_labels' => [], 'signup_open' => true];
            if (in_array($slug, ['site-header', 'site-footer'], true)) {
                foreach (['Home'=>'/','Learning'=>'/learning','Jobs'=>'/jobs','Library'=>'/library','Events'=>'/events','FAQs'=>'/faqs'] as $label => $url) {
                    $settings['links'][] = compact('label', 'url');
                }
                if ($slug === 'site-footer') {
                    foreach (['Privacy Policy'=>'/privacy-policy','Terms of Use'=>'/terms','Become a Mentor'=>'/mentors/signup','Register an Employer'=>'/employers/signup'] as $label => $url) {
                        $settings['links'][] = compact('label', 'url');
                    }
                }
            }
            DB::table('cms_pages')->insert(['slug' => $slug, 'title' => $title, 'settings' => json_encode($settings), 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['cms.manage' => 'Manage Frontend Content', 'timetable.manage' => 'Manage Course Timetables'] as $slug => $name) {
            DB::table('permissions')->updateOrInsert(['slug' => $slug], ['name' => $name, 'module' => explode('.', $slug)[0], 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('permissions')->where('slug', $slug)->value('id');
            $roles = $slug === 'cms.manage' ? ['administrator', 'super-administrator', 'super-admin']
                : ['administrator', 'super-administrator', 'super-admin', 'program-officer', 'programs-officer', 'programs-lead', 'program-manager', 'operations-lead', 'operations-officer'];
            foreach (DB::table('roles')->whereIn('slug', $roles)->pluck('id') as $roleId) {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
        DB::table('permissions')->whereIn('slug', ['cms.manage', 'timetable.manage'])->delete();
    }
};
