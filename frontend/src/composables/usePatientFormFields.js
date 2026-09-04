import { ref, watch } from 'vue';

const STORAGE_KEY = 'patient_form_fields';

const defaultFields = {
  name: { show: true, required: true, order: 1, label: 'Name' },
  phone: { show: true, required: false, order: 2, label: 'Phone' },
  age: { show: true, required: false, order: 3, label: 'Age' },
  gender: { show: true, required: false, order: 4, label: 'Gender' },
  address: { show: true, required: false, order: 5, label: 'Address' },
  medical_notes: { show: true, required: false, order: 6, label: 'Medical Notes' },
  appointment_date: { show: true, required: false, order: 7, label: 'Appointment' },
};

function loadFields() {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      const parsed = JSON.parse(stored);
      return { ...defaultFields, ...parsed };
    }
  } catch (e) {
    console.warn('Failed to load patient form fields settings');
  }
  return { ...defaultFields };
}

function saveFields(fields) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(fields));
  } catch (e) {
    console.warn('Failed to save patient form fields settings');
  }
}

const fields = ref(loadFields());

watch(fields, (newFields) => {
  saveFields(newFields);
}, { deep: true });

export function usePatientFormFields() {
  function getVisibleFields() {
    return Object.entries(fields.value)
      .filter(([, config]) => config.show)
      .sort(([, a], [, b]) => a.order - b.order)
      .map(([key, config]) => ({ key, ...config }));
  }

  function isFieldVisible(key) {
    return fields.value[key]?.show ?? true;
  }

  function isFieldRequired(key) {
    return fields.value[key]?.required ?? false;
  }

  function toggleField(key) {
    if (fields.value[key]) {
      fields.value[key].show = !fields.value[key].show;
    }
  }

  function setFieldRequired(key, required) {
    if (fields.value[key]) {
      fields.value[key].required = required;
    }
  }

  function resetToDefaults() {
    fields.value = { ...defaultFields };
  }

  return {
    fields,
    getVisibleFields,
    isFieldVisible,
    isFieldRequired,
    toggleField,
    setFieldRequired,
    resetToDefaults,
  };
}
