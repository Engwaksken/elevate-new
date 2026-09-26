<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function message(Request $request): JsonResponse
    {
        $data=$request->validate([
            'message'=>['required','string','max:1000'],
        ]);

        $message=Str::lower(trim($data['message']));
        $user=$request->user();

        $reply=$this->replyFor($message,$user?->name);
        $links=$this->linksFor($message);

        return response()->json([
            'message'=>$reply,
            'links'=>$links,
        ]);
    }

    private function replyFor(string $message, ?string $name=null): string
    {
        $hello=$name ? "Hello {$name}. " : '';

        if($this->hasAny($message,['hello','hi','hey','good morning','good afternoon','good evening'])){
            return $hello.'I can help you find learning, mentorship, jobs, library resources, profile support and accessibility tools on ElevateHer360.';
        }

        if($this->hasAny($message,['accessibility','pwd','disability','screen reader','contrast','font size','text size','dyslexia','read page','read aloud'])){
            return 'Use the Accessibility button at the bottom-right of the page. You can increase text size, enable high contrast, greyscale, dyslexia-friendly text, underline links, reduce motion, use a larger cursor, highlight keyboard focus, use a reading guide, or have the main page read aloud.';
        }

        if($this->hasAny($message,['course','courses','learning','lesson','module','assessment','quiz'])){
            return 'Open Learning to view your enrolled courses, modules, lessons and assessments. Your participant dashboard also shows recent learning progress.';
        }

        if($this->hasAny($message,['mentor','mentorship','session','coach'])){
            return 'Open Mentorship to review your mentor relationship, upcoming sessions and mentorship activities.';
        }

        if($this->hasAny($message,['job','jobs','vacancy','opportunity','application','employer'])){
            return 'Open Jobs to browse opportunities. Your dashboard and My Applications area help you track submitted applications.';
        }

        if($this->hasAny($message,['library','book','resource','resources','download'])){
            return 'Open the Digital Library to search resources by title, author, category and language. Access to some resources depends on your account type.';
        }

        if($this->hasAny($message,['resume','cv','career','career ai'])){
            return 'Use the Career tools to build or update your resume and access career guidance features available to your account.';
        }

        if($this->hasAny($message,['profile','account','phone','email','password'])){
            return 'Open Profile to update your account and personal information. If you cannot sign in, use Forgot Password on the login page.';
        }

        if($this->hasAny($message,['notification','notifications','bell','alert'])){
            return 'Open Notifications to review updates that need your attention. Unread notifications are also shown on the participant dashboard.';
        }

        if($this->hasAny($message,['help','support','how do i','where do i'])){
            return 'Tell me what you want to do, for example: find a course, contact a mentor, browse jobs, use the library, update your profile, reset your password, or use accessibility features.';
        }

        return 'I can help you navigate ElevateHer360. Try asking about Learning, Mentorship, Jobs, Library, Resume/Career, Profile, Notifications or Accessibility.';
    }

    private function linksFor(string $message): array
    {
        $links=[];

        $add=function(string $route,string $label) use (&$links){
            if(Route::has($route)){
                $links[]=[
                    'label'=>$label,
                    'url'=>route($route),
                ];
            }
        };

        if($this->hasAny($message,['course','courses','learning','lesson','module','assessment','quiz'])){
            $add('learning.my-courses','My Learning');
            $add('learning.index','Learning');
        }

        if($this->hasAny($message,['mentor','mentorship','session','coach'])){
            $add('mentorship.dashboard','Mentorship');
        }

        if($this->hasAny($message,['job','jobs','vacancy','opportunity','application','employer'])){
            $add('jobs.index','Browse Jobs');
        }

        if($this->hasAny($message,['library','book','resource','resources','download'])){
            $add('library.index','Digital Library');
        }

        if($this->hasAny($message,['resume','cv','career','career ai'])){
            $add('career.resume.index','Resume Builder');
        }

        if($this->hasAny($message,['profile','account','phone','email'])){
            $add('profile.edit','My Profile');
        }

        if($this->hasAny($message,['notification','notifications','bell','alert'])){
            $add('notifications.index','Notifications');
        }

        if($links===[]){
            $add('dashboard','Dashboard');
            $add('home','Home');
        }

        return $links;
    }

    private function hasAny(string $message,array $terms): bool
    {
        foreach($terms as $term){
            if(Str::contains($message,$term)){
                return true;
            }
        }

        return false;
    }
}
