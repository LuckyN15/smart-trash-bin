<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('User Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-2xl font-bold mb-4">{{ __('Welcome') }}, {{ Auth::user()->name }}!</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- User Stats -->
                        <div class="bg-blue-50 dark:bg-gray-700 p-6 rounded-lg">
                            <h4 class="text-gray-600 dark:text-gray-300 text-sm font-semibold">{{ __('Role') }}</h4>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ Auth::user()->role }}</p>
                        </div>

                        <div class="bg-indigo-50 dark:bg-gray-700 p-6 rounded-lg">
                            <h4 class="text-gray-600 dark:text-gray-300 text-sm font-semibold">{{ __('Email Verified') }}</h4>
                            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">
                                @if(Auth::user()->email_verified_at)
                                    {{ __('Yes') }}
                                @else
                                    {{ __('No') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="text-lg font-semibold mb-3">{{ __('User Account') }}</h4>
                        <ul class="space-y-2">
                            <li>
                                <a href="{{ route('profile.edit') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ __('Edit Profile') }}
                                </a>
                            </li>
                            <li><a href="#" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View History') }}</a></li>
                            <li><a href="#" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Account Settings') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
