<template>
  <div class="vue-app min-h-screen bg-gray-100 dark:bg-gray-900">
    <header class="bg-gray-900 shadow" id="app-header">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        <router-link to="/" class="flex flex-row items-center gap-5 hover:opacity-90 transition">
          <img src="https://www.tet.lv/templates/tet/images/tet-logo-dark-footer.svg" alt="Tet Logo" class="h-8 w-auto inline-block" />
          <span class="text-xl font-bold text-white inline">Countries Encyclopedia</span>
        </router-link>
      </div>
    </header>
    <main class="font-inter">
      <router-view v-slot="{ Component, route }">
        <transition name="fade" mode="out-in">
          <keep-alive>
            <component :is="Component" :key="route.fullPath" />
          </keep-alive>
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { onMounted, provide, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const stylesLoaded = ref(false);

// Reset scroll position on route changes
router.beforeEach((to, from, next) => {
  if (to.path !== from.path) {
    window.scrollTo(0, 0);
  }
  next();
});

// Make sure fonts are loaded before rendering
onMounted(() => {
  document.fonts.ready.then(() => {
    document.body.classList.add('fonts-loaded');
    stylesLoaded.value = true;
  });
  
  // Make the font available to all components
  provide('stylesLoaded', stylesLoaded);
});
</script>

<style>
/* Import Inter font from Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* Transition effects */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style> 