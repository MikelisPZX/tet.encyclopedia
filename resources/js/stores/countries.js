import { defineStore } from 'pinia';
import api from '../services/api';

export const useCountriesStore = defineStore('countries', {
  state: () => ({
    countries: [],
    favorites: [],
    searchResults: [],
    loading: false,
    error: null
  }),
  
  getters: {
    getFavorites: (state) => state.favorites,
    getCountries: (state) => state.countries,
    getSearchResults: (state) => state.searchResults
  },
  
  actions: {
    async fetchFavorites() {
      this.loading = true;
      try {
        const favorites = await api.getFavorites();
        this.favorites = favorites;
        this.loading = false;
      } catch (error) {
        this.error = 'Failed to fetch favorites';
        this.loading = false;
      }
    },
    
    async searchCountries(query) {
      if (!query || query.trim() === '') {
        this.searchResults = [];
        return;
      }
      
      this.loading = true;
      try {
        console.log(`Searching for: ${query}`);
        // Use our API service which handles the search through translations
        const results = await api.searchCountries(query);
        console.log('Search results:', results);
        this.searchResults = results;
        this.loading = false;
      } catch (error) {
        console.error('Failed to search countries:', error);
        this.error = 'Failed to search countries';
        this.searchResults = [];
        this.loading = false;
      }
    },
    
    async toggleFavorite(countryCode) {
      try {
        const result = await api.toggleFavorite(countryCode);
        console.log('Store toggleFavorite result:', result);
        
        if (result.success) {
          // If country was favorited
          if (result.favorited) {
            const country = result.country;
            // Map country_code to cca3 for consistency
            const countryForStore = {
              ...country,
              cca3: country.country_code,
              name_common: country.country_name,
              is_favorite: true
            };
            
            // Add to favorites if not already there
            const exists = this.favorites.findIndex(c => c.cca3 === countryForStore.cca3 || c.country_code === countryForStore.country_code) !== -1;
            if (!exists) {
              this.favorites.push(countryForStore);
            } else {
              // Update the existing favorite
              const index = this.favorites.findIndex(c => c.cca3 === countryForStore.cca3 || c.country_code === countryForStore.country_code);
              if (index !== -1) {
                this.favorites[index] = {
                  ...this.favorites[index],
                  ...countryForStore,
                  is_favorite: true
                };
              }
            }
          } else {
            // Remove from favorites - handle both property naming schemes
            this.favorites = this.favorites.filter(c => c.cca3 !== countryCode && c.country_code !== countryCode);
          }
          
          // Update is_favorite flag in search results
          this.searchResults = this.searchResults.map(country => {
            if (country.cca3 === countryCode) {
              return { ...country, is_favorite: Boolean(result.favorited) };
            }
            return country;
          });
        }
        
        return result;
      } catch (error) {
        console.error('Failed to toggle favorite:', error);
        this.error = 'Failed to toggle favorite';
        return { success: false, message: 'Failed to toggle favorite' };
      }
    },
    
    async getCountryByCode(code) {
      this.loading = true;
      try {
        // Get country data with formatted fields
        const countryData = await api.getCountryByCode(code);
        
        // Format area for consistency with Blade template
        if (countryData.country && countryData.country.area) {
          // If area is a string with commas, remove commas first
          let areaValue = countryData.country.area;
          if (typeof areaValue === 'string') {
            areaValue = areaValue.replace(/,/g, '');
          }
          
          const area = parseFloat(areaValue);
          if (!isNaN(area)) {
            // Format large numbers as integers with thousands separators
            countryData.country.area = new Intl.NumberFormat().format(Math.round(area));
          }
        }
        
        // Format population rank 
        if (countryData.country && countryData.country.population_rank) {
          const rank = countryData.country.population_rank;
          if (rank !== null && rank !== "Unknown") {
            // Population rank will be displayed with hash in the template
          }
        }
        
        this.loading = false;
        return countryData;
      } catch (error) {
        this.error = 'Failed to fetch country';
        this.loading = false;
        return null;
      }
    },
    
    async getCountriesByLanguage(language) {
      this.loading = true;
      try {
        const countryData = await api.getCountriesByLanguage(language);
        this.loading = false;
        return countryData;
      } catch (error) {
        this.error = 'Failed to fetch countries by language';
        this.loading = false;
        return [];
      }
    }
  }
}); 