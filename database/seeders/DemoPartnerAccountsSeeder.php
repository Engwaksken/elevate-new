<?php

namespace Database\Seeders;

use App\Models\Employer;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

/**
 * Local test accounts for the mentor and employer portals.
 * Not part of DatabaseSeeder; run explicitly:
 *   php artisan db:seed --class=DemoPartnerAccountsSeeder
 * Password for both: "password". Refuses to run in production.
 */
class DemoPartnerAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (App::environment('production')) {
            $this->command?->warn('Skipped: demo accounts are not created in production.');

            return;
        }

        $mentor = User::updateOrCreate(['email' => 'mentor.demo@example.com'], [
            'name' => 'Demo Mentor',
            'password' => 'password',
            'user_type' => 'mentor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        MentorProfile::updateOrCreate(['user_id' => $mentor->id], [
            'organisation' => 'Demo Org',
            'job_title' => 'Senior Software Engineer',
            'industry' => 'Technology',
            'years_experience' => 8,
            'skills' => ['Laravel', 'Leadership'],
            'mentoring_areas' => ['Career growth'],
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $employer = User::updateOrCreate(['email' => 'employer.demo@example.com'], [
            'name' => 'Demo Employer',
            'password' => 'password',
            'user_type' => 'employer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Employer::updateOrCreate(['owner_user_id' => $employer->id], [
            'company_name' => 'Demo Tech Ltd',
            'industry' => 'Technology',
            'country' => 'Uganda',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->command?->info('Demo accounts: mentor.demo@example.com / employer.demo@example.com (password: password)');
    }
}
