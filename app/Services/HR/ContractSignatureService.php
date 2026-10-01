<?php

namespace App\Services\HR;

use App\Models\EmploymentContract;
use App\Models\User;
use App\Services\Files\FilePreviewService;
use App\Services\UserNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Employment contract e-signature workflow: HR attaches a contract file and sends it,
 * the employee signs it (drawn on a canvas or an uploaded image) and HR is notified.
 * All files live on the private "local" disk and are only served through authorised routes.
 */
class ContractSignatureService
{
    public const DISK = 'local';
    public const MAX_SIGNATURE_BYTES = 2 * 1024 * 1024;
    public const MAX_SIGNATURE_DIMENSION = 4000;

    public function __construct(
        private readonly UserNotificationService $notifications,
        private readonly FilePreviewService $previews
    ) {}

    /** Download, or preview (?preview=1|raw), the contract file. Callers authorise first. */
    public function documentResponse(Request $request, EmploymentContract $contract): Response
    {
        return $this->fileResponse($request, $contract->document_path, $contract->documentName());
    }

    /** Download or preview the signature certificate PDF, generating it if missing. */
    public function certificateResponse(Request $request, EmploymentContract $contract): Response
    {
        if ($contract->isSigned() && ! $contract->certificate_path) {
            $this->generateCertificate($contract);
        }

        return $this->fileResponse($request, $contract->certificate_path, 'signature-certificate-contract-'.$contract->id.'.pdf');
    }

    public function signatureResponse(EmploymentContract $contract): Response
    {
        $storage = Storage::disk(self::DISK);
        abort_unless($contract->signature_path && $storage->exists($contract->signature_path), 404);

        return response($storage->get($contract->signature_path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function fileResponse(Request $request, ?string $path, string $name): Response
    {
        abort_unless($path && Storage::disk(self::DISK)->exists($path), 404);

        if ($this->previews->wantsPreview($request)) {
            return $this->previews->respond($request, self::DISK, $path, $name);
        }

        return Storage::disk(self::DISK)->download($path, $name);
    }

    public function storeDocument(EmploymentContract $contract, UploadedFile $file): void
    {
        abort_if($contract->isSigned(), 422, 'A signed contract cannot be replaced.');

        $old = $contract->document_path;
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf');
        $path = $file->storeAs($this->directory($contract), 'contract-'.Str::random(24).'.'.$extension, self::DISK);

        $contract->update([
            'document_path' => $path,
            'document_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''),
        ]);

        if ($old && $old !== $path) {
            Storage::disk(self::DISK)->delete($old);
        }
    }

    public function sendForSignature(EmploymentContract $contract, User $sender): void
    {
        abort_if($contract->isSigned(), 422, 'This contract has already been signed.');
        abort_unless($contract->document_path, 422, 'Attach the contract file before sending it for signature.');

        $contract->update([
            'signature_status' => EmploymentContract::SIGNATURE_PENDING,
            'sent_for_signature_at' => now(),
            'sent_by' => $sender->id,
        ]);

        $employeeUser = $contract->employee?->user;

        if ($employeeUser && (int) $employeeUser->id !== (int) $sender->id) {
            $this->notifications->send(
                $employeeUser,
                'contract_signature_requested',
                'Contract ready for your signature',
                'Your '.($contract->contract_type ?: 'employment').' contract has been shared with you. Please review and sign it.',
                Route::has('staff.contracts.show') ? route('staff.contracts.show', $contract) : null,
                ['contract_id' => $contract->id]
            );
        }
    }

    /** Decode a canvas "data:image/png;base64," signature and return verified PNG bytes. */
    public function pngFromDataUrl(string $dataUrl): string
    {
        $prefix = 'data:image/png;base64,';

        if (! str_starts_with($dataUrl, $prefix)) {
            $this->fail('The drawn signature must be a PNG image.');
        }

        $bytes = base64_decode(substr($dataUrl, strlen($prefix)), true);

        if ($bytes === false || $bytes === '') {
            $this->fail('The drawn signature could not be read. Please draw it again.');
        }

        $this->assertImage($bytes, [IMAGETYPE_PNG]);

        return $bytes;
    }

    /** Verify an uploaded signature image and normalise it to PNG bytes. */
    public function pngFromUpload(UploadedFile $file): string
    {
        $bytes = (string) file_get_contents($file->getRealPath());
        $type = $this->assertImage($bytes, [IMAGETYPE_PNG, IMAGETYPE_JPEG]);

        if ($type === IMAGETYPE_PNG || ! function_exists('imagecreatefromstring')) {
            return $bytes;
        }

        $image = @imagecreatefromstring($bytes);

        if (! $image) {
            $this->fail('The signature image could not be read.');
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    public function sign(EmploymentContract $contract, User $signer, string $method, string $png, ?string $ip, ?string $userAgent, ?string $comment): EmploymentContract
    {
        $path = $this->directory($contract).'/signature-'.Str::random(24).'.png';

        $contract = DB::transaction(function () use ($contract, $signer, $method, $png, $ip, $userAgent, $comment, $path) {
            $locked = EmploymentContract::query()->with('employee')->lockForUpdate()->findOrFail($contract->id);

            abort_unless($locked->belongsToUser($signer), 403);
            abort_if($locked->isSigned(), 422, 'This contract has already been signed.');
            abort_unless($locked->isAwaitingSignature() && $locked->document_path, 422, 'This contract is not awaiting your signature.');

            Storage::disk(self::DISK)->put($path, $png);

            $locked->update([
                'signature_status' => EmploymentContract::SIGNATURE_SIGNED,
                'signed_at' => now(),
                'signed_by' => $signer->id,
                'signature_path' => $path,
                'signature_method' => $method,
                'signer_ip' => $ip,
                'signer_user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
                'signer_comment' => $comment ?: null,
            ]);

            return $locked;
        });

        $this->generateCertificate($contract);
        $this->notifySigned($contract, $signer);

        return $contract;
    }

    /** Render the one-page signature certificate PDF (contract details + signature + timestamp). */
    public function generateCertificate(EmploymentContract $contract): ?string
    {
        if (! $contract->isSigned() || ! $contract->signature_path) {
            return null;
        }

        try {
            $contract->loadMissing(['employee.user', 'signer', 'sender']);
            $signature = Storage::disk(self::DISK)->get($contract->signature_path);
            $documentHash = $contract->document_path && Storage::disk(self::DISK)->exists($contract->document_path)
                ? hash('sha256', Storage::disk(self::DISK)->get($contract->document_path))
                : null;

            $pdf = Pdf::loadView('hr.contracts.certificate', [
                'contract' => $contract,
                'signatureDataUri' => 'data:image/png;base64,'.base64_encode((string) $signature),
                'documentHash' => $documentHash,
            ])->setPaper('a4');

            $path = $this->directory($contract).'/signature-certificate.pdf';
            Storage::disk(self::DISK)->put($path, $pdf->output());
            $contract->forceFill(['certificate_path' => $path])->save();

            return $path;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function notifySigned(EmploymentContract $contract, User $signer): void
    {
        $hr = $contract->sent_by ? User::find($contract->sent_by) : null;

        if (! $hr || (int) $hr->id === (int) $signer->id) {
            return;
        }

        $this->notifications->send(
            $hr,
            'contract_signed',
            'Contract signed by '.$signer->name,
            $signer->name.' signed the '.($contract->contract_type ?: 'employment').' contract on '.$contract->signed_at?->format('d M Y H:i').'.',
            Route::has('admin.hr.contracts.index') && $contract->employee ? route('admin.hr.contracts.index', $contract->employee) : null,
            ['contract_id' => $contract->id]
        );
    }

    private function assertImage(string $bytes, array $allowedTypes): int
    {
        if (strlen($bytes) > self::MAX_SIGNATURE_BYTES) {
            $this->fail('The signature image may not be larger than 2 MB.');
        }

        $info = @getimagesizefromstring($bytes);

        if (! $info || ! in_array($info[2] ?? null, $allowedTypes, true)) {
            $this->fail('The signature must be a valid PNG or JPG image.');
        }

        if ($info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_SIGNATURE_DIMENSION || $info[1] > self::MAX_SIGNATURE_DIMENSION) {
            $this->fail('The signature image dimensions are not supported.');
        }

        return $info[2];
    }

    private function directory(EmploymentContract $contract): string
    {
        return 'hr/contracts/'.$contract->id;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['signature' => $message]);
    }
}
