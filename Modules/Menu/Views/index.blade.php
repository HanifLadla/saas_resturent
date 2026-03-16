<!DOCTYPE html>
<html lang="en" x-data="menu()" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Menu Management</h1>
            <div class="space-x-2">
                <button @click="showCategoryModal = true" class="bg-green-600 text-white px-4 py-2 rounded-lg">
                    Add Category
                </button>
                <button @click="showProductModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Add Product
                </button>
            </div>
        </div>

        <!-- Categories -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Categories</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <template x-for="category in categories" :key="category.id">
                        <div class="border rounded-lg p-4">
                            <h3 class="font-medium" x-text="category.name"></h3>
                            <p class="text-sm text-gray-600" x-text="category.products_count + ' products'"></p>
                            <div class="mt-2">
                                <button @click="editCategory(category)" class="text-blue-600 text-sm">Edit</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Products</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Name</th>
                                <th class="text-left py-2">Category</th>
                                <th class="text-left py-2">Price</th>
                                <th class="text-left py-2">Stock</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-left py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="product in products" :key="product.id">
                                <tr class="border-b">
                                    <td class="py-2" x-text="product.name"></td>
                                    <td class="py-2" x-text="product.category?.name"></td>
                                    <td class="py-2" x-text="'$' + product.price"></td>
                                    <td class="py-2" x-text="product.stock_quantity || 'N/A'"></td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded-full text-xs"
                                              :class="product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                              x-text="product.is_active ? 'Active' : 'Inactive'"></span>
                                    </td>
                                    <td class="py-2">
                                        <button @click="editProduct(product)" class="text-blue-600 hover:text-blue-800">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function menu() {
            return {
                categories: [],
                products: [],
                showCategoryModal: false,
                showProductModal: false,

                async init() {
                    await this.loadCategories();
                    await this.loadProducts();
                },

                async loadCategories() {
                    const response = await fetch('/menu/categories');
                    const data = await response.json();
                    this.categories = data.data || [];
                },

                async loadProducts() {
                    const response = await fetch('/menu/products');
                    const data = await response.json();
                    this.products = data.data || [];
                },

                editCategory(category) {
                    // Edit category logic
                },

                editProduct(product) {
                    // Edit product logic
                }
            }
        }
    </script>
</body>
</html>