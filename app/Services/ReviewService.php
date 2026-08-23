<?php

namespace App\Services;

use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewNotification;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * @param  array{rating: int, comment?: ?string, product_id?: ?int}  $data
     */
    public function create(User $author, User $seller, array $data): Review
    {
        return DB::transaction(function () use ($author, $seller, $data) {
            $review = Review::create([
                'author_id' => $author->id,
                'seller_id' => $seller->id,
                'product_id' => $data['product_id'] ?? null,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => Review::STATUS_VISIBLE,
            ]);

            $this->recomputeRating($seller);
            $seller->notify(new NewReviewNotification($review));

            return $review;
        });
    }

    private function recomputeRating(User $seller): void
    {
        $stats = Review::where('seller_id', $seller->id)
            ->where('status', Review::STATUS_VISIBLE)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        $seller->sellerProfile()->update([
            'rating_average' => round((float) $stats->avg_rating, 2),
            'rating_count' => (int) $stats->total,
        ]);
    }
}
