<template>
  <section>
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center">
          <FontAwesomeIcon icon="fa-calendar" class="text-white" />
        </div>
        <h1 class="text-xl font-bold text-gray-800">{{ $t('calendar.title') }}</h1>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="btn-ghost btn-sm"
          @click="previousWeek"
        >
          <Icon name="chevron-left" class="w-4 h-4" />
        </button>
        <button
          type="button"
          class="px-3 py-1.5 text-sm font-medium rounded-lg bg-slate-100 hover:bg-slate-200"
          @click="goToToday"
        >
          {{ $t('calendar.today') }}
        </button>
        <button
          type="button"
          class="btn-ghost btn-sm"
          @click="nextWeek"
        >
          <Icon name="chevron-right" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Week Navigation -->
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-700">
        {{ weekStartFormatted }} - {{ weekEndFormatted }}
      </h2>
      <div class="flex gap-1">
        <button
          v-for="view in views"
          :key="view.key"
          type="button"
          class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
          :class="currentView === view.key ? 'bg-primary text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
          @click="currentView = view.key"
        >
          {{ view.label }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-7 gap-2">
      <div v-for="i in 7" :key="i" class="card p-4 animate-pulse">
        <div class="h-4 bg-slate-200 rounded w-3/4 mb-2"></div>
        <div class="h-3 bg-slate-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Calendar Grid - Week View -->
    <div v-else-if="currentView === 'week'" class="card overflow-hidden">
      <div class="grid grid-cols-7 border-b border-slate-200">
        <div
          v-for="day in weekDays"
          :key="day.date"
          class="p-3 text-center border-r border-slate-100 last:border-r-0"
          :class="day.isToday ? 'bg-primary/5' : ''"
        >
          <p class="text-xs font-medium text-slate-500 uppercase">{{ day.dayName }}</p>
          <p
            class="text-lg font-bold mt-1"
            :class="day.isToday ? 'text-primary' : 'text-gray-800'"
          >
            {{ day.dayNumber }}
          </p>
        </div>
      </div>
      <div class="grid grid-cols-7 min-h-[400px]">
        <div
          v-for="day in weekDays"
          :key="day.date"
          class="p-2 border-r border-slate-100 last:border-r-0"
          :class="day.isToday ? 'bg-primary/5' : ''"
        >
          <div v-if="day.appointments.length === 0" class="text-xs text-slate-400 p-2">
            {{ $t('calendar.no_appointments') }}
          </div>
          <div v-else class="space-y-1.5">
            <div
              v-for="apt in day.appointments"
              :key="apt.id"
              class="p-2 rounded-lg text-xs cursor-pointer transition-shadow hover:shadow-md"
              :class="getAppointmentClass(apt)"
              @click="openAppointment(apt)"
            >
              <p class="font-semibold text-white">{{ apt.time }}</p>
              <p class="text-white/90 truncate">{{ apt.patient_name }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Calendar Grid - Month View -->
    <div v-else class="card overflow-hidden">
      <div class="grid grid-cols-7 border-b border-slate-200">
        <div
          v-for="dayName in dayNames"
          :key="dayName"
          class="p-3 text-center text-xs font-medium text-slate-500 uppercase"
        >
          {{ dayName }}
        </div>
      </div>
      <div class="grid grid-cols-7">
        <div
          v-for="(day, index) in monthDays"
          :key="index"
          class="min-h-[80px] p-2 border-b border-r border-slate-100"
          :class="[
            day.isCurrentMonth ? 'bg-white' : 'bg-slate-50',
            day.isToday ? 'bg-primary/5' : '',
          ]"
        >
          <p
            class="text-xs font-medium mb-1"
            :class="day.isCurrentMonth ? 'text-gray-700' : 'text-slate-400'"
          >
            {{ day.dayNumber }}
          </p>
          <div class="space-y-1">
            <div
              v-for="apt in day.appointments.slice(0, 2)"
              :key="apt.id"
              class="p-1 rounded text-[10px] font-medium text-white truncate cursor-pointer"
              :class="getAppointmentBgClass(apt)"
              @click="openAppointment(apt)"
            >
              {{ apt.time }} {{ apt.patient_name }}
            </div>
            <p
              v-if="day.appointments.length > 2"
              class="text-[10px] text-slate-500 font-medium"
            >
              +{{ day.appointments.length - 2 }} {{ $t('calendar.more') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Appointment Detail Modal -->
    <Modal v-model="showDetail" :title="$t('calendar.appointment_details')" size="md">
      <div v-if="selectedAppointment" class="space-y-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
            <FontAwesomeIcon icon="fa-user" class="text-primary" />
          </div>
          <div>
            <h3 class="font-semibold text-lg">{{ selectedAppointment.patient_name }}</h3>
            <p class="text-sm text-slate-500">{{ selectedAppointment.phone }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="p-3 bg-slate-50 rounded-lg">
            <p class="text-xs text-slate-500 mb-1">{{ $t('calendar.date') }}</p>
            <p class="font-medium">{{ selectedAppointment.formatted_date }}</p>
          </div>
          <div class="p-3 bg-slate-50 rounded-lg">
            <p class="text-xs text-slate-500 mb-1">{{ $t('calendar.time') }}</p>
            <p class="font-medium">{{ selectedAppointment.time }}</p>
          </div>
        </div>

        <div v-if="selectedAppointment.notes" class="p-3 bg-slate-50 rounded-lg">
          <p class="text-xs text-slate-500 mb-1">{{ $t('patient.medical_notes') }}</p>
          <p class="text-sm">{{ selectedAppointment.notes }}</p>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showDetail = false">
          {{ $t('common.close') }}
        </button>
        <button type="button" class="btn-primary" @click="addToQueue(selectedAppointment)">
          {{ $t('queue.add_to_queue') }}
        </button>
      </template>
    </Modal>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import FontAwesomeIcon from '../components/FontAwesomeIcon.vue';
import Icon from '../components/Icon.vue';
import Modal from '../components/Modal.vue';
import api from '../utils/axios';

const { t } = useI18n();

const loading = ref(false);
const appointments = ref([]);
const currentView = ref('week');
const showDetail = ref(false);
const selectedAppointment = ref(null);

const currentDate = ref(new Date());

const views = [
  { key: 'week', label: t('calendar.week') },
  { key: 'month', label: t('calendar.month') },
];

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const weekStart = computed(() => {
  const date = new Date(currentDate.value);
  const day = date.getDay();
  const diff = date.getDate() - day;
  return new Date(date.setDate(diff));
});

const weekEnd = computed(() => {
  const date = new Date(weekStart.value);
  date.setDate(date.getDate() + 6);
  return date;
});

const weekStartFormatted = computed(() => {
  return weekStart.value.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});

const weekEndFormatted = computed(() => {
  return weekEnd.value.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
});

const weekDays = computed(() => {
  const days = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  for (let i = 0; i < 7; i++) {
    const date = new Date(weekStart.value);
    date.setDate(date.getDate() + i);
    const dateStr = formatDateKey(date);

    days.push({
      date: dateStr,
      dayNumber: date.getDate(),
      dayName: dayNames[i],
      isToday: date.getTime() === today.getTime(),
      appointments: appointments.value.filter(apt => apt.date === dateStr),
    });
  }
  return days;
});

const monthDays = computed(() => {
  const days = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const firstDay = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1);
  const lastDay = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 0);

  const startPadding = firstDay.getDay();
  for (let i = startPadding - 1; i >= 0; i--) {
    const date = new Date(firstDay);
    date.setDate(date.getDate() - i - 1);
    const dateStr = formatDateKey(date);
    days.push({
      date: dateStr,
      dayNumber: date.getDate(),
      isCurrentMonth: false,
      isToday: date.getTime() === today.getTime(),
      appointments: appointments.value.filter(apt => apt.date === dateStr),
    });
  }

  for (let i = 1; i <= lastDay.getDate(); i++) {
    const date = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), i);
    const dateStr = formatDateKey(date);
    days.push({
      date: dateStr,
      dayNumber: i,
      isCurrentMonth: true,
      isToday: date.getTime() === today.getTime(),
      appointments: appointments.value.filter(apt => apt.date === dateStr),
    });
  }

  const endPadding = 42 - days.length;
  for (let i = 1; i <= endPadding; i++) {
    const date = new Date(lastDay);
    date.setDate(date.getDate() + i);
    const dateStr = formatDateKey(date);
    days.push({
      date: dateStr,
      dayNumber: date.getDate(),
      isCurrentMonth: false,
      isToday: date.getTime() === today.getTime(),
      appointments: appointments.value.filter(apt => apt.date === dateStr),
    });
  }

  return days;
});

function formatDateKey(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatTime(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function getAppointmentClass(apt) {
  if (apt.queue_status === 'completed') {
    return 'bg-green-500 text-white';
  } else if (apt.queue_status === 'active') {
    return 'bg-blue-500 text-white';
  } else if (apt.queue_status === 'pending') {
    return 'bg-amber-500 text-white';
  }
  return 'bg-slate-500 text-white';
}

function getAppointmentBgClass(apt) {
  if (apt.queue_status === 'completed') {
    return 'bg-green-500';
  } else if (apt.queue_status === 'active') {
    return 'bg-blue-500';
  } else if (apt.queue_status === 'pending') {
    return 'bg-amber-500';
  }
  return 'bg-slate-500';
}

async function loadAppointments() {
  loading.value = true;
  try {
    const startDate = formatDateKey(weekStart.value);
    const endDate = formatDateKey(weekEnd.value);

    const { data } = await api.get('/patients', {
      params: {
        appointment_from: startDate,
        appointment_to: endDate,
        per_page: 100,
      },
    });

    appointments.value = (data.data || []).map(p => {
      const appointmentDate = new Date(p.appointment_date);
      return {
        id: p.id,
        patient_id: p.id,
        patient_name: p.name,
        phone: p.phone,
        date: formatDateKey(appointmentDate),
        time: formatTime(p.appointment_date),
        formatted_date: appointmentDate.toLocaleDateString('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric',
        }),
        queue_status: p.queue_status || 'pending',
        notes: p.medical_notes,
      };
    });
  } catch (e) {
    console.error('Failed to load appointments:', e);
  } finally {
    loading.value = false;
  }
}

function previousWeek() {
  const date = new Date(currentDate.value);
  date.setDate(date.getDate() - 7);
  currentDate.value = date;
  loadAppointments();
}

function nextWeek() {
  const date = new Date(currentDate.value);
  date.setDate(date.getDate() + 7);
  currentDate.value = date;
  loadAppointments();
}

function goToToday() {
  currentDate.value = new Date();
  loadAppointments();
}

function openAppointment(apt) {
  selectedAppointment.value = apt;
  showDetail.value = true;
}

function addToQueue(apt) {
  window.location.href = `/queue?add=${apt.patient_id}`;
}

onMounted(() => {
  loadAppointments();
});
</script>
