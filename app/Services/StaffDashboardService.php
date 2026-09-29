<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
final class StaffDashboardService {
 public function for(User $user): array {
   return ['user'=>$user,'abilities'=>[
    'manage_courses'=>$this->allows($user,'manage-assigned-courses'),
    'manage_users'=>$this->allows($user,'manage-users'),
    'view_reports'=>$this->allows($user,'view-reports')],
    'stats'=>['courses'=>$this->count('courses'),'participants'=>$this->count('participants'),'assignments'=>$this->count('assignments'),'notifications'=>$this->count('notifications')]];
 }
 private function allows(User $user,string $ability): bool { try { return $user->can($ability); } catch (\Throwable) { return false; } }
 private function count(string $table): int { return Schema::hasTable($table)?(int)DB::table($table)->count():0; }
}
