<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Countries Speaking :language', ['language' => $language]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($countries->count() > 0)
                        @if(isset($total_count))
                        <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Found {{ $total_count }} countries where <strong>{{ $language }}</strong> is spoken.
                                Showing {{ $countries->count() }} countries per page.
                            </p>
                        </div>
                        @endif
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($countries as $country)
                                <a href="{{ route('countries.show', $country->cca3) }}" class="block p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                    <div class="flex items-center">
                                        @if($country->flag_emoji)
                                            <span class="text-2xl mr-3">{{ $country->flag_emoji }}</span>
                                        @endif
                                        <div>
                                            <h3 class="font-medium">{{ $country->name_common }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($country->population) }} people</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        
                        <!-- Pagination Links -->
                        <div class="mt-6">
                            {{ $countries->links() }}
                        </div>
                    @else
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-md">
                            {{ __('No countries found for this language.') }}
                        </div>
                        
                        @if(isset($debug))
                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="font-semibold mb-2">Debug Information</h3>
                            <div class="text-sm">
                                <p><strong>Search Term:</strong> "{{ $debug['language_search_term'] }}"</p>
                                <p><strong>Countries Found:</strong> {{ $debug['found_countries_count'] }}</p>
                                
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-blue-600">Language Examples (first 3 countries)</summary>
                                    <pre class="mt-2 p-2 bg-gray-200 dark:bg-gray-800 rounded text-xs overflow-auto">{{ json_encode($debug['language_examples'], JSON_PRETTY_PRINT) }}</pre>
                                </details>
                                
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-blue-600">All Available Languages</summary>
                                    <div class="mt-2 p-2 bg-gray-200 dark:bg-gray-800 rounded text-xs max-h-80 overflow-auto">
                                        <table class="w-full">
                                            <thead>
                                                <tr>
                                                    <th class="text-left p-1">Language Name</th>
                                                    <th class="text-left p-1">Language Code</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($debug['all_available_languages'] as $name => $code)
                                                <tr>
                                                    <td class="p-1">{{ $name }}</td>
                                                    <td class="p-1">{{ $code }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </div>
                        </div>
                        @endif
                    @endif
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