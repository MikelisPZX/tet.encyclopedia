<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <!-- Country header with favorite button -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center">
                        {{ $country->name_common }}
                        @if($country->flag_emoji)
                            <span class="ms-2 text-2xl">{{ $country->flag_emoji }}</span>
                        @endif
                    </h2>
                    <form action="{{ route('favorites.toggle', $country->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-2xl hover:scale-110 transition">
                            @if($country->isFavorite())
                                <span class="text-red-500">♥</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">♡</span>
                            @endif
                        </button>
                    </form>
                </div>

                <!-- Basic details -->
                <div class="p-6 text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            @if($country->flag_url)
                                <div class="mb-6">
                                    <img src="{{ $country->flag_url }}" alt="{{ $country->name_common }} flag" class="max-w-full h-auto rounded-lg shadow">
                                </div>
                            @endif
                            
                            <div class="mb-4">
                                <h3 class="text-lg font-medium">{{ __('Details') }}</h3>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Common Name') }}</div>
                                    <div>{{ $country->name_common }}</div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Official Name') }}</div>
                                    <div>{{ $country->name_official }}</div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Country Code') }}</div>
                                    <div>{{ $country->cca3 }}</div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Population') }}</div>
                                    <div>{{ number_format($country->population) }}</div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Population Rank') }}</div>
                                    <div>{{ $country->population_rank }}</div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Area') }}</div>
                                    <div>{{ number_format($country->area, 2) }} km²</div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            @if($neighbors && $neighbors->count() > 0)
                                <div class="mb-6">
                                    <h3 class="text-lg font-medium mb-3">{{ __('Neighboring Countries') }}</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($neighbors as $neighbor)
                                            <a href="{{ route('countries.show', $neighbor->cca3) }}" class="flex items-center p-2 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                                @if($neighbor->flag_emoji)
                                                    <span class="mr-2">{{ $neighbor->flag_emoji }}</span>
                                                @endif
                                                <span>{{ $neighbor->name_common }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            @if($country->languages && count($country->languages) > 0)
                                <div>
                                    <h3 class="text-lg font-medium mb-3">{{ __('Languages') }}</h3>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($country->languages as $code => $language)
                                            <a href="{{ route('countries.by-language', $language) }}" class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full text-sm hover:bg-indigo-200 dark:hover:bg-indigo-800 transition">
                                                {{ $language }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:bg-gray-300 dark:focus:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('Back to Home') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout> 