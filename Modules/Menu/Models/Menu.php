<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Product;

class Menu extends Model
{
    public static function getMenuStructure($restaurantId)
    {
        return Category::where('restaurant_id', $restaurantId)
            ->with(['products' => function($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
    
    public static function getProductsByCategory($restaurantId, $categoryId)
    {
        return Product::where('restaurant_id', $restaurantId)
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->get();
    }
}