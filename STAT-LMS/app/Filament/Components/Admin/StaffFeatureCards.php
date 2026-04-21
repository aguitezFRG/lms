<?php

namespace App\Filament\Components\Admin;

class StaffFeatureCards
{
    public static function render(): string
    {
        return self::renderCards();
    }

    private static function renderCards(): string
    {
        return '
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="/admin/rr-material-parents" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-950 dark:text-success-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Catalog Browsing</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">View all materials in the repository catalog.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/rr-materials" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-950 dark:text-success-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375V9.375m0 10.375a3.375 3.375 0 003.375-3.375V11.25"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Copy Management</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Add and update material copies and availability status.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/material-access-events" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-950 dark:text-success-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H12.75V3.75h-3V0h3v3.75zM8.25 15.75a.75.75 0 00.75.75h6a.75.75 0 000-1.5h-6a.75.75 0 00-.75.75zM3.75 8.25h16.5"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Request Processing</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Review and approve borrow and digital access requests for open materials.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/material-access-events" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-950 dark:text-success-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Overdue Tracking</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Monitor active borrows and flag overdue returns.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-950 dark:text-success-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Dashboard</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">View borrow and visitor activity statistics.</p>
                        </div>
                    </div>
                </a>
            </div>
        ';
    }
}
