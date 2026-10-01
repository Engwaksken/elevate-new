<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\EmploymentContract;
use App\Services\HR\ContractSignatureService;
use Illuminate\Http\Request;

/**
 * "My Contracts": the signed-in employee reviews the contracts HR has shared with them
 * and signs them by drawing or uploading a signature.
 */
class StaffContractController extends Controller
{
    public function index(Request $request)
    {
        $contracts = EmploymentContract::query()
            ->whereHas('employee', fn ($query) => $query->where('user_id', $request->user()->id))
            ->whereNotNull('signature_status')
            ->latest('sent_for_signature_at')
            ->latest()
            ->get();

        return view('hr.contracts.index', compact('contracts'));
    }

    public function show(Request $request, EmploymentContract $contract)
    {
        $this->authoriseOwner($request, $contract);
        $contract->load(['employee.user', 'sender']);

        return view('hr.contracts.show', compact('contract'));
    }

    public function document(Request $request, EmploymentContract $contract, ContractSignatureService $signatures)
    {
        $this->authoriseOwner($request, $contract);

        return $signatures->documentResponse($request, $contract);
    }

    public function signature(Request $request, EmploymentContract $contract, ContractSignatureService $signatures)
    {
        $this->authoriseOwner($request, $contract);

        return $signatures->signatureResponse($contract);
    }

    public function certificate(Request $request, EmploymentContract $contract, ContractSignatureService $signatures)
    {
        $this->authoriseOwner($request, $contract);
        abort_unless($contract->isSigned(), 404);

        return $signatures->certificateResponse($request, $contract);
    }

    public function sign(Request $request, EmploymentContract $contract, ContractSignatureService $signatures)
    {
        $this->authoriseOwner($request, $contract);

        if ($contract->isSigned()) {
            return redirect()->route('staff.contracts.show', $contract)->with('error', 'This contract has already been signed.');
        }

        abort_unless($contract->isAwaitingSignature(), 422, 'This contract is not awaiting your signature.');

        $data = $request->validate([
            'signature_method' => ['required', 'in:drawn,uploaded'],
            'signature_data' => ['required_if:signature_method,drawn', 'nullable', 'string', 'max:3000000'],
            'signature_file' => ['required_if:signature_method,uploaded', 'nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'signer_comment' => ['nullable', 'string', 'max:1000'],
            'agree' => ['accepted'],
        ], [
            'signature_data.required_if' => 'Please draw your signature before submitting.',
            'signature_file.required_if' => 'Please choose a signature image to upload.',
            'agree.accepted' => 'Please confirm that you have read and agree to the contract.',
        ]);

        $png = $data['signature_method'] === 'drawn'
            ? $signatures->pngFromDataUrl((string) $data['signature_data'])
            : $signatures->pngFromUpload($request->file('signature_file'));

        $signatures->sign(
            $contract,
            $request->user(),
            $data['signature_method'],
            $png,
            $request->ip(),
            $request->userAgent(),
            $data['signer_comment'] ?? null
        );

        return redirect()->route('staff.contracts.show', $contract)->with('success', 'Thank you. Your signed contract has been sent back to HR.');
    }

    /** Only the employee the contract belongs to may see it, and only once HR has shared it. */
    private function authoriseOwner(Request $request, EmploymentContract $contract): void
    {
        abort_unless($contract->belongsToUser($request->user()), 403);
        abort_unless($contract->signature_status, 404);
    }
}
