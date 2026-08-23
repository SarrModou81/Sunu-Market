<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_active_categories_with_subcategories(): void
    {
        $category = Category::factory()->create(['is_active' => true, 'position' => 0]);
        $category->subcategories()->create(['name' => 'Sous A', 'slug' => 'sous-a', 'is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.subcategories.0.name', 'Sous A');
    }

    public function test_public_can_list_active_cities(): void
    {
        City::factory()->create(['is_active' => true]);
        City::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/cities');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_only_admin_can_create_category(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/categories', ['name' => 'Sport']);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_and_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $create = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/categories', ['name' => 'Sport']);
        $create->assertCreated();
        $categoryId = $create->json('data.id');

        $update = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/admin/categories/{$categoryId}", ['name' => 'Sport & Loisirs']);
        $update->assertOk();
        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'name' => 'Sport & Loisirs']);
    }

    public function test_admin_can_add_subcategory(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/categories/{$category->id}/subcategories", ['name' => 'Ballons']);

        $response->assertCreated();
        $this->assertDatabaseHas('subcategories', ['category_id' => $category->id, 'name' => 'Ballons']);
    }
}
