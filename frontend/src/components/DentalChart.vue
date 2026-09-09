<template>
  <div class="card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/70 px-5 py-4">
      <div class="min-w-0">
        <h3 class="font-semibold text-slate-900">{{ $t('patient.teeth_title') }}</h3>
        <p class="mt-0.5 text-xs text-slate-500">{{ $t('patient.teeth_hint') }}</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex rounded-lg border border-slate-200 bg-white p-0.5">
          <button
            v-for="notation in ['universal', 'fdi']" :key="notation"
            type="button"
            class="rounded-md px-2.5 py-1 text-xs font-medium transition"
            :class="currentNotation === notation
              ? 'bg-slate-800 text-white'
              : 'text-slate-600 hover:bg-slate-100'"
            @click="currentNotation = notation"
          >
            {{ $t('patient.notation_' + notation) }}
          </button>
        </div>
        <span v-if="saving" class="text-xs text-slate-400">{{ $t('patient.teeth_saving') }}</span>
        <span v-else-if="justSaved" class="text-xs font-medium text-emerald-600">
          ✓ {{ $t('patient.teeth_saved') }}
        </span>
        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">
          {{ $t('patient.teeth_charted', { n: chartedCount }) }}
        </span>
      </div>
    </div>

    <p v-if="loadError" class="px-5 py-6 text-sm text-red-600">{{ loadError }}</p>

    <div v-else dir="ltr" class="overflow-x-auto px-4 py-3">
      <div class="mb-1 flex min-w-[740px] items-center justify-between px-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
        <span>{{ $t('patient.quad_top_right') }} · {{ currentNotation === 'universal' ? '1–8' : '18–11' }}</span>
        <span>{{ $t('patient.quad_top_left') }} · {{ currentNotation === 'universal' ? '9–16' : '21–28' }}</span>
      </div>
      <svg viewBox="0 0 780 130" class="w-full min-w-[740px]" role="group" :aria-label="$t('patient.teeth_title')">
        <g v-for="t in LAYOUT" :key="t.n"
           role="button" tabindex="0"
           :aria-label="`${$t('patient.tooth')} ${getNotationLabel(t.n)}`"
           class="cursor-pointer transition-opacity hover:opacity-75 focus:outline-none"
           :transform="t.row === 'upper' ? `translate(${t.x}, ${t.y + t.h}) scale(1, -1)` : `translate(${t.x}, ${t.y})`"
           @click="select(t.n)"
           @keydown.enter.prevent="select(t.n)"
           @keydown.space.prevent="select(t.n)">
          <rect v-if="selected === t.n" x="-3.5" y="-3.5" :width="t.w + 7" :height="t.h + 7"
                rx="10" fill="none" stroke="#0f172a" stroke-opacity="0.35" stroke-width="1.5" />
          <path :d="PATHS[t.type]"
                :fill="rec(t.n) ? META[rec(t.n).status].fill : META.healthy.fill"
                :stroke="rec(t.n) ? META[rec(t.n).status].stroke : META.healthy.stroke"
                :stroke-width="selected === t.n ? 2.5 : 2"
                stroke-linejoin="round"
                :stroke-dasharray="rec(t.n)?.status === 'missing' ? '5 3' : undefined" />
          <template v-if="rec(t.n)">
            <circle v-if="rec(t.n).status === 'cavity'" :cx="t.w / 2" cy="8" r="3.5"
                    :fill="META.cavity.stroke" opacity="0.85" />
            <rect v-else-if="rec(t.n).status === 'filled'" :x="t.w / 2 - 5" y="4"
                  width="10" height="6" rx="1.5" :fill="META.filled.stroke" opacity="0.8" />
            <line v-else-if="rec(t.n).status === 'root_canal'" :x1="t.w / 2" y1="10"
                  :x2="t.w / 2" :y2="t.h - 8" :stroke="META.root_canal.stroke"
                  stroke-width="2" opacity="0.8" />
            <g v-else-if="rec(t.n).status === 'implant'" :stroke="META.implant.stroke"
               stroke-width="1.6" opacity="0.9">
              <line :x1="t.w / 2" y1="8" :x2="t.w / 2" :y2="t.h - 5" />
              <line :x1="t.w / 2 - 4" :y1="t.h - 14" :x2="t.w / 2 + 4" :y2="t.h - 14" />
              <line :x1="t.w / 2 - 3" :y1="t.h - 10" :x2="t.w / 2 + 3" :y2="t.h - 10" />
            </g>
            <g v-else-if="rec(t.n).status === 'missing'" stroke="#64748b" stroke-width="2.2" stroke-linecap="round">
              <line x1="5" y1="6" :x2="t.w - 5" :y2="t.h - 6" />
              <line :x1="t.w - 5" y1="6" x2="5" :y2="t.h - 6" />
            </g>
            <circle v-else-if="rec(t.n).status === 'previous_visit'" :cx="t.w / 2" :cy="t.h - 9" r="3"
                    :fill="META.previous_visit.stroke" opacity="0.8" />
          </template>
          <template v-if="rec(t.n)?.surfaces?.length">
            <g :transform="`translate(${t.w/2 - 12}, ${t.h - 6})`">
              <rect v-for="(s, si) in rec(t.n).surfaces" :key="s"
                    :x="si * 11" y="0" width="10" height="8" rx="1.5"
                    :fill="SURFACE_META[s].fill" :stroke="SURFACE_META[s].stroke"
                    stroke-width="0.8" />
              <text v-for="(s, si) in rec(t.n).surfaces" :key="`s-${s}`"
                    :x="si * 11 + 5" y="6" text-anchor="middle"
                    font-size="5" font-weight="600" fill="white">{{ s }}</text>
            </g>
          </template>
        </g>
        <text v-for="t in LAYOUT" :key="`n-${t.n}`"
              :x="t.x + t.w / 2" :y="t.row === 'upper' ? 60 : 78"
              text-anchor="middle" font-size="9"
              :font-weight="selected === t.n ? 700 : 400"
              :fill="rec(t.n) ? META[rec(t.n).status].stroke : '#94a3b8'"
              font-family="ui-monospace, SFMono-Regular, monospace">{{ getNotationLabel(t.n) }}</text>
      </svg>

      <div class="mt-1 flex min-w-[740px] items-center justify-between px-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
        <span>{{ $t('patient.quad_bottom_right') }} · {{ currentNotation === 'universal' ? '32–25' : '48–41' }}</span>
        <span>{{ $t('patient.quad_bottom_left') }} · {{ currentNotation === 'universal' ? '24–17' : '38–31' }}</span>
      </div>
    </div>

    <div class="flex flex-wrap gap-x-4 gap-y-1.5 border-t border-slate-100 px-5 py-3">
      <span v-for="s in LEGEND" :key="s" class="inline-flex items-center gap-1.5 text-xs text-slate-600">
        <span class="h-2.5 w-2.5 rounded-full"
              :style="{ backgroundColor: META[s].fill, boxShadow: `inset 0 0 0 2px ${META[s].stroke}` }" />
        {{ $t('patient.status_' + s) }}
        <span v-if="countByStatus[s]" class="tabular-nums text-slate-400">· {{ countByStatus[s] }}</span>
      </span>
    </div>

    <div v-if="selected" class="border-t border-slate-100 bg-slate-50/60 px-5 py-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm font-semibold text-slate-900">
          {{ $t('patient.tooth') }} #{{ selected }}
          <span class="ml-1 text-xs font-normal text-slate-400">{{ $t('patient.tooth_' + typeOf(selected)) }}</span>
          <span v-if="rec(selected)"
                class="ml-2 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                :style="{ backgroundColor: META[rec(selected).status].fill, color: META[rec(selected).status].stroke }">
            {{ $t('patient.status_' + rec(selected).status) }}
          </span>
        </p>
        <div class="flex items-center gap-3">
          <button type="button" class="text-xs text-primary hover:text-primary/80 font-medium"
                  @click="showHistory = !showHistory">
            {{ $t('patient.treatment_history') }}
          </button>
          <button type="button" class="text-xs text-slate-400 hover:text-slate-600"
                  @click="closeEditor">{{ $t('patient.teeth_close') }}</button>
        </div>
      </div>

      <div v-if="showHistory" class="mt-3 rounded-lg border border-slate-200 bg-white p-3">
        <p class="text-xs font-semibold text-slate-500 mb-2">{{ $t('patient.treatment_history') }}</p>
        <div v-if="toothHistory.length" class="space-y-2">
          <div v-for="h in toothHistory" :key="h.id" class="flex items-center justify-between text-xs">
            <div>
              <span class="font-medium text-slate-700">{{ h.procedure || $t('patient.status_' + h.status) }}</span>
              <span v-if="h.surfaces?.length" class="ml-1 text-slate-400">({{ h.surfaces.join(', ') }})</span>
            </div>
            <div class="text-slate-400">{{ formatDate(h.created_at) }}</div>
          </div>
        </div>
        <p v-else class="text-xs text-slate-400">{{ $t('patient.no_treatment_history') }}</p>
      </div>

      <div class="mt-3 flex flex-wrap gap-2">
        <button v-for="s in STATUSES" :key="s" type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold transition"
                :class="draftStatus === s
                          ? 'border-transparent text-white'
                          : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                :style="draftStatus === s ? { backgroundColor: META[s].stroke } : {}"
                @click="draftStatus = s">
          <span class="h-2 w-2 rounded-full"
                :style="{ backgroundColor: META[s].fill, boxShadow: `inset 0 0 0 2px ${META[s].stroke}` }" />
          {{ $t('patient.status_' + s) }}
        </button>
      </div>

      <div v-if="draftStatus === 'cavity' || draftStatus === 'filled'" class="mt-3">
        <p class="text-xs font-medium text-slate-600 mb-1.5">{{ $t('patient.select_surfaces') }}</p>
        <div class="flex flex-wrap gap-1.5">
          <button v-for="s in getAvailableSurfaces(selected)" :key="s" type="button"
                  class="inline-flex items-center justify-center rounded-lg border px-2 py-1 text-xs font-semibold transition"
                  :class="draftSurfaces.includes(s)
                            ? 'border-transparent text-white'
                            : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400'"
                  :style="draftSurfaces.includes(s) ? { backgroundColor: SURFACE_META[s].stroke } : {}"
                  @click="toggleSurface(s)">
            {{ $t('patient.surface_' + s.toLowerCase()) }}
          </button>
        </div>
      </div>

      <div class="mt-3 flex flex-wrap items-center gap-2">
        <input v-model="draftNote" class="field min-w-[200px] flex-1 !py-1.5 text-sm"
               :placeholder="$t('patient.teeth_note_ph')"
               @keydown.enter.prevent="save" />
        <button type="button" class="btn-primary btn-sm" :disabled="saving || !draftStatus" @click="save">
          {{ $t('patient.teeth_save') }}
        </button>
        <button type="button" class="btn-ghost btn-sm" :disabled="saving" @click="clearTooth">
          {{ $t('patient.teeth_clear') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';

const props = defineProps({
  patientId: { type: Number, required: true },
});

const { t } = useI18n();

const PATHS = {
  molar: 'M0,8 Q0,3 5,4.5 Q8,-1 12,3 Q15,-1.5 19,3 Q23,-1.5 26,3 Q30,-1 33,4.5 Q38,3 38,8 L38,24 Q38,34 28,34 L10,34 Q0,34 0,24 Z',
  premolar: 'M0,8 Q0,3 4.5,4.5 Q8,-1 12,3.5 Q16.5,-1.5 21,3.5 Q25,-1 28.5,4.5 Q33,3 33,8 L33,21 Q33,31 24,31 L9,31 Q0,31 0,21 Z',
  canine: 'M0,10 Q0,4 4,5.5 Q8,0 12,4 Q16,0 20,5.5 Q24,4 24,10 L24,20 Q24,26 17,28 Q14,37 12,37 Q10,37 7,28 Q0,26 0,20 Z',
  incisor: 'M0,7 Q0,2 4.5,3.5 Q14,-1.5 23.5,3.5 Q28,2 28,7 L28,19 Q28,27 19,29 Q16,31 14,31 Q12,31 9,29 Q0,27 0,19 Z',
};
const SIZES = { molar: [38, 34], premolar: [33, 31], canine: [24, 37], incisor: [28, 31] };

const SURFACE_META = {
  M: { fill: '#ef4444', stroke: '#b91c1c' },
  O: { fill: '#f59e0b', stroke: '#b45309' },
  D: { fill: '#3b82f6', stroke: '#1d4ed8' },
  B: { fill: '#10b981', stroke: '#047857' },
  L: { fill: '#8b5cf6', stroke: '#6d28d9' },
  F: { fill: '#10b981', stroke: '#047857' },
  I: { fill: '#f59e0b', stroke: '#b45309' },
};

const ANTERIOR_SURFACES = ['M', 'I', 'D', 'B', 'L', 'F'];
const POSTERIOR_SURFACES = ['M', 'O', 'D', 'B', 'L'];

const typeOf = (n) => {
  const q = (n <= 16 ? n - 1 : n - 17) % 8;
  return q <= 2 ? 'molar' : q <= 4 ? 'premolar' : q === 5 ? 'canine' : 'incisor';
};

const toFDI = (universal) => {
  if (universal >= 1 && universal <= 8) return 19 - universal;
  if (universal >= 9 && universal <= 16) return 37 - universal;
  if (universal >= 17 && universal <= 24) return 30 + (25 - universal);
  return 40 + (41 - universal);
};

const getNotationLabel = (n) => {
  return currentNotation.value === 'fdi' ? toFDI(n) : n;
};

const getAvailableSurfaces = (n) => {
  return typeOf(n) === 'incisor' || typeOf(n) === 'canine' ? ANTERIOR_SURFACES : POSTERIOR_SURFACES;
};

const LAYOUT = (() => {
  const cells = [];
  const cellW = 46;
  const startX = 17;
  const rows = {
    upper: Array.from({ length: 16 }, (_, i) => i + 1),
    lower: Array.from({ length: 16 }, (_, i) => 32 - i),
  };
  for (const [row, nums] of Object.entries(rows)) {
    nums.forEach((n, i) => {
      const type = typeOf(n);
      const [w, h] = SIZES[type];
      cells.push({ n, type, row, w, h, x: startX + i * cellW + (cellW - w) / 2, y: row === 'upper' ? 8 : 86 });
    });
  }
  return cells;
})();

const STATUSES = ['cavity', 'filled', 'crown', 'root_canal', 'missing', 'implant', 'previous_visit'];
const LEGEND = ['healthy', ...STATUSES];
const META = {
  healthy:        { stroke: '#16a34a', fill: '#f0fdf4' },
  cavity:         { stroke: '#dc2626', fill: '#fee2e2' },
  filled:         { stroke: '#ea580c', fill: '#ffedd5' },
  crown:          { stroke: '#7c3aed', fill: '#ddd6fe' },
  root_canal:     { stroke: '#4338ca', fill: '#e0e7ff' },
  missing:        { stroke: '#94a3b8', fill: '#f1f5f9' },
  implant:        { stroke: '#2563eb', fill: '#dbeafe' },
  previous_visit: { stroke: '#ca8a04', fill: '#fef9c3' },
};

const records     = ref({});
const historyMap  = ref({});
const loadError   = ref('');
const saving      = ref(false);
const justSaved   = ref(false);
const selected    = ref(null);
const draftStatus = ref(null);
const draftSurfaces = ref([]);
const draftNote   = ref('');
const showHistory = ref(false);
const currentNotation = ref('universal');

const rec = (n) => records.value[n] || null;
const chartedCount = computed(() => Object.keys(records.value).length);
const countByStatus = computed(() => {
  const counts = { healthy: 32 - chartedCount.value };
  for (const r of Object.values(records.value)) {
    counts[r.status] = (counts[r.status] || 0) + 1;
  }
  return counts;
});
const toothHistory = computed(() => selected.value ? (historyMap.value[selected.value] || []) : []);

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString();
};

watch(selected, async (n) => {
  if (n) {
    showHistory.value = false;
    try {
      const { data } = await api.get(`/patients/${props.patientId}/teeth/${n}/history`);
      historyMap.value[n] = data || [];
    } catch {
      historyMap.value[n] = [];
    }
  }
});

onMounted(async () => {
  try {
    const { data } = await api.get(`/patients/${props.patientId}/teeth`);
    records.value = Object.fromEntries(
      (data || []).map((r) => [r.tooth_number, { status: r.status, note: r.note || '', surfaces: r.surfaces || [] }]),
    );
  } catch {
    loadError.value = t('patient.teeth_load_error');
  }
});

function select(n) {
  if (selected.value === n) { closeEditor(); return; }
  selected.value = n;
  draftStatus.value = rec(n)?.status ?? null;
  draftSurfaces.value = rec(n)?.surfaces ? [...rec(n).surfaces] : [];
  draftNote.value = rec(n)?.note ?? '';
  showHistory.value = false;
}

function closeEditor() {
  selected.value = null;
  draftStatus.value = null;
  draftSurfaces.value = [];
  draftNote.value = '';
  showHistory.value = false;
}

function toggleSurface(s) {
  const idx = draftSurfaces.value.indexOf(s);
  if (idx === -1) draftSurfaces.value.push(s);
  else draftSurfaces.value.splice(idx, 1);
}

async function save() {
  const next = { ...records.value };
  if (draftStatus.value) {
    next[selected.value] = {
      status: draftStatus.value,
      note: draftNote.value.trim(),
      surfaces: (draftStatus.value === 'cavity' || draftStatus.value === 'filled') ? [...draftSurfaces.value] : [],
    };
  } else {
    delete next[selected.value];
  }
  await persist(next);
}

async function clearTooth() {
  const next = { ...records.value };
  delete next[selected.value];
  draftStatus.value = null;
  draftSurfaces.value = [];
  draftNote.value = '';
  await persist(next);
}

async function persist(next) {
  saving.value = true;
  try {
    const { data } = await api.put(`/patients/${props.patientId}/teeth`, {
      teeth: Object.entries(next).map(([num, r]) => ({
        tooth_number: Number(num),
        status: r.status,
        note: r.note || null,
        surfaces: r.surfaces || [],
      })),
    });
    records.value = Object.fromEntries(
      (data || []).map((r) => [r.tooth_number, { status: r.status, note: r.note || '', surfaces: r.surfaces || [] }]),
    );
    justSaved.value = true;
    setTimeout(() => { justSaved.value = false; }, 2500);
  } finally {
    saving.value = false;
  }
}
</script>
