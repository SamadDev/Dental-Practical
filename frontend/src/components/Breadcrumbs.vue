<template>
  <nav v-if="crumbs.length > 1" class="breadcrumbs no-print" aria-label="Breadcrumb">
    <ol class="breadcrumb-list">
      <li class="breadcrumb-item">
        <router-link to="/home" class="breadcrumb-link">
          <Icon name="home" :size="14" />
          <span class="hidden sm:inline">{{ $t('nav.home') }}</span>
        </router-link>
      </li>
      <li v-for="(crumb, index) in crumbs" :key="index" class="breadcrumb-item">
        <span class="breadcrumb-separator">/</span>
        <router-link
          v-if="crumb.to && index < crumbs.length - 1"
          :to="crumb.to"
          class="breadcrumb-link"
        >
          {{ crumb.label }}
        </router-link>
        <span v-else class="breadcrumb-current">{{ crumb.label }}</span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import Icon from './Icon.vue';

const route = useRoute();

const routeLabelMap = {
  home: 'nav.home',
  queue: 'nav.queue',
  patients: 'nav.patients',
  patient: 'nav.patients',
  archive: 'nav.archive',
  dashboard: 'nav.dashboard',
  expenses: 'nav.expenses',
  plans: 'nav.plans',
  inventory: 'nav.inventory',
  vendors: 'nav.vendors',
  doctors: 'nav.doctors',
  receptionists: 'nav.receptionists',
  profile: 'nav.profile',
  roles: 'nav.roles',
};

const crumbs = computed(() => {
  const result = [];

  for (const name of route.matched.map(r => r.name)) {
    if (name === 'login') continue;

    const labelKey = routeLabelMap[name];
    if (labelKey) {
      result.push({
        label: labelKey,
        to: name === 'home' ? '/home' : null,
      });
    }
  }

  if (route.name === 'patient' && route.params.id) {
    result.push({
      label: `#${route.params.id}`,
      to: null,
    });
  }

  return result;
});
</script>

<style scoped>
.breadcrumbs {
  padding: 0.75rem 0;
  margin-bottom: 0.5rem;
}

.breadcrumb-list {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.25rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.breadcrumb-separator {
  color: #94a3b8;
  font-size: 0.875rem;
}

.breadcrumb-link {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  color: #64748b;
  text-decoration: none;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  transition: all 0.15s;
}

.breadcrumb-link:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.breadcrumb-current {
  font-size: 0.875rem;
  color: #1e293b;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
}
</style>
