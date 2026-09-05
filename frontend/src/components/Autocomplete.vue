<template>
  <div class="autocomplete" :class="{ 'autocomplete--open': isOpen }">
    <button
      v-if="!isOpen && !modelValue"
      type="button"
      class="autocomplete__trigger autocomplete__trigger--placeholder"
      @click="open"
    >
      <span class="autocomplete__placeholder">{{ placeholder }}</span>
      <svg class="w-4 h-4 autocomplete__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <button
      v-else-if="!isOpen && modelValue"
      type="button"
      class="autocomplete__trigger"
      @click="open"
    >
      <span class="autocomplete__value">{{ selectedLabel }}</span>
      <svg class="w-4 h-4 autocomplete__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <div v-if="isOpen" class="autocomplete__dropdown">
      <div class="autocomplete__search-wrapper">
        <svg class="w-4 h-4 autocomplete__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          ref="searchInput"
          v-model="searchQuery"
          type="text"
          class="autocomplete__search"
          :placeholder="$t('common.search')"
          @keydown.escape="close"
          @keydown.enter.prevent="handleEnter"
          @keydown.arrow-down.prevent="navigateDown"
          @keydown.arrow-up.prevent="navigateUp"
        />
      </div>

      <div class="autocomplete__options">
        <button
          v-for="(option, index) in filteredOptions"
          :key="option.id"
          type="button"
          class="autocomplete__option"
          :class="{
            'autocomplete__option--selected': option[labelField] === modelValue || option.id === modelValue,
            'autocomplete__option--highlighted': highlightedIndex === index,
          }"
          @click="select(option)"
          @mouseenter="highlightedIndex = index"
        >
          {{ option[labelField] }}
        </button>

        <button
          v-if="searchQuery && !exactMatch && allowAdd"
          type="button"
          class="autocomplete__option autocomplete__option--add"
          @click="addNew"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Add "{{ searchQuery }}"
        </button>

        <p v-if="filteredOptions.length === 0 && !searchQuery" class="autocomplete__empty">
          {{ $t('common.no_options') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Select...',
  },
  labelField: {
    type: String,
    default: 'name',
  },
  allowAdd: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'add-new']);

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(0);
const searchInput = ref(null);

const selectedLabel = computed(() => {
  if (!props.modelValue) return '';
  const selected = props.options.find(
    (o) => o[props.labelField] === props.modelValue || o.id === props.modelValue
  );
  return selected ? selected[props.labelField] : props.modelValue;
});

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options.slice(0, 20);
  const query = searchQuery.value.toLowerCase();
  return props.options
    .filter((o) => o[props.labelField].toLowerCase().includes(query))
    .slice(0, 20);
});

const exactMatch = computed(() => {
  if (!searchQuery.value) return false;
  const query = searchQuery.value.toLowerCase();
  return props.options.some((o) => o[props.labelField].toLowerCase() === query);
});

watch(isOpen, (open) => {
  if (open) {
    searchQuery.value = '';
    highlightedIndex.value = 0;
    nextTick(() => searchInput.value?.focus());
  }
});

function open() {
  isOpen.value = true;
}

function close() {
  isOpen.value = false;
  searchQuery.value = '';
}

function select(option) {
  emit('update:modelValue', option[props.labelField]);
  close();
}

function handleEnter() {
  if (filteredOptions.value.length > 0) {
    select(filteredOptions.value[highlightedIndex.value]);
  } else if (searchQuery.value && !exactMatch.value && props.allowAdd) {
    addNew();
  }
}

function navigateDown() {
  if (highlightedIndex.value < filteredOptions.value.length - 1) {
    highlightedIndex.value++;
  }
}

function navigateUp() {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--;
  }
}

function addNew() {
  if (searchQuery.value.trim()) {
    emit('add-new', searchQuery.value.trim());
    close();
  }
}
</script>

<style scoped>
.autocomplete {
  position: relative;
}

.autocomplete__trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  background: white;
  font-size: 0.875rem;
  text-align: left;
  transition: border-color 0.15s;
}

.autocomplete__trigger:hover {
  border-color: #cbd5e1;
}

.autocomplete--open .autocomplete__trigger {
  border-color: #6366f1;
  outline: 2px solid #6366f1/20;
}

.autocomplete__trigger--placeholder {
  color: #94a3b8;
}

.autocomplete__value {
  color: #1e293b;
}

.autocomplete__chevron {
  color: #94a3b8;
  transition: transform 0.2s;
}

.autocomplete--open .autocomplete__chevron {
  transform: rotate(180deg);
}

.autocomplete__dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  margin-top: 0.25rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  z-index: 50;
  overflow: hidden;
}

.autocomplete__search-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.autocomplete__search-icon {
  color: #94a3b8;
  flex-shrink: 0;
}

.autocomplete__search {
  flex: 1;
  border: none;
  outline: none;
  font-size: 0.875rem;
  background: transparent;
}

.autocomplete__options {
  max-height: 240px;
  overflow-y: auto;
}

.autocomplete__option {
  display: block;
  width: 100%;
  padding: 0.625rem 0.75rem;
  text-align: left;
  font-size: 0.875rem;
  color: #334155;
  transition: background 0.1s;
}

.autocomplete__option:hover,
.autocomplete__option--highlighted {
  background: #f8fafc;
}

.autocomplete__option--selected {
  background: #eef2ff;
  color: #4f46e5;
  font-weight: 500;
}

.autocomplete__option--add {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6366f1;
  border-top: 1px solid #e2e8f0;
}

.autocomplete__option--add:hover {
  background: #eef2ff;
}

.autocomplete__empty {
  padding: 1rem;
  text-align: center;
  font-size: 0.875rem;
  color: #94a3b8;
}
</style>
