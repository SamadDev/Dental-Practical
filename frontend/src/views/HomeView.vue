<template>
  <section>
    <header class="mb-6">
      <h2 class="text-2xl font-bold tracking-tight">{{ $t('home.title') }}</h2>
      <p class="mt-0.5 text-sm text-slate-500">{{ $t('home.subtitle') }}</p>
    </header>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:gap-6">
      <router-link
        v-for="m in modules" :key="m.to" :to="m.to"
        class="home-tile group"
      >
        <span class="home-tile-icon" :style="{ background: m.bg, color: m.fg }" aria-hidden="true">
          <Icon :name="m.icon" :size="34" :stroke-width="1.75" />
        </span>
        <span class="home-tile-label">{{ $t(`nav.${m.name}`) }}</span>
      </router-link>
    </div>
  </section>
</template>

<script setup>
import Icon from '../components/Icon.vue';

const modules = [
  { name: 'queue',     to: '/queue',         icon: 'calendar',     bg: '#eef2ff', fg: '#4361ee' },
  { name: 'patients',  to: '/patients',      icon: 'folder',       bg: '#e0f2fe', fg: '#0369a1' },
  { name: 'archive',   to: '/archive',       icon: 'archive',      bg: '#fdf2f8', fg: '#be185d' },
  { name: 'plans',     to: '/payment-plans', icon: 'credit-card',  bg: '#ecfdf5', fg: '#047857' },
  { name: 'inventory', to: '/inventory',     icon: 'package',      bg: '#fefce8', fg: '#a16207' },
  { name: 'vendors',   to: '/vendors',       icon: 'factory',      bg: '#f5f3ff', fg: '#6d28d9' },
  { name: 'cashflow',  to: '/cash-flow',     icon: 'trending-up',  bg: '#f0fdf4', fg: '#15803d' },
  { name: 'expenses',  to: '/expenses',      icon: 'receipt',      bg: '#fff7ed', fg: '#c2410c' },
  { name: 'dashboard', to: '/dashboard',     icon: 'bar-chart',    bg: '#eff6ff', fg: '#1d4ed8' },
];
</script>

<style scoped>
.home-tile {
  @apply flex flex-col items-center justify-center gap-4 rounded-2xl border border-slate-200/70
         bg-white px-4 py-8 shadow-card transition-all duration-200
         hover:-translate-y-1 hover:border-brand-300 hover:shadow-card-hov
         focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500;
}
.home-tile-icon {
  @apply grid h-20 w-20 place-items-center rounded-2xl shadow-sm
         transition-transform duration-200 group-hover:scale-110;
}
.home-tile-label {
  @apply text-sm font-extrabold text-slate-600 group-hover:text-brand-600;
}
</style>
