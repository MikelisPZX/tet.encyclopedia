import axios from 'axios';

const api = {
  /**
   * Search countries by name, including translations
   * @param {string} query - The search query
   * @returns {Promise} - Promise with search results
   */
  searchCountries: async (query) => {
    if (!query || query.trim() === '') {
      return [];
    }
    
    try {
      const response = await axios.get(`/api/search?q=${encodeURIComponent(query)}`, {
        headers: { 'Accept': 'application/json' }
      });
      
      return response.data;
    } catch (error) {
      console.error('Error searching countries:', error);
      return [];
    }
  },
  
  /**
   * Get a country by code
   * @param {string} code - The country code (cca3)
   * @returns {Promise} - Promise with country data
   */
  getCountryByCode: async (code) => {
    try {
      const response = await axios.get(`/api/countries/${code}`, {
        headers: { 'Accept': 'application/json' }
      });
      
      return response.data;
    } catch (error) {
      console.error('Error fetching country:', error);
      return null;
    }
  },
  
  /**
   * Toggle favorite status for a country
   * @param {string} countryCode - The country code (cca3)
   * @returns {Promise} - Promise with result
   */
  toggleFavorite: async (countryCode) => {
    try {
      const response = await axios.post(`/favorites/${countryCode}`, {}, {
        headers: { 
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      
      return response.data;
    } catch (error) {
      console.error('Error toggling favorite:', error);
      return { success: false };
    }
  },
  
  /**
   * Get favorite countries
   * @returns {Promise} - Promise with favorites
   */
  getFavorites: async () => {
    try {
      const response = await axios.get('/favorites', {
        headers: { 'Accept': 'application/json' }
      });
      
      return response.data;
    } catch (error) {
      console.error('Error fetching favorites:', error);
      return [];
    }
  },
  
  /**
   * Get countries by language
   * @param {string} language - The language name or code
   * @returns {Promise} - Promise with countries data
   */
  getCountriesByLanguage: async (language) => {
    try {
      const response = await axios.get(`/api/languages/${language}`, {
        headers: { 'Accept': 'application/json' }
      });
      
      return response.data;
    } catch (error) {
      console.error('Error fetching countries by language:', error);
      return null;
    }
  }
};

export default api; 