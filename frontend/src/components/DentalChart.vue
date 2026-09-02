<template>
  <div class="card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/70 px-5 py-4">
      <div class="min-w-0">
        <h3 class="font-semibold text-slate-900">{{ $t('patient.teeth_title') }}</h3>
        <p class="mt-0.5 text-xs text-slate-500">{{ $t('patient.teeth_hint') }}</p>
      </div>
      <div class="flex items-center gap-2">
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
      <div class="mb-1 flex min-w-[740px] items-center justify-between px-2
                  text-[11px] font-semibold uppercase tracking-wide text-slate-400">
        <span>{{ $t('patient.quad_top_right') }} · 1–8</span>
        <span>{{ $t('patient.quad_top_left') }} · 9–16</span>
      </div>
      <svg viewBox="0 0 780 130" class="w-full min-w-[740px]" role="group" :aria-label="$t('patient.teeth_title')">
        <g v-for="t in LAYOUT" :key="t.n"
           role="button" tabindex="0"
           :aria-label="`${$t('patient.tooth')} ${t.n}`"
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
        </g>
        <text v-for="t in LAYOUT" :key="`n-${t.n}`"
              :x="t.x + t.w / 2" :y="t.row === 'upper' ? 60 : 78"
              text-anchor="middle" font-size="9"
              :font-weight="selected === t.n ? 700 : 400"
              :fill="rec(t.n) ? META[rec(t.n).status].stroke : '#94a3b8'"
              font-family="ui-monospace, SFMono-Regular, monospace">{{ t.n }}</text>
      </svg>

      <div class="mt-1 flex min-w-[740px] items-center justify-between px-2
                  text-[11px] font-semibold uppercase tracking-wide text-slate-400">
        <span>{{ $t('patient.quad_bottom_right') }} · 32–25</span>
        <span>{{ $t('patient.quad_bottom_left') }} · 24–17</span>
      </div>
<!-- PART3 -->
    </div>

    <!-- legend -->
    <div class="flex flex-wrap gap-x-4 gap-y-1.5 border-t border-slate-100 px-5 py-3">
      <span v-for="s in LEGEND" :key="s" class="inline-flex items-center gap-1.5 text-xs text-slate-600">
        <span class="h-2.5 w-2.5 rounded-full"
              :style="{ backgroundColor: META[s].fill, boxShadow: `inset 0 0 0 2px ${META[s].stroke}` }" />
        {{ $t('patient.status_' + s) }}
        <span v-if="countByStatus[s]" class="tabular-nums text-slate-400">· {{ countByStatus[s] }}</span>
      </span>
    </div>

    <!-- tooth editor -->
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
        <button type="button" class="text-xs text-slate-400 hover:text-slate-600"
                @click="closeEditor">{{ $t('patient.teeth_close') }}</button>
      </div>
<!-- PART4 -->
      <div class="mt-3 flex flex-wrap gap-2">
        <button v-for="s in STATUSES" :key="s" type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5
                       text-xs font-semibold transition"
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
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';

const props = defineProps({
  patientId: { type: Number, required: true },
});

const { t } = useI18n();

/* ---- chart geometry (universal numbering, patient's right on the viewer's left) ---- */
const PATHS = {
  // molar (38×34) — wide crown, 3 cusps
  molar: 'M0,8 Q0,3 5,4.5 Q8,-1 12,3 Q15,-1.5 19,3 Q23,-1.5 26,3 Q30,-1 33,4.5 Q38,3 38,8 L38,24 Q38,34 28,34 L10,34 Q0,34 0,24 Z',
  // premolar (33×31) — 2–3 cusps
  premolar: 'M0,8 Q0,3 4.5,4.5 Q8,-1 12,3.5 Q16.5,-1.5 21,3.5 Q25,-1 28.5,4.5 Q33,3 33,8 L33,21 Q33,31 24,31 L9,31 Q0,31 0,21 Z',
  // canine (24×37) — pointed root tip
  canine: 'M0,10 Q0,4 4,5.5 Q8,0 12,4 Q16,0 20,5.5 Q24,4 24,10 L24,20 Q24,26 17,28 Q14,37 12,37 Q10,37 7,28 Q0,26 0,20 Z',
  // incisor (28×31) — flat edge, narrow root
  incisor: 'M0,7 Q0,2 4.5,3.5 Q14,-1.5 23.5,3.5 Q28,2 28,7 L28,19 Q28,27 19,29 Q16,31 14,31 Q12,31 9,29 Q0,27 0,19 Z',
};
const SIZES = { molar: [38, 34], premolar: [33, 31], canine: [24, 37], incisor: [28, 31] };

/** Quadrant position 0..7 (0 = third molar … 7 = central incisor). */
const typeOf = (n) => {
  const q = (n <= 16 ? n - 1 : n - 17) % 8;
  return q <= 2 ? 'molar' : q <= 4 ? 'premolar' : q === 5 ? 'canine' : 'incisor';
};
const LAYOUT = (() => {
  const cells = [];
  const cellW = 46;
  const startX = 17;
  const rows = {
    upper: Array.from({ length: 16 }, (_, i) => i + 1),  // 1..16, viewer left→right
    lower: Array.from({ length: 16 }, (_, i) => 32 - i), // 32..17, viewer left→right
  };
  for (const [row, nums] of Object.entries(rows)) {
    nums.forEach((n, i) => {
      const type = typeOf(n);
      const [w, h] = SIZES[type];
      cells.push({
        n, type, row, w, h,
        x: startX + i * cellW + (cellW - w) / 2,
        y: row === 'upper' ? 8 : 86,
      });
    });
  }
  return cells;
})();

/* ---- statuses ---- */
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

/* ---- state ---- */
const records   = ref({}); // tooth_number → { status, note } (uncharted = healthy)
const loadError = ref('');
const saving    = ref(false);
const justSaved = ref(false);
const selected  = ref(null); // tooth number under edit
const draftStatus = ref(null);
const draftNote   = ref('');

const rec = (n) => records.value[n] || null;
const chartedCount = computed(() => Object.keys(records.value).length);
const countByStatus = computed(() => {
  const counts = { healthy: 32 - chartedCount.value };
  for (const r of Object.values(records.value)) {
    counts[r.status] = (counts[r.status] || 0) + 1;
  }
  return counts;
});

onMounted(async () => {
  try {
    const { data } = await api.get(`/patients/${props.patientId}/teeth`);
    records.value = Object.fromEntries(
      (data || []).map((r) => [r.tooth_number, { status: r.status, note: r.note || '' }]),
    );
  } catch {
    loadError.value = t('patient.teeth_load_error');
  }
});

function select(n) {
  if (selected.value === n) {
    closeEditor();
    return;
  }
  selected.value = n;
  draftStatus.value = rec(n)?.status ?? null;
  draftNote.value = rec(n)?.note ?? '';
}

function closeEditor() {
  selected.value = null;
  draftStatus.value = null;
  draftNote.value = '';
}

async function save() {
  const next = { ...records.value };
  if (draftStatus.value) {
    next[selected.value] = { status: draftStatus.value, note: draftNote.value.trim() };
  } else {
    delete next[selected.value];
  }
  await persist(next);
}

async function clearTooth() {
  const next = { ...records.value };
  delete next[selected.value];
  draftStatus.value = null;
  draftNote.value = '';
  await persist(next);
}

/** Full-chart sync: the server deletes rows missing from the payload. */
async function persist(next) {
  saving.value = true;
  try {
    const { data } = await api.put(`/patients/${props.patientId}/teeth`, {
      teeth: Object.entries(next).map(([num, r]) => ({
        tooth_number: Number(num),
        status: r.status,
        note: r.note || null,
      })),
    });
    records.value = Object.fromEntries(
      (data || []).map((r) => [r.tooth_number, { status: r.status, note: r.note || '' }]),
    );
    justSaved.value = true;
    setTimeout(() => { justSaved.value = false; }, 2500);
  } finally {
    saving.value = false;
  }
}
</script>
