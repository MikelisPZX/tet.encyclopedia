<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Search Section -->
      <div class="bg-white dark:bg-gray-800 overflow-visible shadow-sm sm:rounded-lg mb-6">
        <div class="p-6 text-gray-900 dark:text-gray-100">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">Search Countries</h3>
          </div>
          
          <div class="mb-6 relative">
            <div class="flex">
              <input 
                type="text" 
                v-model="searchQuery"
                placeholder="Search by name or translation (try 'Эстония', 'Deutschland', 'España')..." 
                class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600" 
                @input="debounceSearch"
              >
            </div>
            
            <!-- Live search results dropdown -->
            <div id="live-search-results" v-if="countriesStore.searchResults.length > 0 && showDropdown" class="mt-2">
              <div class="bg-white dark:bg-gray-700 rounded-md shadow-md absolute z-50 w-full left-0 right-0">
                <div id="live-results-content" class="p-2 max-h-60 overflow-y-auto">
                  <div 
                    v-for="country in countriesStore.searchResults" 
                    :key="country.id"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-0"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1" @click="goToCountry(country.cca3)">
                        <span v-if="country.flag_emoji" class="text-xl mr-2 flex-shrink-0">{{ country.flag_emoji }}</span>
                        <div class="flex-1 min-w-0">
                          <div class="font-medium">{{ country.name_common }}</div>
                        </div>
                      </div>
                      <button @click.stop.prevent="toggleFavorite(country.cca3)" class="text-red-500 hover:text-red-700 ml-2 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" :fill="country.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" :stroke-width="country.is_favorite ? '0' : '1.5'">
                          <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="searchQuery && countriesStore.searchResults.length === 0">
            <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-md">
              No countries found matching your search.
            </div>
          </div>
        </div>
      </div>

      <!-- Favorites Section -->
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
          <h3 class="text-lg font-medium mb-4">Favorite Countries</h3>

          <div v-if="countriesStore.favorites.length > 0">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div 
                v-for="country in countriesStore.favorites" 
                :key="country.id || country.cca3 || country.country_code"
                class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow-sm hover:shadow-md transition"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <span v-if="country.flag_emoji" class="text-2xl">{{ country.flag_emoji }}</span>
                    <router-link 
                      :to="{ name: 'country-detail', params: { code: country.cca3 || country.country_code }}" 
                      class="text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline font-medium"
                    >
                      {{ country.name_common || country.country_name }}
                    </router-link>
                  </div>
                  <button @click.prevent.stop="toggleFavorite(country.cca3 || country.country_code)" type="button" class="text-red-500 hover:text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div v-else>
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md text-gray-800 dark:text-gray-100">
              You don't have any favorite countries yet.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCountriesStore } from '../stores/countries';

const router = useRouter();
const countriesStore = useCountriesStore();
const searchQuery = ref('');
const showDropdown = ref(true);
let debounceTimeout = null;

// Fetch favorites when component is mounted
onMounted(() => {
  countriesStore.fetchFavorites();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

// Handle clicking outside the search results to close dropdown
const handleClickOutside = (event) => {
  const searchInput = document.querySelector('input[type="text"]');
  const resultsContainer = document.getElementById('live-search-results');
  
  if (resultsContainer && 
      !resultsContainer.contains(event.target) && 
      searchInput && 
      !searchInput.contains(event.target)) {
    showDropdown.value = false;
  }
};

// Debounce search to prevent too many API calls
const debounceSearch = () => {
  showDropdown.value = true;
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    countriesStore.searchCountries(searchQuery.value);
  }, 300);
};

// Toggle favorite status
const toggleFavorite = async (countryCode) => {
  try {
    await countriesStore.toggleFavorite(countryCode);
  } catch (error) {
    console.error('Error toggling favorite:', error);
  }
};

// Navigate to country detail page
const goToCountry = (code) => {
  showDropdown.value = false;
  router.push({ name: 'country-detail', params: { code } });
};
</script>

<style scoped>
</style> 