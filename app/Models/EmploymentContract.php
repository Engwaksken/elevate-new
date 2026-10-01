<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmploymentContract extends Model
{
    public const SIGNATURE_PENDING='pending_signature';
    public const SIGNATURE_SIGNED='signed';

    protected $fillable=[
        'employee_id','contract_type','start_date','end_date','gross_salary','currency','document_path','status',
        'document_name','signature_status','sent_for_signature_at','sent_by','signed_at','signed_by',
        'signature_path','signature_method','signer_ip','signer_user_agent','signer_comment','certificate_path',
    ];
    protected $casts=['start_date'=>'date','end_date'=>'date','gross_salary'=>'decimal:2','sent_for_signature_at'=>'datetime','signed_at'=>'datetime'];

    public function employee(){ return $this->belongsTo(Employee::class); }
    public function sender(){ return $this->belongsTo(User::class,'sent_by'); }
    public function signer(){ return $this->belongsTo(User::class,'signed_by'); }

    public function isSigned(): bool { return $this->signature_status===self::SIGNATURE_SIGNED; }
    public function isAwaitingSignature(): bool { return $this->signature_status===self::SIGNATURE_PENDING; }

    /** True when the given user is the employee this contract belongs to. */
    public function belongsToUser(?User $user): bool
    {
        return $user && (int)$this->employee?->user_id===(int)$user->id;
    }

    public function documentName(): string
    {
        return $this->document_name ?: ('contract-'.$this->id.'.'.pathinfo((string)$this->document_path,PATHINFO_EXTENSION));
    }

    public function signatureLabel(): string
    {
        return match($this->signature_status){
            self::SIGNATURE_SIGNED=>'Signed',
            self::SIGNATURE_PENDING=>'Awaiting signature',
            default=>$this->document_path ? 'Not sent' : 'No document',
        };
    }

    /** status-chip modifier class used by the HR and staff views. */
    public function signatureChip(): string
    {
        return match($this->signature_status){
            self::SIGNATURE_SIGNED=>'completed',
            self::SIGNATURE_PENDING=>'on_hold',
            default=>'draft',
        };
    }
}
