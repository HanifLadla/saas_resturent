<?php

namespace Modules\Menu\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class MenuService
{
    public function createCategory($data)
    {
        return Category::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            'slug' => Str::slug($data['name']),
            ...$data
        ]);
    }

    public function createProduct($data)
    {
        return Product::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            'slug' => Str::slug($data['name']),
            ...$data
        ]);
    }

    public function bulkUpdatePrices($products)
    {
        foreach ($products as $productData) {
            Product::where('id', $productData['id'])
                ->where('restaurant_id', auth()->user()->restaurant->id)
                ->update(['price' => $productData['price']]);
        }
    }
}