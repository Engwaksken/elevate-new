<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\Resume;
use App\Models\ResumeCertification;
use App\Models\ResumeEducation;
use App\Models\ResumeExperience;
use App\Models\ResumeLanguage;
use App\Models\ResumeProject;
use App\Models\ResumeReferee;
use App\Models\ResumeSkill;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * The resume templates offered to participants and an in-memory sample resume
 * used to preview them before choosing (nothing is saved).
 */
class ResumeTemplates
{
    public const TEMPLATES = [
        'classic' => ['Classic', 'Traditional and formal'],
        'modern' => ['Modern', 'Clean contemporary layout'],
        'minimal' => ['Minimal', 'Simple and highly readable'],
        'professional' => ['Professional', 'Balanced business layout'],
        'graduate' => ['Graduate', 'Ideal for early-career applicants'],
        'technology' => ['Technology', 'Strong fit for digital and technical roles'],
        'executive' => ['Executive', 'Leadership-focused presentation'],
    ];

    public static function keys(): array
    {
        return array_keys(self::TEMPLATES);
    }

    public static function sample(string $template): Resume
    {
        $user = new User(['name' => 'Amina Nakato', 'email' => 'amina.nakato@example.com', 'phone' => '+256 700 123 456']);
        $user->setRelation('profile', new Profile(['district' => 'Kampala', 'country' => 'Uganda']));

        $resume = new Resume([
            'title' => 'Sample resume',
            'template' => $template,
            'professional_summary' => 'IT support graduate with hands-on experience helping small organisations keep their computers, networks and accounts running smoothly. '
                .'Patient when explaining technology to non-technical colleagues, and comfortable working alone or in a team. '
                .'Looking for an IT support or junior systems role where I can keep learning and solve real problems.',
            'portfolio_url' => 'https://github.com/amina-nakato',
        ]);
        $resume->setRelation('user', $user);

        $resume->setRelation('experiences', collect([
            new ResumeExperience(['job_title' => 'IT Support Intern', 'organisation' => 'Kampala Digital Hub', 'location' => 'Kampala', 'start_date' => Carbon::parse('2025-06-01'), 'is_current' => true,
                'description' => 'Set up laptops and printers for 40 staff, resolved help desk requests within a day, and wrote simple how-to guides for common problems.']),
            new ResumeExperience(['job_title' => 'Volunteer Computer Trainer', 'organisation' => 'Women in Technology Uganda', 'location' => 'Kampala', 'start_date' => Carbon::parse('2024-01-01'), 'end_date' => Carbon::parse('2025-05-01'),
                'description' => 'Taught basic computer and internet skills to groups of 15 young women every Saturday.']),
        ]));
        $resume->setRelation('education', collect([
            new ResumeEducation(['qualification' => 'Diploma in Information Technology', 'institution' => 'Makerere University Business School', 'field_of_study' => 'Computer Networks', 'start_date' => Carbon::parse('2022-08-01'), 'end_date' => Carbon::parse('2024-12-01')]),
            new ResumeEducation(['qualification' => 'Uganda Advanced Certificate of Education', 'institution' => 'Gayaza High School', 'end_date' => Carbon::parse('2021-11-01')]),
        ]));
        $resume->setRelation('skills', collect(['Computer hardware repair', 'Windows and Linux', 'Network setup', 'Microsoft 365', 'Customer service', 'Basic Python']
            )->map(fn ($skill) => new ResumeSkill(['skill' => $skill])));
        $resume->setRelation('certifications', collect([
            new ResumeCertification(['name' => 'CompTIA IT Fundamentals', 'issuer' => 'CompTIA', 'issued_on' => Carbon::parse('2025-03-01')]),
        ]));
        $resume->setRelation('languages', collect([
            new ResumeLanguage(['language' => 'English', 'proficiency' => 'Fluent']),
            new ResumeLanguage(['language' => 'Luganda', 'proficiency' => 'Native']),
        ]));
        $resume->setRelation('projects', collect([
            new ResumeProject(['name' => 'Help desk tracker', 'description' => 'A simple web app that records IT requests and shows which ones are still open.']),
        ]));
        $resume->setRelation('referees', collect([
            new ResumeReferee(['name' => 'Grace Atim', 'job_title' => 'IT Manager', 'organisation' => 'Kampala Digital Hub', 'email' => 'grace.atim@example.com', 'phone' => '+256 772 000 111', 'relationship' => 'Supervisor']),
        ]));
        $resume->setRelation('portfolioFiles', collect());

        return $resume;
    }
}
