<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
          <div v-if="loading" class="flex justify-center items-center h-40">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
          </div>
          
          <div v-else>
            <div class="mb-6">
              <h2 class="text-xl font-semibold mb-2">
                Countries speaking {{ language }}
                <span v-if="totalCount" class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">({{ totalCount }} total)</span>
              </h2>
              
              <div class="mt-4">
                <router-link to="/" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                  &larr; Back to Countries List
                </router-link>
              </div>
            </div>
            
            <div v-if="countries.length > 0" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div 
                v-for="country in countries" 
                :key="country.id || country.cca3"
                class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow-sm hover:shadow-md transition"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3 overflow-hidden">
                    <span v-if="country.flag_emoji" class="text-2xl flex-shrink-0">{{ country.flag_emoji }}</span>
                    <router-link 
                      :to="{ name: 'country-detail', params: { code: country.cca3 }}" 
                      class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium truncate"
                    >
                      {{ country.name_common }}
                    </router-link>
                  </div>
                  <button 
                    v-if="country.cca3" 
                    @click="toggleFavorite(country.cca3)" 
                    class="text-red-500 hover:text-red-700 ml-2 flex-shrink-0"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" :fill="country.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" :stroke-width="country.is_favorite ? '0' : '1.5'">
                      <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            
            <div v-else class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-md">
              No countries found for this language.
            </div>
            
            <div v-if="paginationLinks" class="mt-6 flex justify-center">
              <div v-html="paginationLinks" class="pagination"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCountriesStore } from '../stores/countries';
import axios from 'axios';

const props = defineProps({
  language: {
    type: String,
    required: true
  }
});

const route = useRoute();
const router = useRouter();
const countriesStore = useCountriesStore();
const countries = ref([]);
const totalCount = ref(0);
const paginationLinks = ref(null);
const loading = ref(true);

onMounted(async () => {
  await fetchLanguageCountries();
});

// Watch for route changes to reload data when navigating between languages
watch(
  () => props.language,
  async (newLanguage) => {
    if (newLanguage) {
      loading.value = true;
      await fetchLanguageCountries();
    }
  }
);

async function fetchLanguageCountries() {
  try {
    loading.value = true;
    
    // Use axios directly with the proper API endpoint
    const response = await axios.get(`/api/languages/${encodeURIComponent(props.language)}`, {
      headers: { 'Accept': 'application/json' }
    });
    
    if (!response.data) {
      throw new Error('Failed to fetch countries');
    }
    
    countries.value = response.data.countries || [];
    totalCount.value = response.data.total_count || countries.value.length;
    paginationLinks.value = response.data.pagination || null;
    
  } catch (error) {
    console.error('Error fetching countries by language:', error);
    countries.value = [];
  } finally {
    loading.value = false;
  }
}

async function toggleFavorite(countryCode) {
  if (!countryCode) return;
  
  const result = await countriesStore.toggleFavorite(countryCode);
  if (result.success) {
    // Update the country in the list
    countries.value = countries.value.map(country => {
      if (country.cca3 === countryCode) {
        return { ...country, is_favorite: result.favorited };
      }
      return country;
    });
  }
}
</script>

<style scoped>
/* Custom pagination styles */
.pagination :deep(a), .pagination :deep(span) {
  @apply px-3 py-1 mx-1 text-indigo-600 bg-white dark:bg-gray-700 dark:text-indigo-400 rounded-md border border-gray-200 dark:border-gray-600;
}

.pagination :deep(.active) {
  @apply bg-indigo-100 dark:bg-indigo-900 font-semibold;
}

.pagination :deep(a:hover) {
  @apply bg-gray-50 dark:bg-gray-600;
}
</style> 