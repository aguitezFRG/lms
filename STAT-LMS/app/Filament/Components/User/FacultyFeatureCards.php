<?php

namespace App\Filament\Components\User;

use Illuminate\Support\Facades\Blade;

class FacultyFeatureCards
{
    public static function render(): string
    {
        return Blade::render(self::renderCards());
    }

    private static function renderCards(): string
    {
        return '
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="/app/user/catalogs" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-book-open class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Browse Catalog</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Search and filter research materials by title, author, type, format, date, and SDG tags.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user/catalogs" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-computer-desktop class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Digital Access</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Request digital copy access to view materials online.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user/catalogs" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-building-library class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Borrow Materials</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Submit borrow requests for physical copies from the reading room.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user/requests" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-clipboard-document-list class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Track Requests</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">View all your pending and completed requests with real-time status updates.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user/requests" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-x-circle class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Cancel Requests</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Cancel pending requests before they are approved.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user-profile" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-user-circle class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Account Profile</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Update your password and personal account information.</p>
                        </div>
                    </div>
                </a>
            </div>
        ';
    }
}
