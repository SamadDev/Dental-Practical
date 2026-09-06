<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('reminder.title') }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('reminder.subtitle') }}</p>
            </div>
          </div>
          <button @click="$emit('close')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-4 space-y-4">
          <!-- Appointment Info -->
          <div v-if="appointment" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ patientName }}</p>
                <p class="text-sm text-gray-500">{{ formatDateTime(appointment) }}</p>
              </div>
            </div>
          </div>

          <!-- Reminder Options -->
          <div class="space-y-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('reminder.send_before') }}</p>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="option in reminderOptions"
                :key="option.value"
                type="button"
                @click="selectedReminder = option.value"
                class="p-3 rounded-lg border-2 transition-all text-center"
                :class="selectedReminder === option.value 
                  ? 'border-primary bg-primary/5 text-primary' 
                  : 'border-gray-200 dark:border-gray-600 hover:border-primary/50'"
              >
                <span class="block text-lg font-bold">{{ option.label }}</span>
                <span class="text-xs opacity-70">{{ option.sublabel }}</span>
              </button>
            </div>
          </div>

          <!-- Custom Time -->
          <div v-if="selectedReminder === 'custom'" class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('reminder.custom_time') }}
            </label>
            <input
              v-model="customTime"
              type="datetime-local"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
            />
          </div>

          <!-- Message Preview -->
          <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
            <p class="text-xs font-medium text-green-800 dark:text-green-300 mb-2">{{ $t('reminder.message_preview') }}</p>
            <p class="text-sm text-green-700 dark:text-green-400">{{ messagePreview }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
          <button @click="$emit('close')" class="btn-ghost">
            {{ $t('common.cancel') }}
          </button>
          <button @click="sendReminder" :disabled="sending" class="btn-primary flex items-center gap-2">
            <svg v-if="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            {{ $t('reminder.send_now') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatDateTime } from '../utils/datetime';

const props = defineProps({
  show: Boolean,
  patientName: { type: String, default: '' },
  patientPhone: { type: String, default: '' },
  appointment: { type: String, default: null },
});

const emit = defineEmits(['close', 'send']);

const { t } = useI18n();

const sending = ref(false);
const selectedReminder = ref('1day');
const customTime = ref('');

const reminderOptions = computed(() => [
  { value: '1hour', label: '1', sublabel: t('reminder.hour') },
  { value: '1day', label: '1', sublabel: t('reminder.day') },
  { value: '2days', label: '2', sublabel: t('reminder.days') },
  { value: '1week', label: '1', sublabel: t('reminder.week') },
  { value: 'custom', label: '⚙️', sublabel: t('reminder.custom') },
]);

const messagePreview = computed(() => {
  const name = props.patientName || t('reminder.dear_patient');
  const date = props.appointment 
    ? new Date(props.appointment).toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    : t('reminder.your_appointment');
  
  return t('reminder.message_template', { name, date });
});

const reminderTime = computed(() => {
  if (!props.appointment) return null;
  
  const aptDate = new Date(props.appointment);
  
  switch (selectedReminder.value) {
    case '1hour':
      aptDate.setHours(aptDate.getHours() - 1);
      break;
    case '1day':
      aptDate.setDate(aptDate.getDate() - 1);
      break;
    case '2days':
      aptDate.setDate(aptDate.getDate() - 2);
      break;
    case '1week':
      aptDate.setDate(aptDate.getDate() - 7);
      break;
    case 'custom':
      return customTime.value ? new Date(customTime.value) : null;
  }
  
  return aptDate;
});

const whatsappLink = computed(() => {
  if (!props.patientPhone) return '#';
  const phone = props.patientPhone.replace(/\D/g, '');
  const msg = messagePreview.value;
  return `https://wa.me/964${phone}?text=${encodeURIComponent(msg)}`;
});

function sendReminder() {
  sending.value = true;
  
  // Open WhatsApp with the pre-filled message
  window.open(whatsappLink.value, '_blank');
  
  // Emit event for tracking/reminders
  emit('send', {
    patientName: props.patientName,
    patientPhone: props.patientPhone,
    appointment: props.appointment,
    reminderTime: reminderTime.value,
    message: messagePreview.value,
  });
  
  setTimeout(() => {
    sending.value = false;
    emit('close');
  }, 500);
}

watch(() => props.show, (val) => {
  if (val) {
    selectedReminder.value = '1day';
    customTime.value = '';
  }
});
</script>
