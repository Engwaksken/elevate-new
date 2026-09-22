<?php
namespace Database\Seeders;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
class RolePermissionSeeder extends Seeder {
    public function run(): void {
        $roles=[
            ['Super Administrator','super-administrator'],['Administrator','administrator'],['Programs Lead','programs-lead'],['Program Manager','program-manager'],['Program Officer','program-officer'],['M&E / MEAL Lead','meal-lead'],['M&E Officer','me-officer'],['Operations Lead','operations-lead'],['Operations Officer','operations-officer'],['Instructor / Trainer','instructor'],['Mentorship Coordinator','mentorship-coordinator'],['Career Coach','career-coach'],['Jobs / Placement Officer','placement-officer'],['Library Administrator','library-administrator'],['HR','hr'],['Procurement Officer','procurement-officer'],['Asset / Stores Officer','asset-stores-officer'],['Finance','finance'],['Consultant','consultant'],['Viewer','viewer'],['Student / Learner','student'],['Graduate / Alumni','alumni'],['Job Seeker','job-seeker'],['Mentor','mentor'],['Employer','employer']
        ];
        foreach($roles as [$name,$slug]) Role::updateOrCreate(['slug'=>$slug],['name'=>$name,'is_system'=>true]);
        $permissions=['users.view','users.create','users.edit','users.delete','roles.manage','permissions.manage','programmes.view','programmes.manage','cohorts.view','cohorts.manage','courses.view','courses.create','courses.edit','courses.delete','students.view','students.edit','mentors.view','mentors.manage','mentorship.match','jobs.view','jobs.manage','employers.approve','library.manage','workplans.view','workplans.create','workplans.edit','workplans.approve','milestones.manage','activities.manage','tasks.manage','indicators.view','indicators.manage','indicators.verify','meal.view','meal.manage','calendar.manage','hr.view','hr.manage','leave.view','leave.approve','appraisals.view','appraisals.manage','staff_exit.manage','assets.view','assets.manage','assets.dispose','procurement.view','procurement.create','procurement.approve','procurement.receive','reports.view','reports.export','settings.manage'];
        foreach($permissions as $slug) Permission::updateOrCreate(['slug'=>$slug],['name'=>ucwords(str_replace(['.','_'],' ',$slug)),'module'=>explode('.',$slug)[0]]);
        $super=Role::where('slug','super-administrator')->firstOrFail();
        $super->permissions()->sync(Permission::pluck('id'));
        $viewer=Role::where('slug','viewer')->firstOrFail();
        $viewer->permissions()->sync(Permission::where('slug','like','%.view')->pluck('id'));
    }
}
