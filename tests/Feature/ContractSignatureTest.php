<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractSignatureTest extends TestCase
{
    use RefreshDatabase;

    private User $hr;
    private User $employeeUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');

        $this->hr = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $this->hr->roles()->attach($role);

        $this->employeeUser = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_number' => 'EMP-100',
            'status' => 'active',
        ]);
    }

    private function createContract(array $overrides = []): EmploymentContract
    {
        $this->actingAs($this->hr)->post(route('admin.hr.contracts.store', $this->employee), array_merge([
            'contract_type' => 'Fixed term',
            'start_date' => '2026-10-01',
            'end_date' => '2027-09-30',
            'gross_salary' => 1500000,
            'currency' => 'UGX',
            'status' => 'active',
            'document' => UploadedFile::fake()->create('contract.pdf', 200, 'application/pdf'),
            'send_for_signature' => '1',
        ], $overrides))->assertRedirect()->assertSessionHasNoErrors();

        return EmploymentContract::latest('id')->firstOrFail();
    }

    private function pngDataUrl(int $width = 400, int $height = 150): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imageline($image, 10, 100, 380, 40, imagecolorallocate($image, 0, 0, 0));
        ob_start();
        imagepng($image);
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    private function signDrawn(EmploymentContract $contract, ?User $as = null, ?string $data = null)
    {
        return $this->actingAs($as ?? $this->employeeUser)->post(route('staff.contracts.sign', $contract), [
            'signature_method' => 'drawn',
            'signature_data' => $data ?? $this->pngDataUrl(),
            'signer_comment' => 'Happy to sign.',
            'agree' => '1',
        ]);
    }

    public function test_hr_attaches_contract_file_and_sends_for_signature(): void
    {
        $contract = $this->createContract();

        $this->assertNotNull($contract->document_path);
        $this->assertSame('contract.pdf', $contract->document_name);
        Storage::disk('local')->assertExists($contract->document_path);
        $this->assertSame(EmploymentContract::SIGNATURE_PENDING, $contract->signature_status);
        $this->assertNotNull($contract->sent_for_signature_at);
        $this->assertSame($this->hr->id, (int) $contract->sent_by);

        $this->assertTrue(UserNotification::where('user_id', $this->employeeUser->id)
            ->where('type', 'contract_signature_requested')->exists());

        $this->actingAs($this->hr)->get(route('admin.hr.contracts.document', $contract))->assertOk();
        $this->actingAs($this->hr)->get(route('admin.hr.employees.index'))
            ->assertOk()
            ->assertSee(route('admin.hr.contracts.index', $this->employee), false)
            ->assertSee('name="document"', false);
    }

    public function test_contract_file_must_be_pdf_or_word(): void
    {
        $this->actingAs($this->hr)->post(route('admin.hr.contracts.store', $this->employee), [
            'start_date' => '2026-10-01',
            'status' => 'draft',
            'document' => UploadedFile::fake()->create('contract.exe', 10, 'application/x-msdownload'),
        ])->assertSessionHasErrors('document');

        $this->assertSame(0, EmploymentContract::count());
    }

    public function test_employee_sees_and_signs_with_drawn_signature(): void
    {
        $contract = $this->createContract();

        $this->actingAs($this->employeeUser)->get(route('staff.contracts.index'))
            ->assertOk()->assertSee('Review &amp; Sign', false);
        $this->actingAs($this->employeeUser)->get(route('staff.contracts.show', $contract))
            ->assertOk()->assertSee('data-signature-pad', false);
        $this->actingAs($this->employeeUser)->get(route('staff.contracts.document', $contract))->assertOk();

        $this->signDrawn($contract)->assertRedirect(route('staff.contracts.show', $contract))->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertTrue($contract->isSigned());
        $this->assertSame('drawn', $contract->signature_method);
        $this->assertSame($this->employeeUser->id, (int) $contract->signed_by);
        $this->assertNotNull($contract->signed_at);
        $this->assertSame('Happy to sign.', $contract->signer_comment);
        $this->assertNotNull($contract->signer_ip);
        Storage::disk('local')->assertExists($contract->signature_path);
        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring(Storage::disk('local')->get($contract->signature_path))[2]);
        $this->assertNotNull($contract->certificate_path);
        Storage::disk('local')->assertExists($contract->certificate_path);

        $this->assertTrue(UserNotification::where('user_id', $this->hr->id)->where('type', 'contract_signed')->exists());

        $this->actingAs($this->employeeUser)->get(route('staff.contracts.signature', $contract))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($this->employeeUser)->get(route('staff.contracts.certificate', $contract))->assertOk();
    }

    public function test_employee_signs_with_uploaded_image(): void
    {
        $contract = $this->createContract();

        $this->actingAs($this->employeeUser)->post(route('staff.contracts.sign', $contract), [
            'signature_method' => 'uploaded',
            'signature_file' => UploadedFile::fake()->image('signature.jpg', 300, 120),
            'agree' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertTrue($contract->isSigned());
        $this->assertSame('uploaded', $contract->signature_method);
        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring(Storage::disk('local')->get($contract->signature_path))[2]);
    }

    public function test_uploaded_signature_must_be_an_image(): void
    {
        $contract = $this->createContract();

        $this->actingAs($this->employeeUser)->post(route('staff.contracts.sign', $contract), [
            'signature_method' => 'uploaded',
            'signature_file' => UploadedFile::fake()->create('signature.pdf', 10, 'application/pdf'),
            'agree' => '1',
        ])->assertSessionHasErrors('signature_file');

        $this->assertFalse($contract->refresh()->isSigned());
    }

    public function test_another_user_cannot_view_or_sign(): void
    {
        $contract = $this->createContract();
        $other = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($other)->get(route('staff.contracts.show', $contract))->assertForbidden();
        $this->actingAs($other)->get(route('staff.contracts.document', $contract))->assertForbidden();
        $this->signDrawn($contract, $other)->assertForbidden();
        $this->actingAs($other)->get(route('staff.contracts.index'))->assertOk()->assertDontSee('Fixed term');
        $this->actingAs($this->employeeUser)->get(route('admin.hr.contracts.document', $contract))->assertForbidden();

        $this->assertFalse($contract->refresh()->isSigned());
    }

    public function test_contract_cannot_be_signed_twice(): void
    {
        $contract = $this->createContract();
        $this->signDrawn($contract)->assertSessionHasNoErrors();
        $firstSignature = $contract->refresh()->signature_path;
        $firstSignedAt = $contract->signed_at;

        $this->signDrawn($contract)->assertRedirect(route('staff.contracts.show', $contract))->assertSessionHas('error');

        $contract->refresh();
        $this->assertSame($firstSignature, $contract->signature_path);
        $this->assertEquals($firstSignedAt, $contract->signed_at);
    }

    public function test_invalid_data_url_is_rejected(): void
    {
        $contract = $this->createContract();

        $this->signDrawn($contract, null, 'data:image/svg+xml;base64,'.base64_encode('<svg/>'))->assertSessionHasErrors('signature');
        $this->signDrawn($contract, null, 'data:image/png;base64,'.base64_encode('not really a png'))->assertSessionHasErrors('signature');
        $this->signDrawn($contract, null, 'data:image/png;base64,@@@')->assertSessionHasErrors('signature');

        $this->assertFalse($contract->refresh()->isSigned());
        $this->assertNull($contract->signature_path);
    }

    public function test_unshared_contract_is_hidden_from_employee(): void
    {
        $contract = $this->createContract(['send_for_signature' => '0']);
        $this->assertNull($contract->signature_status);

        $this->actingAs($this->employeeUser)->get(route('staff.contracts.show', $contract))->assertNotFound();
        $this->signDrawn($contract)->assertNotFound();
    }

    public function test_hr_sees_signed_state_and_cannot_replace_signed_file(): void
    {
        $contract = $this->createContract();

        $this->actingAs($this->hr)->get(route('admin.hr.contracts.index', $this->employee))
            ->assertOk()->assertSee('Awaiting signature')->assertSee('Replace file');

        $this->actingAs($this->hr)->post(route('admin.hr.contracts.document.update', $contract), [
            'document' => UploadedFile::fake()->create('contract-v2.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('contract-v2.docx', $contract->refresh()->document_name);

        $this->signDrawn($contract)->assertSessionHasNoErrors();

        $this->actingAs($this->hr)->get(route('admin.hr.contracts.index', $this->employee))
            ->assertOk()
            ->assertSee('Signed')
            ->assertSee(route('admin.hr.contracts.signature', $contract), false)
            ->assertSee(route('admin.hr.contracts.certificate', $contract), false)
            ->assertDontSee('Replace file');
        $this->actingAs($this->hr)->get(route('admin.hr.contracts.signature', $contract))->assertOk();
        $this->actingAs($this->hr)->get(route('admin.hr.contracts.certificate', $contract))->assertOk();

        $this->actingAs($this->hr)->post(route('admin.hr.contracts.document.update', $contract), [
            'document' => UploadedFile::fake()->create('contract-v3.pdf', 50, 'application/pdf'),
        ])->assertStatus(422);
        $this->assertSame('contract-v2.docx', $contract->refresh()->document_name);
    }
}
