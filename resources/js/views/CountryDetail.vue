<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div v-if="loading" class="flex justify-center items-center h-40">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
      </div>
      
      <div v-else-if="country">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-2xl font-bold flex items-center">
                <span v-if="country.flag_emoji" class="text-3xl mr-3">{{ country.flag_emoji }}</span>
                {{ country.name_common }}
              </h2>
              <button 
                @click.prevent.stop="toggleFavorite(country.cca3)" 
                type="button"
                class="text-red-500 hover:text-red-700"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" :stroke-width="isFavorite ? '0' : '1.5'">
                  <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div v-if="country.flag_url" class="mb-6">
                  <img :src="country.flag_url" :alt="country.name_common + ' flag'" class="max-w-full h-auto rounded-lg shadow">
                </div>
                
                <div class="mb-4">
                  <h3 class="text-lg font-medium">Details</h3>
                  <div class="mt-3 grid grid-cols-2 gap-2">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Common Name</div>
                    <div>{{ country.name_common }}</div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">Official Name</div>
                    <div>{{ country.name_official }}</div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">Country Code</div>
                    <div>{{ country.cca3 }}</div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">Population</div>
                    <div>{{ formatNumber(country.population) }}</div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">Population Rank</div>
                    <div v-if="country.population_rank && country.population_rank !== 'Unknown'">
                      #{{ country.population_rank }}
                    </div>
                    <div v-else>
                      Data unavailable
                    </div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">Area</div>
                    <div>{{ formatNumberWithDecimals(country.area) }} km²</div>
                  </div>
                </div>
              </div>
              
              <div>
                <div v-if="Object.keys(country.languages || {}).length > 0" class="mb-6">
                  <h3 class="text-lg font-medium mb-2">Languages</h3>
                  <div class="flex flex-wrap gap-2">
                    <router-link 
                      v-for="(name, code) in country.languages" 
                      :key="code"
                      :to="{ name: 'countries-by-language', params: { language: name }}" 
                      class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-full text-sm hover:bg-indigo-200 dark:hover:bg-indigo-800 transition"
                    >
                      {{ name }}
                    </router-link>
                  </div>
                </div>
                
                <div v-if="neighbors && neighbors.length > 0" class="mb-6">
                  <h3 class="text-lg font-medium mb-3">Neighboring Countries</h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <router-link 
                      v-for="neighbor in neighbors" 
                      :key="neighbor.cca3 || neighbor['cca3']"
                      :to="{ name: 'country-detail', params: { code: neighbor.cca3 || neighbor['cca3'] }}" 
                      class="flex items-center p-2 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                    >
                      <span v-if="neighbor.flag_emoji || neighbor['flag_emoji'] || neighbor['flag']" class="mr-2">
                        {{ neighbor.flag_emoji || neighbor['flag_emoji'] || neighbor['flag'] }}
                      </span>
                      <span>{{ neighbor.name_common || 
                        (neighbor['name'] && neighbor['name']['common']) || 
                        (neighbor['name_common']) || 
                        'Unknown' }}</span>
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-4">
          <router-link to="/" class="text-indigo-600 dark:text-indigo-400 hover:underline">
            &larr; Back to Countries List
          </router-link>
        </div>
      </div>
      
      <div v-else class="bg-red-50 dark:bg-red-900/30 p-6 rounded-md">
        <p class="text-red-700 dark:text-red-300">Country not found.</p>
        <router-link to="/" class="text-indigo-600 dark:text-indigo-400 hover:underline mt-4 inline-block">
          &larr; Back to Countries List
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCountriesStore } from '../stores/countries';

const props = defineProps({
  code: {
    type: String,
    required: true
  }
});

const route = useRoute();
const router = useRouter();
const countriesStore = useCountriesStore();
const country = ref(null);
const neighbors = ref([]);
const loading = ref(true);
const isFavorite = ref(false);

// Fetch country data when the component is mounted or code prop changes
onMounted(async () => {
  await fetchCountryData();
});

// Watch for route changes to reload data when navigating between countries
watch(
  () => props.code,
  async (newCode) => {
    if (newCode) {
      loading.value = true;
      await fetchCountryData();
    }
  }
);

// Fetch country data from API
async function fetchCountryData() {
  try {
    loading.value = true;
    
    // Use axios for more consistent handling
    const countryData = await countriesStore.getCountryByCode(props.code);
    console.log('Country data from API:', countryData);
    
    if (!countryData || !countryData.country) {
      throw new Error('Country not found');
    }
    
    // Assign data and update favorite status
    country.value = countryData.country;
    neighbors.value = countryData.neighbors || [];
    console.log('Neighbors data:', neighbors.value);
    isFavorite.value = Boolean(country.value.is_favorite);
    
  } catch (error) {
    console.error('Error fetching country:', error);
  } finally {
    loading.value = false;
  }
}

// Toggle favorite status
async function toggleFavorite(countryCode) {
  if (!countryCode) return;
  
  try {
    const result = await countriesStore.toggleFavorite(countryCode);
    console.log('Toggle favorite result:', result);
    
    if (result && result.success) {
      // Only update the favorite status, not the entire country object
      isFavorite.value = Boolean(result.favorited);
      
      // Create a copy of the existing country object and only update the is_favorite property
      if (country.value) {
        country.value = {
          ...country.value,
          is_favorite: Boolean(result.favorited)
        };
      }
    }
  } catch (error) {
    console.error('Error toggling favorite:', error);
  }
}

// Format numbers with commas
function formatNumber(num) {
  if (num === undefined || num === null) return '';
  return new Intl.NumberFormat().format(num);
}

// Format area numbers (no decimal places, with thousands separators)
function formatNumberWithDecimals(num) {
  if (num === undefined || num === null) return '';
  
  // If the number is already formatted with commas (as a string), remove commas first
  if (typeof num === 'string') {
    // Remove any existing commas before parsing
    num = num.replace(/,/g, '');
  }
  
  // Format with thousands separators but no decimal places for large numbers
  return new Intl.NumberFormat().format(parseInt(num));
}
</script> 