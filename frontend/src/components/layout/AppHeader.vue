<template>
  <header class="app-header">
    <div class="header-content">
      <!-- Left: Menu button + Title -->
      <div class="header-left">
        <button
          type="button"
          class="menu-toggle"
          @click="toggleSidebar"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 12h18M3 6h18M3 18h18" />
          </svg>
        </button>
        <div class="header-title">
          <h1 class="title-text">{{ pageTitle }}</h1>
        </div>
      </div>

      <!-- Right: Actions -->
      <div class="header-right">
        <!-- Date/Time -->
        <div class="header-datetime">
          <span class="datetime-text">{{ currentDate }}</span>
        </div>

        <!-- Print Button -->
        <button
          type="button"
          class="header-btn"
          @click="print"
          title="Print"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
          </svg>
        </button>

        <!-- Language Dropdown -->
        <div class="header-dropdown">
          <button
            type="button"
            class="header-btn"
            @click="showLangDropdown = !showLangDropdown"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
            </svg>
          </button>
          <div v-if="showLangDropdown" class="dropdown-menu">
            <button
              v-for="langOption in languages"
              :key="langOption.code"
              type="button"
              class="dropdown-item"
              :class="{ 'dropdown-item--active': lang.current === langOption.code }"
              @click="changeLanguage(langOption.code)"
            >
              {{ langOption.label }}
            </button>
          </div>
        </div>

        <!-- User Dropdown -->
        <div class="header-dropdown">
          <button
            type="button"
            class="user-btn"
            @click="showUserDropdown = !showUserDropdown"
          >
            <div class="user-avatar">
              {{ userInitials }}
            </div>
          </button>
          <div v-if="showUserDropdown" class="dropdown-menu dropdown-menu--user">
            <div class="user-info">
              <div class="user-avatar-lg">
                {{ userInitials }}
              </div>
              <div class="user-details">
                <span class="user-name">{{ user?.name }}</span>
                <span class="user-email">{{ user?.email }}</span>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <button
              type="button"
              class="dropdown-item dropdown-item--danger"
              @click="handleLogout"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
              </svg>
              {{ $t('common.logout') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useLangStore } from '../../store/lang';
import { useAuth } from '../../composables/useAuth';

const route = useRoute();
const { t, locale } = useI18n();
const lang = useLangStore();
const { user, logout } = useAuth();
const emit = defineEmits(['toggle-sidebar']);

const showLangDropdown = ref(false);
const showUserDropdown = ref(false);
const currentDate = ref('');

const languages = [
  { code: 'en', label: 'English' },
  { code: 'ku', label: 'کوردی' },
  { code: 'ar', label: 'العربية' },
];

const userInitials = computed(() => {
  const name = user.value?.name || '?';
  return name.trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
});

const pageTitle = computed(() => {
  const name = route.name;
  if (name) return t(`nav.${name}`, route.name);
  return '';
});

function updateDateTime() {
  const now = new Date();
  const options = {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  };
  currentDate.value = now.toLocaleDateString('en-US', options);
}

function changeLanguage(code) {
  lang.set(code);
  locale.value = code;
  showLangDropdown.value = false;
  document.querySelector('html')?.setAttribute('dir', lang.isRtl ? 'rtl' : 'ltr');
}

async function handleLogout() {
  await logout();
  window.location.href = '/login';
}

function toggleSidebar() {
  emit('toggle-sidebar');
}

function print() {
  window.print();
}

function handleClickOutside(event) {
  if (!event.target.closest('.header-dropdown')) {
    showLangDropdown.value = false;
    showUserDropdown.value = false;
  }
}

onMounted(() => {
  updateDateTime();
  setInterval(updateDateTime, 60000);
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.app-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 30;
}

html.dark .app-header {
  background: #1e293b;
  border-color: #334155;
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.menu-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  color: #64748b;
  transition: all 0.2s;
}

.menu-toggle:hover {
  background: #f1f5f9;
  color: #1e293b;
}

html.dark .menu-toggle:hover {
  background: #334155;
  color: #f1f5f9;
}

.header-title {
  display: flex;
  flex-direction: column;
}

.title-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

html.dark .title-text {
  color: #f1f5f9;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-datetime {
  padding: 0.5rem 1rem;
  background: #f8fafc;
  border-radius: 10px;
}

html.dark .header-datetime {
  background: #334155;
}

.datetime-text {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

html.dark .datetime-text {
  color: #94a3b8;
}

.header-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  color: #64748b;
  transition: all 0.2s;
}

.header-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}

html.dark .header-btn:hover {
  background: #334155;
  color: #f1f5f9;
}

.header-dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  min-width: 180px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.5rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  z-index: 50;
}

html.dark .dropdown-menu {
  background: #1e293b;
  border-color: #334155;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
  border-radius: 8px;
  transition: all 0.15s;
  cursor: pointer;
}

.dropdown-item:hover {
  background: #f1f5f9;
  color: #1e293b;
}

html.dark .dropdown-item {
  color: #cbd5e1;
}

html.dark .dropdown-item:hover {
  background: #334155;
  color: #f1f5f9;
}

.dropdown-item--active {
  background: #FEE2E2;
  color: #dc2626;
}

html.dark .dropdown-item--active {
  background: rgba(231, 63, 30, 0.2);
  color: #fca5a5;
}

.dropdown-item--danger {
  color: #dc2626;
}

.dropdown-item--danger:hover {
  background: #FEE2E2;
}

html.dark .dropdown-item--danger:hover {
  background: rgba(239, 68, 68, 0.2);
}

.user-btn {
  display: flex;
  align-items: center;
  padding: 0;
  background: none;
  border: none;
  cursor: pointer;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #E73F1E 0%, #dc2626 100%);
  color: white;
  font-size: 0.875rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(231, 63, 30, 0.3);
  transition: transform 0.2s;
}

.user-btn:hover .user-avatar {
  transform: scale(1.05);
}

.dropdown-menu--user {
  min-width: 240px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
}

.user-avatar-lg {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #E73F1E 0%, #dc2626 100%);
  color: white;
  font-size: 1rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-details {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.user-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

html.dark .user-name {
  color: #f1f5f9;
}

.user-email {
  font-size: 0.75rem;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

html.dark .user-email {
  color: #94a3b8;
}

.dropdown-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 0.5rem 0;
}

html.dark .dropdown-divider {
  background: #334155;
}

@media (max-width: 768px) {
  .header-datetime {
    display: none;
  }
}
</style>
