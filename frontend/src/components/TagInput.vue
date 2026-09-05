<template>
  <div class="tag-input" :class="{ 'tag-input--focused': isFocused }">
    <div class="tag-input__tags">
      <span v-for="tag in modelValue" :key="tag.id || tag" class="tag-input__tag">
        {{ tag.name || tag }}
        <button type="button" @click="removeTag(tag)" class="tag-input__tag-remove" aria-label="Remove">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </span>
      <input
        ref="inputRef"
        v-model="searchQuery"
        type="text"
        :placeholder="modelValue.length === 0 ? placeholder : ''"
        class="tag-input__input"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown.enter.prevent="handleEnter"
        @keydown.backspace="handleBackspace"
        @input="handleInput"
      />
    </div>

    <div v-if="showSuggestions && filteredSuggestions.length > 0" class="tag-input__dropdown">
      <button
        v-for="item in filteredSuggestions"
        :key="item.id"
        type="button"
        class="tag-input__suggestion"
        @mousedown.prevent="selectItem(item)"
      >
        {{ item.name }}
      </button>
      <button
        v-if="searchQuery && !exactMatch"
        type="button"
        class="tag-input__suggestion tag-input__suggestion--add"
        @mousedown.prevent="addNewItem"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add "{{ searchQuery }}"
      </button>
    </div>

    <div v-else-if="showSuggestions && searchQuery && filteredSuggestions.length === 0" class="tag-input__dropdown">
      <button
        type="button"
        class="tag-input__suggestion tag-input__suggestion--add"
        @mousedown.prevent="addNewItem"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add "{{ searchQuery }}"
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  suggestions: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Type to search...',
  },
  labelField: {
    type: String,
    default: 'name',
  },
});

const emit = defineEmits(['update:modelValue', 'add-new']);

const inputRef = ref(null);
const searchQuery = ref('');
const isFocused = ref(false);
const showSuggestions = ref(false);

const filteredSuggestions = computed(() => {
  if (!searchQuery.value) return props.suggestions.slice(0, 10);
  const query = searchQuery.value.toLowerCase();
  return props.suggestions
    .filter((item) => {
      const name = item[props.labelField] || item;
      return name.toLowerCase().includes(query);
    })
    .slice(0, 10);
});

const exactMatch = computed(() => {
  if (!searchQuery.value) return false;
  const query = searchQuery.value.toLowerCase();
  return props.suggestions.some(
    (item) => (item[props.labelField] || item).toLowerCase() === query
  );
});

function handleFocus() {
  isFocused.value = true;
  showSuggestions.value = true;
}

function handleBlur() {
  isFocused.value = false;
  setTimeout(() => {
    showSuggestions.value = false;
  }, 150);
}

function handleInput() {
  showSuggestions.value = true;
}

function handleEnter() {
  if (filteredSuggestions.value.length > 0 && !exactMatch.value) {
    selectItem(filteredSuggestions.value[0]);
  } else if (searchQuery.value && !exactMatch.value) {
    addNewItem();
  }
}

function handleBackspace() {
  if (!searchQuery.value && props.modelValue.length > 0) {
    removeTag(props.modelValue[props.modelValue.length - 1]);
  }
}

function selectItem(item) {
  const name = item[props.labelField] || item;
  const newTag = { id: item.id || name, name };
  if (!props.modelValue.some((v) => (v.id || v) === newTag.id)) {
    emit('update:modelValue', [...props.modelValue, newTag]);
  }
  searchQuery.value = '';
  showSuggestions.value = false;
  inputRef.value?.focus();
}

function addNewItem() {
  if (searchQuery.value.trim()) {
    emit('add-new', searchQuery.value.trim());
    searchQuery.value = '';
  }
}

function removeTag(tag) {
  const tagId = tag.id || tag;
  emit(
    'update:modelValue',
    props.modelValue.filter((t) => (t.id || t) !== tagId)
  );
}
</script>

<style scoped>
.tag-input {
  position: relative;
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.375rem;
  background: white;
  transition: border-color 0.15s;
  min-height: 44px;
}

.tag-input--focused {
  border-color: #6366f1;
  outline: 2px solid #6366f1/20;
}

.tag-input__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  align-items: center;
}

.tag-input__tag {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.125rem 0.5rem;
  background: #f1f5f9;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-medium: #475569;
}

.tag-input__tag-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: color 0.15s;
}

.tag-input__tag-remove:hover {
  color: #ef4444;
}

.tag-input__input {
  flex: 1;
  min-width: 120px;
  border: none;
  outline: none;
  padding: 0.25rem;
  font-size: 0.875rem;
  background: transparent;
}

.tag-input__dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  margin-top: 0.25rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  max-height: 200px;
  overflow-y: auto;
  z-index: 50;
}

.tag-input__suggestion {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  text-align: left;
  font-size: 0.875rem;
  color: #334155;
  transition: background 0.1s;
}

.tag-input__suggestion:hover {
  background: #f8fafc;
}

.tag-input__suggestion--add {
  color: #6366f1;
  border-top: 1px solid #e2e8f0;
}

.tag-input__suggestion--add:hover {
  background: #eef2ff;
}
</style>
