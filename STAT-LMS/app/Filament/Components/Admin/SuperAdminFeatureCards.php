<?php

namespace App\Filament\Components\Admin;

class SuperAdminFeatureCards
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
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Catalog Management</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Add, edit, and remove research material catalog entries.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/rr-materials" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375V9.375m0 10.375a3.375 3.375 0 003.375-3.375V11.25"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Copy Tracking</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Manage physical and digital copies and their availability.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/material-access-events" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.12.08C4.919 4.339 3.5 5.674 3.5 8.25v10.31c0 1.224.813 2.303 1.99 2.608 1.104.282 2.175.354 3.21.188 1.086-.172 2.127-.575 2.902-1.121a.75.75 0 00.897-1.15 2.262 2.262 0 01-.498-.338c-.39-.337-.706-.728-.928-1.155a3.404 3.404 0 01-.36-.935c-.067-.364-.103-.739-.103-1.123V8.25a.75.75 0 01.75-.75h.75c1.248 0 2.381.514 3.197 1.342l1.327 1.371h-2.443a.75.75 0 00-.651 1.163l1.01 1.744a.75.75 0 00.651.379h2.468c.6 0 1.174-.138 1.7-.386.31-.13.6-.29.865-.476l.012-.008a3.5 3.5 0 001.036-2.982 3.507 3.507 0 00-3.5-3.45h-.664m-1.395 2.807c-.42.38-.986.604-1.614.604h-1.29a.75.75 0 01-.75-.75V8.25a.75.75 0 01.75-.75h1.29c.628 0 1.194.224 1.614.604a.75.75 0 00.996-1.12 3.015 3.015 0 00-2.61-1.484h-1.29a2.25 2.25 0 00-2.25 2.25v7.5c0 .621.504 1.125 1.125 1.125h1.29c1.014 0 1.932-.358 2.656-.955.6-.503 1.038-1.176 1.241-1.938a.75.75 0 00-.703-.986.77.77 0 00-.75.714z"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Request Approval</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Review and approve or reject borrow and digital access requests.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/users" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.592-2.782m-6.365 1.918a6.375 6.375 0 00.73-7.524 5.25 5.25 0 00-6.058-1.793c-2.269.813-3.534 2.87-3.228 5.236.27 2.08 1.943 3.806 4.188 4.256m10.863 4.764l4.128-4.128m-4.128 4.128V14.5m0 5.25h4.128"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">User Management</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Manage user accounts, assign roles, and enforce bans.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin/repository-change-logs" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Audit Logs</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">View immutable change history across all repository activities.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Analytics Dashboard</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Monitor borrow trends, overdue items, and pending requests.</p>
                        </div>
                    </div>
                </a>

                <a href="/admin" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.385 3.97 9.751 8.999 10.68.625.128 1.26.19 1.901.19 5.052 0 9.353-3.648 10.105-8.59.065-.417.129-.835.129-1.255 0-5.523-4.477-10-10-10s-10 4.477-10 10z"></path></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Full System Override</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Restore force-deleted records, manage all users regardless of privilege level.</p>
                        </div>
                    </div>
                </a>
            </div>
        ';
    }
}
