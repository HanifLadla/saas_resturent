<!DOCTYPE html>
<html lang="en" x-data="staff()" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Staff Management</h1>
            <button @click="showAddModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                Add Staff
            </button>
        </div>

        <!-- Staff Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Name</th>
                                <th class="text-left py-2">Email</th>
                                <th class="text-left py-2">Role</th>
                                <th class="text-left py-2">Branch</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-left py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="user in staff" :key="user.id">
                                <tr class="border-b">
                                    <td class="py-2" x-text="user.name"></td>
                                    <td class="py-2" x-text="user.email"></td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800"
                                              x-text="user.role.replace('_', ' ')"></span>
                                    </td>
                                    <td class="py-2" x-text="user.branch?.name || 'All Branches'"></td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded-full text-xs"
                                              :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                              x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                                    </td>
                                    <td class="py-2">
                                        <button @click="editUser(user)" class="text-blue-600 hover:text-blue-800 mr-2">
                                            Edit
                                        </button>
                                        <button @click="toggleStatus(user)" 
                                                :class="user.is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'"
                                                x-text="user.is_active ? 'Deactivate' : 'Activate'">
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
        function staff() {
            return {
                staff: [],
                showAddModal: false,

                async init() {
                    await this.loadStaff();
                },

                async loadStaff() {
                    const response = await fetch('/staff');
                    const data = await response.json();
                    this.staff = data.staff.data || [];
                },

                editUser(user) {
                    // Edit user logic
                },

                async toggleStatus(user) {
                    const response = await fetch(`/staff/${user.id}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ is_active: !user.is_active })
                    });

                    if (response.ok) {
                        user.is_active = !user.is_active;
                    }
                }
            }
        }
    </script>
</body>
</html>