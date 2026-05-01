<?php

namespace App\Filament\Components\Admin;

use Illuminate\Support\Facades\Blade;

class CommitteeFeatureCards
{
    public static function render(): string
    {
        return Blade::render(self::renderCards());
    }

    private static function renderCards(): string
    {
        return '
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="/admin/rr-material-parents" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-book-open class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Catalog Management</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Add, edit, and remove research material catalog entries.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/rr-materials" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-document-duplicate class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Copy Tracking</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Manage physical and digital copies and their availability.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/material-access-events" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Request Approval</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Review and approve or reject borrow and digital access requests.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/users" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-users class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">User Management</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Manage user accounts, assign roles, and enforce bans.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/repository-change-logs" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Audit Logs</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">View immutable change history across all repository activities.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-chart-bar class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Analytics Dashboard</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Monitor borrow trends, overdue items, and pending requests.</p>
                        </div>
                    </div>
                </a>
            </div>
        ';
    }
}
