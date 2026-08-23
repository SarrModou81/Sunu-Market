<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_by_seller(): void
    {
        $seller = User::factory()->create();
        Product::factory()->create(['user_id' => $seller->id]);
        Product::factory()->create();

        $response = $this->getJson("/api/products?seller_id={$seller->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_filters_by_keyword(): void
    {
        Product::factory()->create(['title' => 'Chaussures de sport Nike']);
        Product::factory()->create(['title' => 'Table basse en bois']);

        $response = $this->getJson('/api/products?q=nike');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Chaussures de sport Nike');
    }

    public function test_search_filters_by_category_and_price_range(): void
    {
        $category = Category::factory()->create();
        $other = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'price' => 5000]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 500000]);
        Product::factory()->create(['category_id' => $other->id, 'price' => 5000]);

        $response = $this->getJson("/api/products?category_id={$category->id}&min_price=1000&max_price=10000");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_only_returns_active_products(): void
    {
        Product::factory()->create(['status' => Product::STATUS_ACTIVE]);
        Product::factory()->create(['status' => Product::STATUS_DRAFT]);
        Product::factory()->create(['status' => Product::STATUS_BLOCKED]);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_sort_by_price_ascending(): void
    {
        Product::factory()->create(['price' => 30000, 'title' => 'Cher']);
        Product::factory()->create(['price' => 5000, 'title' => 'Pas cher']);

        $response = $this->getJson('/api/products?sort=price_asc');

        $response->assertOk();
        $response->assertJsonPath('data.0.title', 'Pas cher');
    }

    public function test_boosted_products_appear_in_relevance_results_without_monopolizing(): void
    {
        Product::factory()->count(15)->create();
        $boosted = Product::factory()->boosted()->create(['title' => 'Annonce boostée']);

        $response = $this->getJson('/api/products?per_page=20');

        $response->assertOk();
        // Rotation : 4 annonces normales puis 1 boostée (voir ProductSearchService::BOOST_SLOT_EVERY),
        // la boostée n'occupe donc pas la première place mais reste visible.
        $response->assertJsonPath('data.4.title', 'Annonce boostée');
        $this->assertNotSame('Annonce boostée', $response->json('data.0.title'));
    }

    public function test_pagination_metadata_is_present(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products?per_page=2');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonCount(2, 'data');
    }
}
