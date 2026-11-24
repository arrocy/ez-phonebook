<?php

namespace Arrocy\Phonebook;

use Illuminate\Support\ServiceProvider;

class PhonebookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/phonebook.php', 'phonebook');
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'phonebook');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->injectSidebarMenu();
    }

    protected function injectSidebarMenu(): void
    {
        $items = config('menu.sidebar.workspace.items');

        if (isset($items)) {
            $items[] = ['label' => 'Phonebooks', 'icon' => 'fa-solid fa-cubes', 'route' => 'phonebook.index'];
            config(['menu.sidebar.workspace.items' => $items]);
        }
    }
}