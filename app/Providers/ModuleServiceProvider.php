<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class ModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerModules();
    }

    public function boot()
    {
        $this->loadModuleRoutes();
        $this->loadModuleViews();
    }

    private function registerModules()
    {
        $modules = [
            'Dashboard', 'POS', 'Inventory', 'Menu', 'Customers', 
            'Staff', 'Suppliers', 'Reports', 'Subscriptions', 
            'Notifications', 'Accounting', 'KitchenDisplay', 'API'
        ];

        foreach ($modules as $module) {
            $this->app->bind(
                "Modules\\{$module}\\Services\\{$module}Service",
                "Modules\\{$module}\\Services\\{$module}Service"
            );
        }
    }

    private function loadModuleRoutes()
    {
        $modulesPath = base_path('Modules');
        
        if (File::exists($modulesPath)) {
            $modules = File::directories($modulesPath);
            
            foreach ($modules as $module) {
                $routeFile = $module . '/routes/web.php';
                if (File::exists($routeFile)) {
                    $this->loadRoutesFrom($routeFile);
                }
            }
        }
    }

    private function loadModuleViews()
    {
        $modulesPath = base_path('Modules');
        
        if (File::exists($modulesPath)) {
            $modules = File::directories($modulesPath);
            
            foreach ($modules as $module) {
                $viewsPath = $module . '/Views';
                if (File::exists($viewsPath)) {
                    $moduleName = strtolower(basename($module));
                    $this->loadViewsFrom($viewsPath, $moduleName);
                }
            }
        }
    }
}