<?php

namespace App\Filament\Components\User;

class StudentFeatureCards
{
    public static function render(): string
    {
        return self::renderCards();
    }

    private static function renderCards(): string
    {
        return '
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="/app/user/catalogs" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Browse Catalog</p>
                            <p class="mt-0.5 text-xs text-gray-500">Search and filter research materials by title, author, type, format, and SDG tags.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user/catalogs" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Digital Access</p>
                            <p class="mt-0.5 text-xs text-gray-500">Request digital copy access to view materials online.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user/catalogs" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Borrow Materials</p>
                            <p class="mt-0.5 text-xs text-gray-500">Submit borrow requests for physical copies from the reading room.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user/requests" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.12.08C6.45 5.021 5.604 5.984 5.46 7.119 5.394 7.65 5.388 8.187 5.39 8.25V18a2.25 2.25 0 0 0 2.25 2.25h.75m3-.75h4.5a.75.75 0 0 0 .75-.75V15a.75.75 0 0 0-.75-.75h-4.5a.75.75 0 0 0-.75.75v3.75c0 .415.336.75.75.75Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Track Requests</p>
                            <p class="mt-0.5 text-xs text-gray-500">View your pending and completed requests with real-time updates.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user/requests" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Cancel Requests</p>
                            <p class="mt-0.5 text-xs text-gray-500">Cancel pending requests before approval.</p>
                        </div>
                    </div>
                </a>

                <a href="/app/user-profile" class="block">
                    <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 cursor-pointer transition">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Account Profile</p>
                            <p class="mt-0.5 text-xs text-gray-500">Update your password and personal account information.</p>
                        </div>
                    </div>
                </a>
            </div>
        ';
    }
}
