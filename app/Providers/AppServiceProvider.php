<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share recent chats with every view that uses the app layout so the sidebar always has data
        View::composer('layouts.app', function ($view) {
            $recentChats = [];

            if (Auth::check()) {
                $recentChats = Auth::user()->conversations()
                    ->select('id', 'title', 'updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->take(20)
                    ->get()
                    ->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])
                    ->toArray();
            }

            $view->with('recentChats', $recentChats);
        });
    }
}
