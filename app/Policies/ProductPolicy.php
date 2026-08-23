<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || $user->isModerator();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || $user->isModerator();
    }

    public function manageImages(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || $user->isModerator();
    }
}
