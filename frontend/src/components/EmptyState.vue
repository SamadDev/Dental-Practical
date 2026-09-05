<template>
  <div class="empty-state">
    <div class="empty-icon" :class="`empty-icon--${variant}`">
      <svg v-if="variant === 'patients'" class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M23 21v-2a4 4 0 0 0-3-3.87"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      <svg v-else-if="variant === 'queue'" class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
        <rect x="9" y="3" width="6" height="4" rx="1"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 16h6"/>
      </svg>
      <svg v-else-if="variant === 'calendar'" class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <rect x="3" y="4" width="18" height="18" rx="2" stroke-width="1.5"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 2v4M8 2v4M3 10h18"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>
      </svg>
      <svg v-else-if="variant === 'search'" class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8" stroke-width="1.5"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 8v6M8 11h6" opacity="0.5"/>
      </svg>
      <svg v-else-if="variant === 'money'" class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <rect x="2" y="6" width="20" height="12" rx="2" stroke-width="1.5"/>
        <circle cx="12" cy="12" r="3" stroke-width="1.5"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 12h.01M18 12h.01"/>
      </svg>
      <svg v-else-if="variant === 'archive'" class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 1 0 0-4h14a2 2 0 1 0 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 12h4"/>
      </svg>
      <svg v-else class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-4.5a2 2 0 0 1-2-2V7m8 6h4.5a2 2 0 0 1 2 2v2"/>
      </svg>
    </div>

    <h3 class="empty-title">{{ title }}</h3>
    <p class="empty-description">{{ description }}</p>

    <div v-if="$slots.actions" class="empty-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'patients', 'queue', 'calendar', 'search', 'money', 'archive'].includes(v),
  },
  title: {
    type: String,
    default: 'Nothing here yet',
  },
  description: {
    type: String,
    default: 'Get started by adding something new.',
  },
});
</script>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  text-align: center;
}

.empty-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  margin-bottom: 1.5rem;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  color: #E73F1E;
}

.empty-icon--patients {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  color: #3b82f6;
}

.empty-icon--queue {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  color: #22c55e;
}

.empty-icon--calendar {
  background: linear-gradient(135deg, #faf5ff 0%, #ede9fe 100%);
  color: #8b5cf6;
}

.empty-icon--search {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  color: #f59e0b;
}

.empty-icon--money {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  color: #10b981;
}

.empty-icon--archive {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #64748b;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.empty-description {
  font-size: 0.875rem;
  color: #64748b;
  max-width: 280px;
  margin-bottom: 1.5rem;
}

.empty-actions {
  display: flex;
  gap: 0.75rem;
}
</style>
