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
        <span class="home-tile-icon" :style="{ background: m.bg }" aria-hidden="true">{{ m.icon }}</span>
        <span class="home-tile-label">{{ $t(`nav.${m.name}`) }}</span>
      </router-link>
    </div>
  </section>
</template>

<script setup>
const modules = [
  { name: 'queue',     to: '/queue',         icon: '📅', bg: '#e0f2fe' },
  { name: 'patients',  to: '/patients',      icon: '🗂️', bg: '#dbeafe' },
  { name: 'archive',   to: '/archive',       icon: '🦷', bg: '#fce7f3' },
  { name: 'plans',     to: '/payment-plans', icon: '💳', bg: '#d1fae5' },
  { name: 'inventory', to: '/inventory',     icon: '📦', bg: '#fef3c7' },
  { name: 'vendors',   to: '/vendors',       icon: '🏭', bg: '#ede9fe' },
  { name: 'cashflow',  to: '/cash-flow',     icon: '📈', bg: '#dcfce7' },
  { name: 'expenses',  to: '/expenses',      icon: '🧾', bg: '#ffedd5' },
  { name: 'dashboard', to: '/dashboard',     icon: '📊', bg: '#e0e7ff' },
];
</script>

<style scoped>
.home-tile {
  @apply flex flex-col items-center justify-center gap-4 rounded-2xl border-2 border-sky-100
         bg-white px-4 py-8 shadow-card transition-all duration-200
         hover:-translate-y-1 hover:border-sky-200 hover:shadow-card-hov
         focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500;
}
.home-tile-icon {
  @apply grid h-20 w-20 place-items-center rounded-2xl text-4xl shadow-sm
         transition-transform duration-200 group-hover:scale-110;
}
.home-tile-label {
  @apply text-sm font-bold text-slate-700 group-hover:text-slate-900;
}
</style>
