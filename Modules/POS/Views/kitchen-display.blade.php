<!DOCTYPE html>
<html lang="en" x-data="kitchenDisplay()" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .order-card {
            transition: all 0.3s ease;
        }
        .order-card.urgent {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        .priority-normal { @apply bg-green-50 border-green-200; }
        .priority-medium { @apply bg-yellow-50 border-yellow-200; }
        .priority-high { @apply bg-orange-50 border-orange-200; }
        .priority-urgent { @apply bg-red-50 border-red-200; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b px-6 py-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Kitchen Display System</h1>
                <p class="text-gray-600">{{ auth()->user()->branch->name }} - Real-time Order Management</p>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Station Filter -->
                <select x-model="selectedStation" @change="loadOrders()" class="border rounded-lg px-3 py-2">
                    <option value="">All Stations</option>
                    <option value="1">Grill Station</option>
                    <option value="2">Fry Station</option>
                    <option value="3">Bar Station</option>
                    <option value="4">Dessert Station</option>
                </select>
                
                <!-- Statistics -->
                <div class="flex space-x-4 text-sm">
                    <div class="bg-yellow-100 px-3 py-2 rounded-lg">
                        <span class="font-semibold text-yellow-800">Pending: </span>
                        <span x-text="statistics.pending_count" class="font-bold"></span>
                    </div>
                    <div class="bg-blue-100 px-3 py-2 rounded-lg">
                        <span class="font-semibold text-blue-800">Preparing: </span>
                        <span x-text="statistics.preparing_count" class="font-bold"></span>
                    </div>
                    <div class="bg-green-100 px-3 py-2 rounded-lg">
                        <span class="font-semibold text-green-800">Avg Time: </span>
                        <span x-text="statistics.average_time + ' min'" class="font-bold"></span>
                    </div>
                </div>
                
                <!-- Auto Refresh -->
                <div class="flex items-center space-x-2">
                    <i class="fas fa-sync-alt text-gray-500" :class="{ 'animate-spin': isRefreshing }"></i>
                    <span class="text-sm text-gray-600">Auto-refresh: <span x-text="refreshCountdown"></span>s</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Grid -->
    <div class="p-6">
        <div x-show="orders.length === 0" class="text-center py-12">
            <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-500 mb-2">No Active Orders</h3>
            <p class="text-gray-400">All orders are completed or no new orders received</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <template x-for="order in orders" :key="order.id">
                <div 
                    class="order-card bg-white rounded-xl shadow-lg border-2 overflow-hidden"
                    :class="'priority-' + order.priority + (order.priority === 'urgent' ? ' urgent' : '')">
                    
                    <!-- Order Header -->
                    <div class="p-4 border-b bg-gray-50">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-bold text-lg text-gray-800" x-text="order.order_number"></h3>
                                <p class="text-sm text-gray-600 capitalize" x-text="order.order_type.replace('_', ' ')"></p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500" x-text="order.station"></div>
                                <div x-show="order.table_number" class="text-sm font-medium text-blue-600">
                                    Table <span x-text="order.table_number"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Timer -->
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-clock text-gray-400"></i>
                                <span class="text-sm font-medium" 
                                      :class="order.elapsed_time > order.estimated_time ? 'text-red-600' : 'text-gray-600'"
                                      x-text="order.elapsed_time + ' / ' + order.estimated_time + ' min'"></span>
                            </div>
                            <div class="text-xs px-2 py-1 rounded-full font-medium"
                                 :class="{
                                     'bg-yellow-100 text-yellow-800': order.status === 'pending',
                                     'bg-blue-100 text-blue-800': order.status === 'preparing',
                                     'bg-green-100 text-green-800': order.status === 'ready'
                                 }"
                                 x-text="order.status.charAt(0).toUpperCase() + order.status.slice(1)">
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="p-4 space-y-3 max-h-64 overflow-y-auto">
                        <template x-for="item in order.items" :key="item.id">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800" x-text="item.product_name"></h4>
                                    <div x-show="item.variants && item.variants.length > 0" class="text-xs text-gray-500 mt-1">
                                        <template x-for="variant in item.variants" :key="variant.name">
                                            <span x-text="variant.name + ': ' + variant.value" class="mr-2"></span>
                                        </template>
                                    </div>
                                    <div x-show="item.modifiers && item.modifiers.length > 0" class="text-xs text-blue-600 mt-1">
                                        <template x-for="modifier in item.modifiers" :key="modifier.name">
                                            <span x-text="'+ ' + modifier.name" class="mr-2"></span>
                                        </template>
                                    </div>
                                    <div x-show="item.special_instructions" class="text-xs text-orange-600 mt-1 font-medium">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <span x-text="item.special_instructions"></span>
                                    </div>
                                </div>
                                <div class="text-right ml-3">
                                    <span class="text-lg font-bold text-gray-800" x-text="'×' + item.quantity"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Special Instructions -->
                    <div x-show="order.special_instructions" class="px-4 py-2 bg-orange-50 border-t">
                        <div class="flex items-start space-x-2">
                            <i class="fas fa-sticky-note text-orange-500 mt-1"></i>
                            <div>
                                <p class="text-xs font-medium text-orange-700 mb-1">Special Instructions:</p>
                                <p class="text-sm text-orange-800" x-text="order.special_instructions"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="p-4 border-t bg-gray-50">
                        <div x-show="order.status === 'pending'" class="space-y-2">
                            <button 
                                @click="updateOrderStatus(order.id, 'preparing')"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                <i class="fas fa-play mr-2"></i>Start Preparing
                            </button>
                        </div>
                        
                        <div x-show="order.status === 'preparing'" class="space-y-2">
                            <button 
                                @click="updateOrderStatus(order.id, 'ready')"
                                class="w-full bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors">
                                <i class="fas fa-check mr-2"></i>Mark Ready
                            </button>
                            <button 
                                @click="updateOrderStatus(order.id, 'pending')"
                                class="w-full bg-gray-500 text-white py-1 px-4 rounded-lg text-sm hover:bg-gray-600 transition-colors">
                                <i class="fas fa-pause mr-2"></i>Hold
                            </button>
                        </div>
                        
                        <div x-show="order.status === 'ready'" class="text-center">
                            <div class="text-green-600 font-medium">
                                <i class="fas fa-check-circle mr-2"></i>Ready for Pickup
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Sound Alert -->
    <audio x-ref="alertSound" preload="auto">
        <source src="/sounds/kitchen-alert.mp3" type="audio/mpeg">
    </audio>

    <script>
        function kitchenDisplay() {
            return {
                orders: [],
                statistics: {
                    pending_count: 0,
                    preparing_count: 0,
                    average_time: 0
                },
                selectedStation: '',
                isRefreshing: false,
                refreshCountdown: 30,
                refreshInterval: null,
                countdownInterval: null,

                init() {
                    this.loadOrders();
                    this.startAutoRefresh();
                    this.connectWebSocket();
                },

                async loadOrders() {
                    this.isRefreshing = true;
                    
                    try {
                        const url = `/kitchen/display?station=${this.selectedStation}`;
                        const response = await fetch(url);
                        const data = await response.json();
                        
                        this.orders = data.orders || [];
                        this.statistics = data.statistics || this.statistics;
                        
                        // Play sound for new urgent orders
                        this.checkForUrgentOrders();
                        
                    } catch (error) {
                        console.error('Failed to load orders:', error);
                    } finally {
                        this.isRefreshing = false;
                    }
                },

                async updateOrderStatus(orderId, status) {
                    try {
                        const response = await fetch(`/kitchen/orders/${orderId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ status })
                        });

                        const result = await response.json();
                        
                        if (result.success) {
                            this.loadOrders(); // Refresh the display
                        } else {
                            alert('Error updating order status: ' + result.message);
                        }
                    } catch (error) {
                        alert('Network error occurred');
                    }
                },

                startAutoRefresh() {
                    this.refreshInterval = setInterval(() => {
                        this.loadOrders();
                        this.refreshCountdown = 30;
                    }, 30000);

                    this.countdownInterval = setInterval(() => {
                        this.refreshCountdown--;
                        if (this.refreshCountdown <= 0) {
                            this.refreshCountdown = 30;
                        }
                    }, 1000);
                },

                connectWebSocket() {
                    // WebSocket connection for real-time updates
                    if (window.Echo) {
                        window.Echo.channel('kitchen-orders')
                            .listen('NewKitchenOrder', (e) => {
                                this.loadOrders();
                                this.playAlert();
                            })
                            .listen('KitchenOrderStatusUpdated', (e) => {
                                this.loadOrders();
                            });
                    }
                },

                checkForUrgentOrders() {
                    const urgentOrders = this.orders.filter(order => order.priority === 'urgent');
                    if (urgentOrders.length > 0) {
                        this.playAlert();
                    }
                },

                playAlert() {
                    if (this.$refs.alertSound) {
                        this.$refs.alertSound.play().catch(() => {
                            // Handle autoplay restrictions
                        });
                    }
                },

                destroy() {
                    if (this.refreshInterval) clearInterval(this.refreshInterval);
                    if (this.countdownInterval) clearInterval(this.countdownInterval);
                }
            }
        }
    </script>
</body>
</html>