import { createRouter, createWebHistory } from 'vue-router';
import CountriesIndex from '../views/CountriesIndex.vue';
import CountryDetail from '../views/CountryDetail.vue';
import LanguageCountries from '../views/LanguageCountries.vue';

const routes = [
  {
    path: '/',
    name: 'home',
    component: CountriesIndex
  },
  {
    path: '/countries/:code',
    name: 'country-detail',
    component: CountryDetail,
    props: true
  },
  {
    path: '/languages/:language',
    name: 'countries-by-language',
    component: LanguageCountries,
    props: true
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router; 