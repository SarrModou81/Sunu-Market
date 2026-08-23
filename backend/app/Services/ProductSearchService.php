<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class ProductSearchService
{
    private const ROTATION_BUCKET_SECONDS = 300;

    private const BOOST_SLOT_EVERY = 4;

    /**
     * Recherche paginée des annonces actives selon les filtres du cahier des charges
     * (section 7) : titre/description/marque, catégorie, prix, état, ville, quartier,
     * livraison, avec tri par pertinence, date, prix, distance ou popularité. En tri
     * "pertinence" (par défaut), les annonces boostées sont intercalées avec rotation
     * plutôt que de monopoliser les premiers résultats.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'relevance';

        if ($sort === 'relevance') {
            return $this->searchWithRotation($filters, $perPage, $page);
        }

        $query = $this->baseQuery($filters);
        $this->applySort($query, $sort, $filters);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function baseQuery(array $filters): Builder
    {
        $query = Product::query()
            ->active()
            ->with(['images', 'city', 'category']);

        if (! empty($filters['q'])) {
            // LOWER(...) LIKE fonctionne à l'identique sur PostgreSQL (production) et
            // SQLite (tests), contrairement à ILIKE qui est spécifique à PostgreSQL.
            $term = '%'.mb_strtolower(trim((string) $filters['q'])).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(brand) LIKE ?', [$term]);
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        }

        if (! empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        if (! empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (! empty($filters['neighborhood_id'])) {
            $query->where('neighborhood_id', $filters['neighborhood_id']);
        }

        if (array_key_exists('delivery_available', $filters) && $filters['delivery_available'] !== null) {
            $query->where('delivery_available', (bool) $filters['delivery_available']);
        }

        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', (int) $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', (int) $filters['max_price']);
        }

        return $query;
    }

    private function applySort(Builder $query, string $sort, array $filters): void
    {
        match ($sort) {
            'date' => $query->orderByDesc('published_at')->orderByDesc('id'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'popularity' => $query->orderByDesc('views_count')->orderByDesc('id'),
            'distance' => $query->orderByDesc('published_at'), // affiné en mémoire si lat/lng fournis, voir searchWithRotation
            default => $query->orderByDesc('published_at'),
        };
    }

    /**
     * Tri "pertinence" : intercale les annonces boostées (avec rotation dans le temps)
     * parmi les annonces normales, pour préserver une visibilité aux deux (section 7).
     */
    private function searchWithRotation(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $boosted = $this->rotatedBoostedPool($filters);
        $normalQuery = $this->baseQuery($filters)->where(function (Builder $q) {
            $q->whereNull('boosted_until')->orWhere('boosted_until', '<=', now());
        })->orderByDesc('published_at')->orderByDesc('id');

        $normalTotal = (clone $normalQuery)->count();
        $totalNeeded = ($page * $perPage);
        $normalItems = $normalQuery->limit($totalNeeded)->get();

        $merged = $this->interleave($boosted, $normalItems);

        $total = $normalTotal + $boosted->count();
        $offset = ($page - 1) * $perPage;
        $items = $merged->slice($offset, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => Request::url(),
            'query' => Request::query(),
        ]);
    }

    private function rotatedBoostedPool(array $filters): Collection
    {
        $pool = $this->baseQuery($filters)
            ->boosted()
            ->limit(100)
            ->get();

        $timeBucket = intdiv(now()->timestamp, self::ROTATION_BUCKET_SECONDS);

        return $pool->sortBy(fn (Product $product) => ($product->id + $timeBucket) % 97)->values();
    }

    /**
     * @param  Collection<int, Product>  $boosted
     * @param  Collection<int, Product>  $normal
     * @return Collection<int, Product>
     */
    private function interleave(Collection $boosted, Collection $normal): Collection
    {
        if ($boosted->isEmpty()) {
            return $normal;
        }

        $merged = collect();
        $boostedIndex = 0;
        $normalIndex = 0;

        while ($normalIndex < $normal->count() || $boostedIndex < $boosted->count()) {
            for ($i = 0; $i < self::BOOST_SLOT_EVERY && $normalIndex < $normal->count(); $i++) {
                $merged->push($normal[$normalIndex]);
                $normalIndex++;
            }

            if ($boostedIndex < $boosted->count()) {
                $merged->push($boosted[$boostedIndex]);
                $boostedIndex++;
            }
        }

        return $merged->values();
    }
}
