<?php

namespace Tests\Feature;

use App\Models\LibraryCategory;
use App\Models\LibraryResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function librarian(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));

        return $user;
    }

    public function test_library_page_offers_the_categories_window_and_quick_add_option(): void
    {
        LibraryCategory::create(['name' => 'Careers', 'slug' => 'careers', 'is_active' => true]);

        $this->actingAs($this->librarian())->get(route('admin.library.index'))
            ->assertOk()
            ->assertSee('id="manageLibraryCategories"', false)
            ->assertSee(route('admin.library.categories.store'), false)
            ->assertSee('+ New category')
            ->assertSee('Careers');
    }

    public function test_staff_can_add_rename_switch_off_and_delete_categories(): void
    {
        $this->actingAs($this->librarian())
            ->post(route('admin.library.categories.store'), ['name' => '  Digital   Skills ', 'description' => 'Computers'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('open_library_modal', 'categories');

        $category = LibraryCategory::sole();
        $this->assertSame('Digital Skills', $category->name);
        $this->assertSame('digital-skills', $category->slug);
        $this->assertTrue($category->is_active);

        $this->post(route('admin.library.categories.store'), ['name' => 'Digital Skills'])->assertSessionHasErrors('name');

        $this->put(route('admin.library.categories.update', $category), ['name' => 'ICT Skills'])->assertSessionHasNoErrors();
        $category->refresh();
        $this->assertSame('ICT Skills', $category->name);
        $this->assertSame('ict-skills', $category->slug);
        $this->assertFalse($category->is_active);

        $this->delete(route('admin.library.categories.destroy', $category))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('library_categories', 0);
    }

    public function test_a_category_used_by_books_cannot_be_deleted(): void
    {
        $category = LibraryCategory::create(['name' => 'Careers', 'slug' => 'careers', 'is_active' => true]);
        $book = LibraryResource::create(['title' => 'CV Guide', 'language' => 'English', 'access_level' => 'authenticated', 'library_category_id' => $category->id]);
        $book->delete(); // archived books still count

        $this->actingAs($this->librarian())
            ->delete(route('admin.library.categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('library_categories', ['id' => $category->id]);
    }

    public function test_quick_add_from_the_book_form_returns_json(): void
    {
        $this->actingAs($this->librarian())
            ->postJson(route('admin.library.categories.store'), ['name' => 'Health'])
            ->assertCreated()
            ->assertJson(['name' => 'Health']);

        $this->postJson(route('admin.library.categories.store'), ['name' => 'health'])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_participants_cannot_manage_categories(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);

        $this->actingAs($participant)->post(route('admin.library.categories.store'), ['name' => 'Hack'])->assertForbidden();
        $this->assertDatabaseCount('library_categories', 0);
    }
}
