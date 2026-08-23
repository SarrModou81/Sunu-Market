<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_dismiss_report(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $product = Product::factory()->create();
        $report = Report::create([
            'reporter_id' => User::factory()->create()->id,
            'reportable_type' => 'product',
            'reportable_id' => $product->id,
            'reason' => 'arnaque',
            'status' => Report::STATUS_PENDING,
        ]);

        $this->authenticateAs($moderator);
        $response = $this->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => 'dismissed',
            'resolution_note' => 'Aucune preuve trouvée',
        ]);

        $response->assertOk();
        $this->assertSame(Report::STATUS_DISMISSED, $report->fresh()->status);
        $this->assertSame($moderator->id, $report->fresh()->handled_by);
        $this->assertNotNull($report->fresh()->handled_at);
    }

    public function test_resolving_with_block_reportable_blocks_the_product(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE]);
        $report = Report::create([
            'reporter_id' => User::factory()->create()->id,
            'reportable_type' => 'product',
            'reportable_id' => $product->id,
            'reason' => 'faux_produit',
            'status' => Report::STATUS_PENDING,
        ]);

        $this->authenticateAs($moderator);
        $response = $this->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => 'action_taken',
            'resolution_note' => 'Produit confirmé frauduleux',
            'block_reportable' => true,
        ]);

        $response->assertOk();
        $this->assertSame(Product::STATUS_BLOCKED, $product->fresh()->status);
    }
}
