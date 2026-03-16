<?php

namespace Modules\Menu\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        $stats = [
            'total_categories' => Category::where('restaurant_id', $restaurant->id)->count(),
            'total_products' => Product::where('restaurant_id', $restaurant->id)->count(),
            'active_products' => Product::where('restaurant_id', $restaurant->id)->where('is_active', true)->count(),
            'out_of_stock' => Product::where('restaurant_id', $restaurant->id)
                ->where('track_inventory', true)->where('stock_quantity', '<=', 0)->count()
        ];

        return view('menu.index', compact('stats'));
    }

    public function categories(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $categories = Category::where('restaurant_id', $restaurant->id)
            ->withCount('products')
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('sort_order')
            ->paginate(20);

        return response()->json($categories);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant->id;
        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($validated);
        return response()->json(['success' => true, 'category' => $category]);
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($validated);
        return response()->json(['success' => true, 'category' => $category]);
    }

    public function products(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $query = Product::where('restaurant_id', $restaurant->id)->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            match($request->status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'low_stock' => $query->where('track_inventory', true)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_alert'),
                'out_of_stock' => $query->where('track_inventory', true)
                    ->where('stock_quantity', '<=', 0)
            };
        }

        $products = $query->paginate(20);
        return response()->json($products);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'required|string|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'variants' => 'nullable|array',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'kitchen_stations' => 'nullable|array',
            'preparation_time' => 'nullable|integer|min:1'
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant->id;
        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);
        return response()->json(['success' => true, 'product' => $product->load('category')]);
    }

    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'variants' => 'nullable|array',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'kitchen_stations' => 'nullable|array',
            'preparation_time' => 'nullable|integer|min:1',
            'is_active' => 'boolean'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);
        return response()->json(['success' => true, 'product' => $product->load('category')]);
    }

    public function deleteProduct(Product $product)
    {
        // Check if product has orders
        if ($product->orderItems()->exists()) {
            return response()->json([
                'success' => false, 
                'message' => 'Cannot delete product with existing orders'
            ], 422);
        }

        $product->delete();
        return response()->json(['success' => true]);
    }

    public function bulkUpdatePrices(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.price' => 'required|numeric|min:0'
        ]);

        foreach ($validated['products'] as $productData) {
            Product::where('id', $productData['id'])
                ->where('restaurant_id', auth()->user()->restaurant->id)
                ->update(['price' => $productData['price']]);
        }

        return response()->json(['success' => true]);
    }

    public function duplicateProduct(Product $product)
    {
        $newProduct = $product->replicate();
        $newProduct->name = $product->name . ' (Copy)';
        $newProduct->slug = Str::slug($newProduct->name);
        $newProduct->sku = $product->sku . '-copy-' . time();
        $newProduct->save();

        return response()->json(['success' => true, 'product' => $newProduct->load('category')]);
    }
}