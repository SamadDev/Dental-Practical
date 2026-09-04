<template>
  <div class="card border border-slate-200 overflow-hidden">
    <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 flex items-center justify-between">
      <div>
        <h3 class="font-semibold text-slate-900">Patient Form Fields</h3>
        <p class="text-xs text-slate-500 mt-0.5">Configure which fields appear in patient forms</p>
      </div>
      <button type="button" class="btn-ghost btn-sm" @click="show = !show">
        {{ show ? 'Hide' : 'Configure' }}
      </button>
    </div>

    <div v-if="show" class="p-4">
      <div class="space-y-3">
        <div v-for="field in fieldList" :key="field.key"
             class="flex items-center justify-between p-3 rounded-lg border border-slate-100 hover:border-slate-200 transition-colors">
          <div class="flex items-center gap-3">
            <button type="button" @click="toggleField(field.key)"
                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    :class="field.show ? 'bg-indigo-500' : 'bg-slate-300'">
              <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                    :class="field.show ? 'translate-x-4' : 'translate-x-0'" />
            </button>
            <span class="text-sm font-medium text-slate-700">{{ field.label }}</span>
          </div>

          <div v-if="field.show && field.key !== 'name'" class="flex items-center gap-2">
            <label class="flex items-center gap-1.5 text-xs text-slate-500 cursor-pointer">
              <input type="checkbox" :checked="field.required" @change="setFieldRequired(field.key, $event.target.checked)"
                     class="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500" />
              Required
            </label>
          </div>
        </div>
      </div>

      <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
        <p class="text-xs text-slate-500">
          {{ visibleCount }} of {{ totalCount }} fields visible
        </p>
        <button type="button" class="btn-ghost btn-sm text-red-500" @click="resetToDefaults">
          Reset to Defaults
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePatientFormFields } from '../composables/usePatientFormFields';

const { fields, getVisibleFields, toggleField, setFieldRequired, resetToDefaults } = usePatientFormFields();

const show = ref(false);

const fieldList = computed(() => getVisibleFields());
const visibleCount = computed(() => fieldList.value.length);
const totalCount = computed(() => Object.keys(fields.value).length);
</script>
