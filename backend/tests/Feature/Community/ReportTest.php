<?php

namespace Tests\Feature\Community;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_a_product(): void
    {
        $product = Product::factory()->create();
        $reporter = User::factory()->create();
        $token = $reporter->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/reports', [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'faux_produit',
                'message' => 'Ce produit semble frauduleux.',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => 'product',
            'reportable_id' => $product->id,
            'reason' => 'faux_produit',
        ]);
    }

    public function test_arbitrary_class_name_is_rejected_as_reportable_type(): void
    {
        $reporter = User::factory()->create();
        $token = $reporter->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/reports', [
                'reportable_type' => 'App\\Models\\User',
                'reportable_id' => 1,
                'reason' => 'autre',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('reportable_type');
    }

    public function test_user_cannot_report_self(): void
    {
        $reporter = User::factory()->create();
        $token = $reporter->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/reports', [
                'reportable_type' => 'user',
                'reportable_id' => $reporter->id,
                'reason' => 'harcelement',
            ]);

        $response->assertStatus(422);
    }

    public function test_invalid_reason_is_rejected(): void
    {
        $product = Product::factory()->create();
        $reporter = User::factory()->create();
        $token = $reporter->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/reports', [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'invalide',
            ]);

        $response->assertStatus(422);
    }
}
