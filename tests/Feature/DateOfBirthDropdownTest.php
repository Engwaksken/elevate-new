<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateOfBirthDropdownTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'surname' => 'Nansubuga',
            'given_name' => 'Sarah',
            'email' => 'sarah.dob@gmail.com',
            'phone' => '+256700000000',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
            'terms' => '1',
            'interests' => ['jobs'],
        ], $overrides);
    }

    public function test_register_form_offers_day_month_and_year_dropdowns(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="date_of_birth_day"', false)
            ->assertSee('name="date_of_birth_month"', false)
            ->assertSee('name="date_of_birth_year"', false)
            ->assertSee('<option value="'.(now()->year - 60).'"', false)
            ->assertDontSee('type="date" name="date_of_birth"', false);
    }

    public function test_dropdowns_save_the_date_of_birth(): void
    {
        $this->post(route('register.store'), $this->payload([
            'date_of_birth_day' => '7',
            'date_of_birth_month' => '3',
            'date_of_birth_year' => '1994',
        ]))->assertSessionHasNoErrors();

        $user = User::where('email', 'sarah.dob@gmail.com')->firstOrFail();
        $this->assertSame('1994-03-07', $user->profile->date_of_birth->format('Y-m-d'));
    }

    public function test_an_impossible_or_partial_date_is_rejected(): void
    {
        $this->post(route('register.store'), $this->payload([
            'date_of_birth_day' => '31',
            'date_of_birth_month' => '2',
            'date_of_birth_year' => '1994',
        ]))->assertSessionHasErrors('date_of_birth');

        $this->post(route('register.store'), $this->payload([
            'date_of_birth_day' => '',
            'date_of_birth_month' => '2',
            'date_of_birth_year' => '1994',
        ]))->assertSessionHasErrors('date_of_birth');
    }

    public function test_date_of_birth_stays_optional(): void
    {
        $this->post(route('register.store'), $this->payload([
            'date_of_birth_day' => '',
            'date_of_birth_month' => '',
            'date_of_birth_year' => '',
        ]))->assertSessionHasNoErrors();
    }
}
