<template>
  <section class="flex justify-center">
    <div class="w-full max-w-6xl">
      <header class="mb-5 flex items-center justify-between gap-3 border-b border-slate-200 pb-3">
        <div>
          <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $t('nav.home') }}</p>
          <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-800">Clinic menu</h2>
        </div>
        <div class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
          {{ totalSections }} modules
        </div>
      </header>

      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 xl:gap-4">
        <router-link
          v-for="m in modules" :key="m.to" :to="m.to"
          class="section-tile"
        >
          <div class="section-tile-icon" :style="{ background: m.bg, color: m.fg }" aria-hidden="true">
            <Icon :name="m.icon" :size="22" :stroke-width="1.8" />
          </div>

          <div class="section-tile-content">
            <p class="section-tile-label">{{ $t(`nav.${m.name}`) }}</p>
            <p class="section-tile-desc">{{ m.short }}</p>
          </div>
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup>
import Icon from '../components/Icon.vue';

const modules = [
  { name: 'queue',     to: '/queue',         icon: 'calendar',     bg: '#eef2ff', fg: '#4361ee', short: 'Queue' },
  { name: 'patients',  to: '/patients',      icon: 'users',        bg: '#e0f2fe', fg: '#0369a1', short: 'Patients' },
  { name: 'archive',   to: '/archive',       icon: 'archive',      bg: '#fdf2f8', fg: '#be185d', short: 'Archive' },
  { name: 'dashboard', to: '/dashboard',     icon: 'bar-chart',    bg: '#eff6ff', fg: '#1d4ed8', short: 'Dashboard' },
  { name: 'plans',     to: '/payment-plans', icon: 'credit-card',  bg: '#ecfdf5', fg: '#047857', short: 'Plans' },
  { name: 'inventory', to: '/inventory',     icon: 'package',      bg: '#fefce8', fg: '#a16207', short: 'Inventory' },
  { name: 'vendors',   to: '/vendors',       icon: 'factory',      bg: '#f5f3ff', fg: '#6d28d9', short: 'Vendors' },
  { name: 'expenses',  to: '/expenses',      icon: 'receipt',      bg: '#fff7ed', fg: '#c2410c', short: 'Expenses' },
];

const totalSections = modules.length;
</script>

<style scoped>
.section-tile {
  @apply flex min-h-[110px] flex-col items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white p-3 text-center shadow transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary;
}
.section-tile-icon {
  @apply grid h-9 w-9 place-items-center rounded-md shadow-sm ring-1 ring-black/5;
}
.section-tile-content {
  @apply flex flex-col items-center gap-0.5;
}
.section-tile-label {
  @apply text-xs font-bold text-slate-800;
}
.section-tile-desc {
  @apply text-[9px] font-semibold uppercase tracking-[0.06em] text-slate-500;
}
</style>
