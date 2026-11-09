<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryResource extends Model
{
    use SoftDeletes;

    public const DOWNLOAD_FREE='free';
    public const DOWNLOAD_ON_REQUEST='request';

    protected $fillable=[
        'library_category_id','title','author','description','tags','cover_image_path',
        'file_path','external_url','language','publication_date','access_level','download_policy',
        'views_count','downloads_count','is_active','created_by'
    ];
    protected $casts=['tags'=>'array','publication_date'=>'date','is_active'=>'boolean'];

    public function category(){ return $this->belongsTo(LibraryCategory::class,'library_category_id'); }

    /** Courses this book belongs to. None means it is open to every participant. */
    public function courses()
    {
        return $this->belongsToMany(Course::class)->withTimestamps();
    }

    /**
     * Books the user may see as far as courses go: staff see everything;
     * everyone else sees books with no course, plus books of courses they
     * are actively enrolled in. Access level is checked separately.
     */
    public function scopeAvailableTo($query, ?User $user)
    {
        if ($user?->isStaff()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->whereDoesntHave('courses');

            if ($user) {
                $q->orWhereHas('courses', fn ($c) => $c->whereIn('courses.id', self::activeCourseIdsQuery($user)));
            }
        });
    }

    public function isAvailableTo(?User $user): bool
    {
        if ($user?->isStaff() || ! $this->courses()->exists()) {
            return true;
        }

        return $user !== null
            && $this->courses()->whereIn('courses.id', self::activeCourseIdsQuery($user))->exists();
    }

    private static function activeCourseIdsQuery(User $user)
    {
        return Enrolment::query()
            ->select('course_id')
            ->where('user_id', $user->id)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', \App\Services\EnrolmentService::INACTIVE_STATUSES));
    }

    public function requiresDownloadApproval(): bool { return $this->download_policy===self::DOWNLOAD_ON_REQUEST; }

    /** Whether this user may save a copy: free books, staff, or an unexpired approved request. */
    public function canBeDownloadedBy(?User $user): bool
    {
        if(!$this->requiresDownloadApproval()) return true;
        if(!$user) return false;
        if($user->isStaff()) return true;

        return LibraryDownloadRequest::where('user_id',$user->id)->where('library_resource_id',$this->id)
            ->where('status',LibraryDownloadRequest::APPROVED)->where('expires_at','>',now())->exists();
    }

    /** PDFs can be read page by page in the built-in reader, with progress saved. */
    public function isPdf(): bool
    {
        return $this->file_path && strtolower(pathinfo($this->file_path,PATHINFO_EXTENSION))==='pdf';
    }
}
