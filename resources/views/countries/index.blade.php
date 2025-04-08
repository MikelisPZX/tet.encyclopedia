<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search Section -->
            <div class="bg-white dark:bg-gray-800 overflow-visible shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium">{{ __('Search Countries') }}</h3>
                    </div>
                    
                    <div class="mb-6 relative">
                        <div class="flex">
                            <input 
                                type="text" 
                                id="live-search"
                                name="search" 
                                value="{{ $search ?? '' }}"
                                placeholder="Search by name or translation..." 
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600" 
                                autocomplete="off"
                            >
                        </div>
                        
                        <!-- Live search results container -->
                        <div id="live-search-results" class="mt-2 hidden">
                            <div class="bg-white dark:bg-gray-700 rounded-md shadow-md absolute z-50 w-full left-0 right-0">
                                <div id="live-results-content" class="p-2 max-h-60 overflow-y-auto">
                                    <!-- Results will be dynamically inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($search && $countries->count() > 0)
                        <div>
                            <h4 class="text-md font-medium mb-2">{{ __('Search Results') }}</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($countries as $country)
                                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow-sm hover:shadow-md transition">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                @if($country->flag_emoji)
                                                    <span class="text-2xl">{{ $country->flag_emoji }}</span>
                                                @endif
                                                <a href="{{ route('countries.show', $country->cca3) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                                    {{ $country->name_common }}
                                                </a>
                                            </div>
                                            <form action="{{ route('favorites.toggle', $country->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-red-500 hover:text-red-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="{{ $country->isFavorite() ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="{{ $country->isFavorite() ? '0' : '1.5' }}">
                                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif($search)
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-md">
                            {{ __('No countries found matching your search.') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Favorites Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">{{ __('Favorite Countries') }}</h3>

                    @if($favorites->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($favorites as $country)
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow-sm hover:shadow-md transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            @if($country->flag_emoji)
                                                <span class="text-2xl">{{ $country->flag_emoji }}</span>
                                            @endif
                                            <a href="{{ route('countries.show', $country->cca3) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                                {{ $country->name_common }}
                                            </a>
                                        </div>
                                        <form action="{{ route('favorites.toggle', $country->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-red-500 hover:text-red-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                            {{ __('You don\'t have any favorite countries yet.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Live Search -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('live-search');
            const resultsContainer = document.getElementById('live-search-results');
            const resultsContent = document.getElementById('live-results-content');
            let debounceTimer;
            let favorites = [];

            // Fetch all favorite country IDs immediately
            fetchFavorites();
            
            // Add global event delegation for all favorite buttons
            document.addEventListener('click', function(e) {
                // Handle favorite button clicks in the main search results and favorites section
                const favoriteBtn = e.target.closest('.text-red-500');
                const form = e.target.closest('form[action*="/favorites/"]');
                
                if (favoriteBtn && form) {
                    e.preventDefault();
                    const countryId = form.action.split('/').pop();
                    
                    // Check if we're in the favorites section
                    const inFavoritesSection = favoriteBtn.closest('.p-6') && favoriteBtn.closest('.p-6').querySelector('h3').textContent.includes('Favorite Countries');
                    if (inFavoritesSection) {
                        // We're in the favorites section, handle immediate UI update
                        updateFavoriteStatus(countryId, false);
                    } else {
                        // We're elsewhere, check the current state
                        const isFavorited = favoriteBtn.querySelector('svg').getAttribute('fill') === 'currentColor';
                        updateFavoriteStatus(countryId, !isFavorited);
                    }
                }
                
                // Hide results when clicking outside search
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.classList.add('hidden');
                }
            });

            // Function to fetch all favorites
            function fetchFavorites() {
                fetch('/favorites', {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    favorites = data.map(favorite => parseInt(favorite.country_id));
                    console.log('Loaded favorites:', favorites);
                })
                .catch(error => {
                    console.error('Error fetching favorites:', error);
                });
            }

            // Add event listener for key input in search box
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(debounceTimer);
                
                if (query.length === 0) {
                    resultsContainer.classList.add('hidden');
                    return;
                }
                
                debounceTimer = setTimeout(() => {
                    fetchDirectApiResults(query);
                }, 300);
            });

            // Function to fetch search results directly from REST Countries API
            function fetchDirectApiResults(query) {
                fetch(`/api/search?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Format API data to match our expected format
                    const formattedData = formatApiResults(data);
                    displaySearchResults(formattedData, false); // false = no favorite info
                })
                .catch(error => {
                    console.error('Error fetching from direct API:', error);
                    resultsContent.innerHTML = `
                        <div class="p-3 text-gray-500 dark:text-gray-400">
                            Error searching countries. Please try again later.
                        </div>
                    `;
                    resultsContainer.classList.remove('hidden');
                });
            }
            
            // Process and format API results to match our expected format
            function formatApiResults(data) {
                if (!Array.isArray(data)) return [];
                
                return data.map(country => {
                    const countryId = country.ccn3 || country.cca3;
                    // Convert API response to our format
                    return {
                        id: countryId, // Use numeric country code (ccn3) as ID for favoriting
                        cca2: country.cca2,
                        cca3: country.cca3,
                        name_common: country.name.common,
                        name_official: country.name.official,
                        flag_emoji: country.flag,
                        // Check if this country is in our favorites (by matching ID)
                        is_favorite: favorites.includes(parseInt(countryId))
                    };
                });
            }
            
            // Display search results in the dropdown
            function displaySearchResults(data, includesFavoriteInfo) {
                // Clear previous results
                resultsContent.innerHTML = '';
                
                if (data.length > 0) {
                    data.forEach(country => {
                        // Create the entire row as clickable (except the favorite button)
                        const resultItem = document.createElement('div');
                        resultItem.className = 'p-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer flex items-center justify-between';
                        resultItem.setAttribute('data-country-code', country.cca3);
                        resultItem.setAttribute('data-country-id', country.id);
                        
                        const leftSide = document.createElement('div');
                        leftSide.className = 'flex items-center flex-1'; // Add flex-1 to make it take up available space
                        
                        if (country.flag_emoji) {
                            const flagSpan = document.createElement('span');
                            flagSpan.className = 'text-xl mr-2';
                            flagSpan.textContent = country.flag_emoji;
                            leftSide.appendChild(flagSpan);
                        }
                        
                        const nameSpan = document.createElement('span');
                        nameSpan.className = 'text-gray-800 dark:text-gray-200';
                        nameSpan.textContent = country.name_common;
                        leftSide.appendChild(nameSpan);
                        
                        const rightSide = document.createElement('div');
                        
                        // Determine if country is a favorite
                        let isFavorite = false;
                        if (includesFavoriteInfo && country.is_favorite) {
                            // Use API-provided value if available
                            isFavorite = country.is_favorite;
                        } else if (country.id && favorites.includes(parseInt(country.id))) {
                            // Otherwise check against our local favorites
                            isFavorite = true;
                        }
                        
                        rightSide.innerHTML = `
                            <button type="button" class="text-red-500 hover:text-red-700 favorite-btn" 
                                data-country-id="${country.id}" 
                                data-country-cca3="${country.cca3}" 
                                data-country-name="${country.name_common}" 
                                data-country-flag="${country.flag_emoji || ''}"
                                data-favorite="${isFavorite ? 'true' : 'false'}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" 
                                    fill="${isFavorite ? 'currentColor' : 'none'}" 
                                    stroke="currentColor" 
                                    stroke-width="${isFavorite ? '0' : '1.5'}">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        `;
                        
                        resultItem.appendChild(leftSide);
                        resultItem.appendChild(rightSide);
                        
                        // Add click event to the entire row (except favorite button)
                        resultItem.addEventListener('click', function(e) {
                            // Make sure we're not clicking the favorite button
                            if (!e.target.closest('.favorite-btn')) {
                                window.location.href = `/countries/${country.cca3}`;
                            }
                        });
                        
                        // Add click event for favorite button
                        const favoriteBtn = rightSide.querySelector('.favorite-btn');
                        favoriteBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            // Immediately update the UI to show toggling effect
                            const currentState = this.getAttribute('data-favorite') === 'true';
                            updateFavoriteStatus(country.id, !currentState);
                        });
                        
                        resultsContent.appendChild(resultItem);
                    });
                    
                    resultsContainer.classList.remove('hidden');
                } else if (query && query.length > 0) {
                    resultsContent.innerHTML = `
                        <div class="p-3 text-gray-500 dark:text-gray-400">
                            No countries found matching "${query}"
                        </div>
                    `;
                    resultsContainer.classList.remove('hidden');
                } else {
                    resultsContainer.classList.add('hidden');
                }
            }
            
            // Function to update favorite status (both UI and server)
            function updateFavoriteStatus(countryId, setFavorite) {
                const countryIdInt = parseInt(countryId);
                
                // Update UI immediately for better user experience
                updateUIForFavoriteStatus(countryIdInt, setFavorite);
                
                // Then send request to server
                fetch(`/favorites/${countryId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // If failed, revert UI changes
                        console.error('Failed to update favorite status');
                        updateUIForFavoriteStatus(countryIdInt, !setFavorite);
                    }
                })
                .catch(error => {
                    console.error('Error toggling favorite:', error);
                    // Revert UI changes on error
                    updateUIForFavoriteStatus(countryIdInt, !setFavorite);
                });
            }
            
            // Update UI elements for favorite status
            function updateUIForFavoriteStatus(countryId, isFavorite) {
                // Update favorites array
                const favoriteIndex = favorites.indexOf(countryId);
                if (isFavorite) {
                    if (favoriteIndex === -1) {
                        favorites.push(countryId);
                    }
                    
                    // If we need to add to favorites section, fetch country data first
                    fetchCountryById(countryId).then(country => {
                        if (country) {
                            addToFavoritesSection(country);
                        } else {
                            // If country data isn't available immediately, add a placeholder
                            const placeholderCountry = {
                                id: countryId,
                                cca3: 'loading',
                                name_common: 'Loading...',
                                flag_emoji: ''
                            };
                            addToFavoritesSection(placeholderCountry);
                            
                            // Then attempt to reload the page after a short delay
                            // This ensures the server has time to create the country record
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    });
                } else {
                    if (favoriteIndex > -1) {
                        favorites.splice(favoriteIndex, 1);
                    }
                    
                    // Remove from favorites section
                    removeFromFavoritesSection(countryId);
                }
                
                // Update all heart icons for this country
                updateAllHeartIcons(countryId, isFavorite);
            }
            
            // Function to fetch country by ID
            function fetchCountryById(countryId) {
                return fetch(`/countries/data/${countryId}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Country not found');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    return data;
                })
                .catch(error => {
                    console.error('Error fetching country data:', error);
                    return null;
                });
            }
            
            // Get the favorites section container
            function getFavoritesSectionContainer() {
                // Look for the section with "Favorite Countries" heading
                const favoritesHeaders = document.querySelectorAll('.p-6 h3');
                for (const header of favoritesHeaders) {
                    if (header.textContent.includes('Favorite Countries')) {
                        return header.parentNode;
                    }
                }
                return null;
            }
            
            // Functions to manage the favorites section
            function addToFavoritesSection(country) {
                const countryId = country.id;
                const countryCca3 = country.cca3;
                const countryName = country.name_common;
                const countryFlag = country.flag_emoji;
                
                // Get the favorites section
                const favoritesContainer = getFavoritesSectionContainer();
                if (!favoritesContainer) {
                    console.error('Could not find favorites section');
                    return;
                }
                
                // If the country is already in favorites, don't add it again
                if (document.querySelector(`[data-country-id="${countryId}"]`)) {
                    return;
                }
                
                // Create the country card
                const countryCard = document.createElement('div');
                countryCard.className = 'bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow-sm hover:shadow-md transition';
                countryCard.setAttribute('data-country-id', countryId);
                
                countryCard.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            ${countryFlag ? `<span class="text-2xl">${countryFlag}</span>` : ''}
                            <a href="/countries/${countryCca3}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                ${countryName}
                            </a>
                        </div>
                        <form action="/favorites/${countryId}" method="POST">
                            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                            <button type="submit" class="text-red-500 hover:text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    </div>
                `;
                
                // Check for the empty message or grid
                const emptyMessage = favoritesContainer.querySelector('.bg-gray-50.dark\\:bg-gray-700.p-4.rounded-md');
                let grid = favoritesContainer.querySelector('.grid');
                
                if (emptyMessage && !grid) {
                    // Replace empty message with grid
                    grid = document.createElement('div');
                    grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4';
                    favoritesContainer.replaceChild(grid, emptyMessage);
                } else if (!grid) {
                    // Create grid if it doesn't exist
                    grid = document.createElement('div');
                    grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4';
                    // Add grid after the heading
                    const heading = favoritesContainer.querySelector('h3');
                    if (heading && heading.nextSibling) {
                        favoritesContainer.insertBefore(grid, heading.nextSibling);
                    } else {
                        favoritesContainer.appendChild(grid);
                    }
                }
                
                // Add the card to the grid
                grid.appendChild(countryCard);
            }
            
            function removeFromFavoritesSection(countryId) {
                // Find the favorite item
                const favoriteItem = document.querySelector(`[data-country-id="${countryId}"]`);
                if (!favoriteItem) return;
                
                // Get the favorites section and grid
                const favoritesContainer = getFavoritesSectionContainer();
                if (!favoritesContainer) return;
                
                const grid = favoritesContainer.querySelector('.grid');
                if (!grid) return;
                
                // Remove the item
                favoriteItem.remove();
                
                // If grid is now empty, replace with empty message
                if (grid.children.length === 0) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.className = 'bg-gray-50 dark:bg-gray-700 p-4 rounded-md';
                    emptyMessage.textContent = 'You don\'t have any favorite countries yet.';
                    favoritesContainer.replaceChild(emptyMessage, grid);
                }
            }
            
            // Function to update all heart icons for a country
            function updateAllHeartIcons(countryId, isFavorite) {
                // Update all SVG icons in the entire page for this country
                const allHeartIcons = document.querySelectorAll(`
                    form[action$="/favorites/${countryId}"] svg,
                    .favorite-btn[data-country-id="${countryId}"] svg,
                    [data-country-id="${countryId}"] .text-red-500 svg
                `);
                
                allHeartIcons.forEach(icon => {
                    if (isFavorite) {
                        icon.setAttribute('fill', 'currentColor');
                        icon.setAttribute('stroke-width', '0');
                    } else {
                        icon.setAttribute('fill', 'none');
                        icon.setAttribute('stroke-width', '1.5');
                    }
                });
                
                // Update all favorite buttons in dropdown
                const favoriteButtons = document.querySelectorAll(`.favorite-btn[data-country-id="${countryId}"]`);
                favoriteButtons.forEach(button => {
                    button.setAttribute('data-favorite', isFavorite ? 'true' : 'false');
                });
            }
        });
    </script>
</x-app-layout> 