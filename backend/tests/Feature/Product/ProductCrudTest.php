<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(array $sellerAttributes = []): User
    {
        $user = User::factory()->create();
        $user->sellerProfile()->create($sellerAttributes);
        $token = $user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}");

        return $user;
    }

    public function test_user_can_publish_product_with_images(): void
    {
        Storage::fake('public');
        $this->authenticatedUser();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $response = $this->postJson('/api/products', [
            'title' => 'iPhone 12 en excellent état',
            'description' => 'Téléphone peu utilisé, avec chargeur.',
            'category_id' => $category->id,
            'price' => 150000,
            'quantity' => 1,
            'condition' => 'tres_bon',
            'city_id' => $city->id,
            'images' => [UploadedFile::fake()->image('photo.jpg', 800, 600)],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', Product::STATUS_ACTIVE);
        $response->assertJsonCount(1, 'data.images');
        $this->assertDatabaseHas('products', ['title' => 'iPhone 12 en excellent état', 'status' => Product::STATUS_ACTIVE]);
    }

    public function test_publishing_without_images_fails_unless_draft(): void
    {
        $this->authenticatedUser();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $response = $this->postJson('/api/products', [
            'title' => 'Sans photo',
            'description' => 'Description',
            'category_id' => $category->id,
            'price' => 1000,
            'quantity' => 1,
            'condition' => 'bon',
            'city_id' => $city->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_draft_can_be_created_without_images(): void
    {
        $this->authenticatedUser();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $response = $this->postJson('/api/products', [
            'title' => 'Brouillon',
            'description' => 'Description',
            'category_id' => $category->id,
            'price' => 1000,
            'quantity' => 1,
            'condition' => 'bon',
            'city_id' => $city->id,
            'is_draft' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', Product::STATUS_DRAFT);
    }

    public function test_standard_seller_cannot_exceed_active_products_quota(): void
    {
        Storage::fake('public');
        $user = $this->authenticatedUser(['seller_type' => SellerProfile::TYPE_STANDARD]);
        $category = Category::factory()->create();
        $city = City::factory()->create();

        Product::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $response = $this->postJson('/api/products', [
            'title' => 'Onzième annonce',
            'description' => 'Description',
            'category_id' => $category->id,
            'price' => 1000,
            'quantity' => 1,
            'condition' => 'bon',
            'city_id' => $city->id,
            'images' => [UploadedFile::fake()->image('photo.jpg')],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('quota');
    }

    public function test_pro_seller_has_no_active_products_quota(): void
    {
        Storage::fake('public');
        $user = $this->authenticatedUser([
            'seller_type' => SellerProfile::TYPE_PRO,
            'pro_expires_at' => now()->addMonth(),
        ]);
        $category = Category::factory()->create();
        $city = City::factory()->create();

        Product::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $response = $this->postJson('/api/products', [
            'title' => 'Onzième annonce Pro',
            'description' => 'Description',
            'category_id' => $category->id,
            'price' => 1000,
            'quantity' => 1,
            'condition' => 'bon',
            'city_id' => $city->id,
            'images' => [UploadedFile::fake()->image('photo.jpg')],
        ]);

        $response->assertCreated();
    }

    public function test_owner_can_update_own_product(): void
    {
        $user = $this->authenticatedUser();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson("/api/products/{$product->id}", ['title' => 'Nouveau titre']);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'title' => 'Nouveau titre']);
    }

    public function test_other_user_cannot_update_product(): void
    {
        $owner = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);
        $this->authenticatedUser();

        $response = $this->postJson("/api/products/{$product->id}", ['title' => 'Piraté']);

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_own_product(): void
    {
        $user = $this->authenticatedUser();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertOk();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_viewing_product_increments_views_count_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $owner->sellerProfile()->create();
        $product = Product::factory()->create(['user_id' => $owner->id, 'views_count' => 0]);

        $this->getJson("/api/products/{$product->id}")->assertOk();

        $this->assertSame(1, $product->fresh()->views_count);
    }

    public function test_inactive_product_is_hidden_from_public(): void
    {
        $product = Product::factory()->create(['status' => Product::STATUS_DRAFT]);

        $this->getJson("/api/products/{$product->id}")->assertStatus(404);
    }

    public function test_authenticated_user_can_list_own_products_of_any_status(): void
    {
        $user = $this->authenticatedUser();
        Product::factory()->create(['user_id' => $user->id, 'status' => Product::STATUS_DRAFT]);
        Product::factory()->create(['user_id' => $user->id, 'status' => Product::STATUS_ACTIVE]);

        $response = $this->getJson('/api/my/products');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_authenticated_buyer_can_reveal_seller_phone_and_increments_contacts(): void
    {
        $seller = User::factory()->create(['phone' => '+221771234567']);
        $seller->sellerProfile()->create();
        $product = Product::factory()->create(['user_id' => $seller->id, 'contacts_count' => 0]);
        $this->authenticatedUser();

        $response = $this->postJson("/api/products/{$product->id}/reveal-phone");

        $response->assertOk();
        $response->assertJsonPath('data.phone', '+221771234567');
        $this->assertSame(1, $product->fresh()->contacts_count);
        $this->assertSame(1, $seller->sellerProfile->fresh()->contacts_count);
    }

    public function test_owner_revealing_own_phone_does_not_increment_contacts(): void
    {
        $user = $this->authenticatedUser();
        $product = Product::factory()->create(['user_id' => $user->id, 'contacts_count' => 0]);

        $this->postJson("/api/products/{$product->id}/reveal-phone")->assertOk();

        $this->assertSame(0, $product->fresh()->contacts_count);
    }

    public function test_guest_cannot_reveal_phone(): void
    {
        $product = Product::factory()->create();

        $this->postJson("/api/products/{$product->id}/reveal-phone")->assertStatus(401);
    }
}
