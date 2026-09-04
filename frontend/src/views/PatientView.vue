<template>
  <section v-if="patient">
    <router-link to="/patients"
                 class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600
                         hover:text-indigo-700 hover:underline">
      <span aria-hidden="true">←</span> {{ $t('common.back_to_patients') }}
    </router-link>

    <!-- Profile header -->
    <div class="card mt-3 border border-slate-200 px-4 py-4">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex min-w-0 items-start gap-3">
          <div class="grid h-14 w-14 shrink-0 place-items-center rounded-xl text-xl font-bold"
               :class="severeAllergy ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700'">
            {{ initials }}
          </div>
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-xl font-bold text-slate-900">{{ patient.name }}</h2>
              <span v-if="severeAllergy" class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">
                ⚠ Allergies
              </span>
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
              <span v-if="patient.patient_code" class="font-mono text-slate-400">{{ patient.patient_code }}</span>
              <span v-if="patient.gender">{{ patient.gender === 'female' ? '♀' : '♂' }}</span>
              <span v-if="patient.age">{{ patient.age }} yrs</span>
              <span>📅 {{ daysAsPatient }} days as patient</span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <!-- Appointment countdown -->
          <div v-if="appointmentCountdown !== null" class="rounded-lg px-3 py-2 text-center" :class="appointmentCountdown <= 1 ? 'bg-red-50 border border-red-200' : 'bg-indigo-50 border border-indigo-200'">
            <div class="text-lg font-bold" :class="appointmentCountdown <= 1 ? 'text-red-700' : 'text-indigo-700'">
              {{ appointmentCountdown === 0 ? 'Today!' : appointmentCountdown + 'd' }}
            </div>
            <div class="text-[10px]" :class="appointmentCountdown <= 1 ? 'text-red-500' : 'text-indigo-500'">until appt</div>
          </div>
          <div class="rounded-lg bg-indigo-50 px-3 py-2 text-center">
            <div class="text-lg font-bold text-indigo-700">{{ totalVisits }}</div>
            <div class="text-[10px] text-indigo-500">Visits</div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
        <button v-if="can('queue.manage')" type="button" class="btn-success btn-sm" @click="addToQueue">
          <Icon name="plus" :size="14" /> Add to Queue
        </button>
        <button v-if="can('patients.edit')" type="button" class="btn-ghost btn-sm" @click="openEdit">
          <Icon name="edit" :size="14" /> Edit
        </button>
        <button v-if="can('patients.delete')" type="button" class="btn-ghost btn-sm text-red-500" @click="askDeletePatient">
          <Icon name="trash" :size="14" />
        </button>
        <a v-if="patient.phone" :href="formatPhoneForWhatsApp(patient.phone)" target="_blank" rel="noopener noreferrer"
           class="btn-ghost btn-sm">
          💬 WhatsApp
        </a>
        <button v-if="patient.phone && upcomingFollowup" type="button" class="btn-ghost btn-sm" @click="callPatient">
          📞 Call
        </button>
      </div>
    </div>

    <!-- Alerts -->
    <div v-if="patient.outstanding_short_term_debt > 0 || upcomingFollowup" class="mt-3 flex flex-wrap items-center gap-2">
      <p v-if="patient.outstanding_short_term_debt > 0"
         class="inline-flex items-center gap-1 rounded-full border border-red-300 bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">
        Outstanding: {{ format(patient.outstanding_short_term_debt) }} {{ $t('currency') }}
      </p>
      <p v-if="upcomingFollowup"
         class="inline-flex items-center gap-1 rounded-full border border-indigo-300 bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
        📅 Next: {{ formatDateTime(patient.appointment_date) }}
      </p>
      <a v-if="patient.phone && upcomingFollowup"
         :href="reminderWhatsAppLink"
         target="_blank" rel="noopener noreferrer"
         class="inline-flex items-center gap-1 rounded-full border border-green-300 bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700 hover:bg-green-100 transition-colors">
        💬 Send Reminder
      </a>
    </div>

    <!-- Quick stats - each as separate card -->
    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
      <div class="card border border-slate-200 px-3 py-3 text-center">
        <div class="text-xs text-slate-500">Last Visit</div>
        <div class="mt-1 text-sm font-medium text-slate-900">{{ lastVisitDisplay || '—' }}</div>
      </div>
      <div class="card border border-slate-200 px-3 py-3 text-center">
        <div class="text-xs text-slate-500">Total Paid</div>
        <div class="mt-1 font-mono text-sm font-semibold text-emerald-700">{{ format(totalPaid) }}</div>
      </div>
      <div class="card border border-slate-200 px-3 py-3 text-center">
        <div class="text-xs text-slate-500">Outstanding</div>
        <div class="mt-1 font-mono text-sm font-semibold text-red-700">{{ format(patient.outstanding_short_term_debt || 0) }}</div>
      </div>
      <div class="card border border-slate-200 px-3 py-3 text-center">
        <div class="text-xs text-slate-500">Debt</div>
        <div class="mt-1 font-mono text-sm font-semibold text-amber-700">{{ format(patient.long_term_debt || 0) }}</div>
      </div>
    </div>

    <!-- Conditions -->
    <div class="card mt-3 border border-slate-200 overflow-hidden">
      <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
        <h3 class="font-semibold text-slate-900">Conditions & Allergies</h3>
      </div>
      <ul v-if="conditions.length" class="divide-y divide-slate-100">
        <li v-for="c in conditions" :key="c.id" class="flex items-center gap-3 px-4 py-2.5">
          <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs"
                :class="c.severity === 'severe' ? 'bg-red-100 text-red-700' : c.severity === 'moderate' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'">
            {{ c.type === 'allergy' ? '⚠' : '🩺' }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-900">{{ c.name }}</p>
            <p v-if="c.note" class="text-xs text-slate-500">{{ c.note }}</p>
          </div>
          <span class="text-[10px] font-semibold uppercase" :class="c.severity === 'severe' ? 'text-red-600' : c.severity === 'moderate' ? 'text-amber-600' : 'text-slate-500'">
            {{ c.severity }}
          </span>
        </li>
      </ul>
      <p v-else class="px-4 py-6 text-sm text-slate-400">No conditions recorded</p>
      <div v-if="can('patients.edit')" class="border-t border-slate-100 px-4 py-2">
        <button type="button" class="btn-ghost btn-sm w-full" @click="openConditions">
          <Icon name="edit" :size="12" /> Manage
        </button>
      </div>
    </div>

    <!-- Patient Details -->
    <div class="card mt-3 border border-slate-200 overflow-hidden">
      <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
        <h3 class="font-semibold text-slate-900">Contact Info</h3>
      </div>
      <dl class="divide-y divide-slate-100 text-sm">
        <div class="flex items-center justify-between px-4 py-2.5">
          <dt class="text-slate-500">Phone</dt>
          <dd class="font-mono text-slate-900">
            <a v-if="patient.phone" :href="formatPhoneForWhatsApp(patient.phone)" target="_blank" rel="noopener noreferrer"
               class="text-indigo-600 hover:underline">
              {{ formatPhoneForDisplay(patient.phone) }}
            </a>
            <span v-else class="text-slate-400">—</span>
          </dd>
        </div>
        <div class="flex items-center justify-between px-4 py-2.5">
          <dt class="text-slate-500">Address</dt>
          <dd class="text-slate-900 text-right max-w-xs">{{ patient.address || '—' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-2.5">
          <dt class="text-slate-500">Age</dt>
          <dd class="text-slate-900">{{ patient.age ? `${patient.age} years` : '—' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-2.5">
          <dt class="text-slate-500">Gender</dt>
          <dd class="text-slate-900">{{ patient.gender ? $t('patient.gender_' + patient.gender) : '—' }}</dd>
        </div>
        <div v-if="patient.medical_notes" class="px-4 py-2.5">
          <dt class="mb-1 text-slate-500">Medical Notes</dt>
          <dd class="text-slate-900">{{ patient.medical_notes }}</dd>
        </div>
      </dl>
    </div>

    <!-- Payment Plans -->
    <div v-if="patient.aqsat_contracts?.length" class="card mt-3 border border-slate-200 overflow-hidden">
      <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
        <h3 class="font-semibold text-slate-900">Payment Plans</h3>
      </div>
      <div class="divide-y divide-slate-100">
        <div v-for="c in patient.aqsat_contracts" :key="c.id" class="flex items-center justify-between px-4 py-3">
          <div>
            <p class="font-medium text-slate-900">{{ c.treatment_name }}</p>
            <p class="text-xs text-slate-500">{{ c.status }}</p>
          </div>
          <div class="text-right">
            <p class="font-mono text-sm font-semibold text-slate-900">{{ format(c.remaining_balance) }}</p>
            <p class="text-xs text-slate-400">of {{ format(c.total_amount) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Dental chart -->
    <div class="card mt-3 border border-slate-200 p-4">
      <h3 class="mb-3 font-semibold text-slate-900">Dental Chart</h3>
      <DentalChart v-if="patient" :patient-id="patient.id" />
    </div>

    <!-- X-ray -->
    <div v-if="activeVisit" class="card mt-3 border border-slate-200 p-4">
      <h3 class="mb-3 font-semibold text-slate-900">X-Ray</h3>
      <label
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 px-4 py-6 text-center transition-colors hover:border-indigo-400 hover:bg-indigo-50/40"
      >
        <span class="text-2xl">📷</span>
        <span class="text-sm font-medium text-slate-700">{{ $t('visit.upload_xray') }}</span>
        <input type="file" accept="image/*" capture="environment" class="sr-only" :disabled="uploading" @change="uploadXray($event, activeVisit)" />
      </label>
      <img v-if="activeVisit.xray_path" :src="xrayUrl(activeVisit.xray_path)" :alt="$t('visit.xray')" class="mt-3 max-h-48 rounded-lg border" />
    </div>

    <!-- Visits Timeline -->
    <div class="card mt-3 border border-slate-200 overflow-hidden">
      <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 flex items-center justify-between">
        <h3 class="font-semibold text-slate-900">Visit History</h3>
        <span class="text-xs text-slate-500">{{ patient.visits?.length || 0 }} visits</span>
      </div>
      <div class="p-4">
        <div v-if="!patient.visits?.length" class="text-center py-8 text-slate-400">
          No visits yet
        </div>
        <div v-else class="relative">
          <!-- Timeline line -->
          <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-200"></div>

          <!-- Timeline items -->
          <div class="space-y-5">
            <div v-for="visit in sortedVisits" :key="visit.id" class="relative pl-10">
              <!-- Timeline dot -->
              <div class="absolute left-3 top-1 h-3.5 w-3.5 rounded-full border-2 border-white"
                   :class="visit.queue_status === 'completed' ? 'bg-emerald-500' : visit.queue_status === 'active' ? 'bg-indigo-500' : 'bg-slate-400'">
              </div>

              <div class="flex flex-wrap items-start justify-between gap-3 rounded-lg border border-slate-100 bg-white p-3 hover:border-slate-200 transition-colors">
                <div class="flex-1 min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <span v-if="visit.treatment_name"
                          class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700">
                      {{ visit.treatment_name }}
                    </span>
                    <StatusBadge kind="queue_status" :value="visit.queue_status" />
                  </div>
                  <p class="mt-1 text-xs text-slate-500">
                    {{ formatDateTime(visit.created_at) }}
                    <span class="ml-1 text-slate-400">({{ relativeTime(visit.created_at) }})</span>
                    <span v-if="visit.visit_type" class="ml-2 text-slate-400">
                      · {{ visit.visit_type }}
                    </span>
                  </p>
                  <p v-if="visit.treatment_notes" class="mt-2 text-sm text-slate-600 bg-slate-50 rounded p-2">
                    📝 {{ visit.treatment_notes }}
                  </p>
                </div>
                <div class="text-right min-w-[100px]">
                  <p class="font-mono text-sm font-medium text-slate-900">{{ format(visit.total_cost) }}</p>
                  <p class="text-xs" :class="visit.amount_paid >= visit.total_cost ? 'text-emerald-600' : 'text-amber-600'">
                    Paid: {{ format(visit.amount_paid) }}
                  </p>
                  <p v-if="visit.short_term_debt > 0" class="text-xs text-red-600 font-medium">
                    Due: {{ format(visit.short_term_debt) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Treatment Gallery -->
    <div class="card mt-3 border border-slate-200 overflow-hidden">
      <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 flex items-center justify-between">
        <h3 class="font-semibold text-slate-900">Treatment Gallery</h3>
        <button v-if="can('patients.edit')" type="button" class="btn-ghost btn-sm" @click="openGallery">
          <Icon name="plus" :size="14" /> Add Photos
        </button>
      </div>
      <div class="p-4">
        <div v-if="!galleryPhotos.length" class="text-center py-8">
          <span class="text-4xl">📸</span>
          <p class="mt-2 text-sm text-slate-500">No treatment photos yet</p>
        </div>
        <div v-else class="grid grid-cols-3 gap-2">
          <div v-for="photo in galleryPhotos" :key="photo.id" class="relative aspect-square rounded-lg overflow-hidden border border-slate-200">
            <img :src="photoUrl(photo.path)" :alt="photo.note || 'Treatment photo'"
                 class="h-full w-full object-cover cursor-pointer hover:opacity-90 transition-opacity"
                 @click="openPhotoDetail(photo)" />
            <div v-if="photo.type" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2">
              <span class="text-white text-xs">{{ photo.type }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit patient -->
    <Modal v-model="showEdit" :title="$t('common.edit')" size="md">
      <div class="space-y-4">
        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
          <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-indigo-100 text-sm font-bold text-indigo-700">
            {{ initials }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-900">{{ patient.name }}</p>
            <p class="text-xs text-slate-500">{{ patient.patient_code || 'No code' }}</p>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
            <input v-model="editForm.name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" :class="{ 'border-red-400': errors.name }" />
            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">🇮🇶 +964</span>
              <input :value="formatPhoneInput(editForm.phone)" @input="editForm.phone = sanitizePhoneInput($event.target.value)" type="tel" dir="ltr" inputmode="tel" placeholder="770 123 4567" class="w-full rounded-lg border border-slate-300 px-3 py-2 pl-16 font-mono text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
            </div>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Age</label>
            <input v-model.number="editForm.age" type="number" min="0" max="120" inputmode="numeric" placeholder="—" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Date of Birth</label>
            <input v-model="editForm.date_of_birth" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Gender</label>
            <div class="flex rounded-lg border border-slate-200 bg-slate-50 overflow-hidden">
              <button type="button" @click="editForm.gender = editForm.gender === 'male' ? '' : 'male'"
                      :class="editForm.gender === 'male' ? 'bg-indigo-500 text-white' : 'text-slate-600 hover:bg-slate-100'"
                      class="flex-1 px-3 py-2 text-sm font-medium transition-colors">
                ♂ Male
              </button>
              <button type="button" @click="editForm.gender = editForm.gender === 'female' ? '' : 'female'"
                      :class="editForm.gender === 'female' ? 'bg-pink-500 text-white' : 'text-slate-600 hover:bg-slate-100'"
                      class="flex-1 px-3 py-2 text-sm font-medium border-l border-slate-200 transition-colors">
                ♀ Female
              </button>
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Address</label>
            <input v-model="editForm.address" type="text" placeholder="Street address" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
          </div>
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Medical Notes</label>
          <textarea v-model="editForm.medical_notes" rows="2" placeholder="Any medical conditions, allergies, or notes..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Next Appointment</label>
          <input v-model="editForm.appointment_date" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showEdit = false">
          Cancel
        </button>
        <button type="button" class="btn-primary" @click="askSaveEdit">
          Save Changes
        </button>
      </template>
    </Modal>

    <!-- Manage conditions -->
    <Modal v-model="showConditions" :title="$t('patient.manage_conditions')" size="md">
      <div class="space-y-4">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
          <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Add New</h4>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-slate-600">Type</label>
              <select v-model="conditionForm.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="allergy">⚠ Allergy</option>
                <option value="condition">🩺 Condition</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-slate-600">Severity</label>
              <select v-model="conditionForm.severity" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="mild">Mild</option>
                <option value="moderate">Moderate</option>
                <option value="severe">Severe</option>
              </select>
            </div>
          </div>
          <div class="mt-3">
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
            <input v-model="conditionForm.name" type="text"
                   placeholder="e.g., Penicillin allergy"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                   :class="{ 'border-red-400': conditionErrors.name }" />
            <p v-if="conditionErrors.name" class="mt-1 text-xs text-red-500">{{ conditionErrors.name[0] }}</p>
          </div>
          <div class="mt-3">
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Note (optional)</label>
            <input v-model="conditionForm.note" type="text"
                   placeholder="Additional details..."
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div class="mt-3 flex justify-end">
            <button type="button" class="btn-primary btn-sm" @click="askSaveCondition" :disabled="savingCondition">
              {{ editingConditionId ? 'Update' : '+ Add Condition' }}
            </button>
          </div>
        </div>

        <div>
          <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Current Conditions ({{ conditions.length }})</h4>
          <ul v-if="conditions.length" class="space-y-2">
            <li v-for="c in conditions" :key="c.id"
                class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5">
              <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs"
                    :class="c.severity === 'severe' ? 'bg-red-100 text-red-700' : c.severity === 'moderate' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'">
                {{ c.type === 'allergy' ? '⚠' : '🩺' }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900">{{ c.name }}</p>
                <p class="text-xs text-slate-500">{{ c.type === 'allergy' ? 'Allergy' : 'Condition' }} · {{ c.severity }}</p>
              </div>
              <button type="button" class="btn-ghost btn-sm" @click="editCondition(c)">
                <Icon name="edit" :size="14" />
              </button>
              <button type="button" class="btn-ghost btn-sm text-red-500" @click="askDeleteCondition(c)">
                <Icon name="trash" :size="14" />
              </button>
            </li>
          </ul>
          <p v-else class="text-sm text-slate-400 text-center py-4">No conditions recorded</p>
        </div>
      </div>
    </Modal>

    <ConfirmDialog
      v-model="showConfirmCondition"
      :title="$t('common.confirm_save')"
      :message="$t('common.confirm_save_msg')"
      :confirm-label="$t('common.save')"
      :danger="false"
      @confirmed="saveCondition"
    />
    <ConfirmDialog
      v-model="showConfirmDeleteCondition"
      :title="$t('common.confirm_delete')"
      :message="confirmDeleteConditionMsg"
      :confirm-label="$t('common.delete')"
      @confirmed="deleteCondition"
    />
    <ConfirmDialog
      v-model="showConfirmDeletePatient"
      :title="$t('common.confirm_delete')"
      :message="confirmDeletePatientMsg"
      :confirm-label="$t('common.delete')"
      @confirmed="deletePatient"
    />

    <ConfirmDialog
      v-model="showConfirmSave"
      :title="$t('common.confirm_save')"
      :message="$t('common.confirm_save_msg')"
      :confirm-label="$t('common.save')"
      :danger="false"
      @confirmed="saveEdit"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import Modal         from '../components/Modal.vue';
import StatusBadge  from '../components/StatusBadge.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField     from '../components/FormField.vue';
import Icon from '../components/Icon.vue';
import DentalChart from '../components/DentalChart.vue';
import { formatIQD } from '../utils/iqd';
import { formatDateTime, toLocalInput } from '../utils/datetime';
import { formatPhoneForDisplay, formatPhoneForWhatsApp, formatPhoneInput, sanitizePhoneInput } from '../utils/phone';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';

const route   = useRoute();
const router  = useRouter();
const { t }   = useI18n();
const { can } = useAuth();
const toast = useToast();
const patient = ref(null);
const uploading = ref(false);

const format = (v) => formatIQD(v);

const activeVisit = computed(() =>
  patient.value?.visits?.find((v) => v.queue_status === 'active'),
);

const pendingVisits = computed(() =>
  (patient.value?.visits || []).filter((v) => v.queue_status !== 'completed'),
);

const totalVisits = computed(() => (patient.value?.visits || []).length);

const sortedVisits = computed(() => {
  const visits = patient.value?.visits || [];
  return [...visits].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const galleryPhotos = computed(() => patient.value?.photos || []);

const upcomingFollowup = computed(() => {
  const d = patient.value?.appointment_date;
  if (!d) return false;
  const now = new Date();
  const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  return String(d).slice(0, 10) >= today;
});

const reminderWhatsAppLink = computed(() => {
  if (!patient.value?.phone) return '#';
  const phone = patient.value.phone.replace(/\D/g, '');
  const name = patient.value.name;
  const date = patient.value.appointment_date;
  const dateStr = date ? new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '';
  const msg = `Hello ${name}, this is a reminder for your dental appointment on ${dateStr}. Please contact us if you need to reschedule.`;
  return `https://wa.me/964${phone}?text=${encodeURIComponent(msg)}`;
});

const lastVisitDisplay = computed(() => {
  const visits = patient.value?.visits || [];
  if (!visits.length) return null;
  const getDate = (v) => v.appointment_date || v.updated_at || v.created_at || null;
  const dated = visits.map((v) => ({ v, d: getDate(v) })).filter(x => x.d);
  if (!dated.length) return null;
  dated.sort((a, b) => new Date(a.d) - new Date(b.d));
  return formatDateTime(dated[dated.length - 1].d);
});

const totalPaid = computed(() => (patient.value?.visits || []).reduce((s, v) => s + (Number(v.amount_paid) || 0), 0));

const daysAsPatient = computed(() => {
  if (!patient.value?.created_at) return 0;
  const days = Math.floor((Date.now() - new Date(patient.value.created_at)) / 86400000);
  return days;
});

const appointmentCountdown = computed(() => {
  const d = patient.value?.appointment_date;
  if (!d) return null;
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  const appt = new Date(d);
  appt.setHours(0, 0, 0, 0);
  const diff = Math.ceil((appt - now) / 86400000);
  if (diff < 0) return null;
  return diff;
});

function callPatient() {
  if (!patient.value?.phone) return;
  const phone = patient.value.phone.replace(/\D/g, '');
  window.location.href = `tel:+964${phone}`;
}

const showEdit        = ref(false);
const showConfirmSave = ref(false);
const editForm        = ref({});
const errors          = ref({});

function validate() {
  const e = {};
  if (!editForm.value.name?.trim()) e.name = t('patient.name_required');
  const digits = String(editForm.value.phone || '').replace(/\D/g, '');
  if (editForm.value.phone && (digits.length < 7 || digits.length > 15)) {
    e.phone = t('patient.phone_invalid');
  }
  const age = editForm.value.age;
  if (age !== null && age !== '' && (Number.isNaN(+age) || age < 0 || age > 120)) {
    e.age = t('patient.age_invalid');
  }
  errors.value = e;
  return Object.keys(e).length === 0;
}

function openEdit() {
  editForm.value = {
    name:             patient.value.name,
    phone:            patient.value.phone || '',
    age:              patient.value.age || '',
    gender:           patient.value.gender || '',
    address:          patient.value.address || '',
    medical_notes:     patient.value.medical_notes || '',
    appointment_date: toLocalInput(patient.value.appointment_date),
  };
  errors.value = {};
  showEdit.value = true;
}

function askSaveEdit() {
  if (!validate()) return;
  showConfirmSave.value = true;
}

async function saveEdit() {
  await api.put(`/patients/${patient.value.id}`, editForm.value);
  showEdit.value = false;
  await load();
}

const apiOrigin = (import.meta.env.VITE_API_BASE || 'http://192.168.1.50:8000/api/v1')
  .replace(/\/api\/v1\/?$/, '');

function xrayUrl(path) { return `${apiOrigin}/storage/${path}`; }
function photoUrl(path) { return `${apiOrigin}/storage/${path}`; }
function relativeTime(dateStr) {
  if (!dateStr) return '';
  const diff = Date.now() - new Date(dateStr).getTime();
  const days = Math.floor(diff / 86400000);
  if (days === 0) return 'Today';
  if (days === 1) return 'Yesterday';
  if (days < 7) return `${days} days ago`;
  if (days < 30) return `${Math.floor(days / 7)} weeks ago`;
  if (days < 365) return `${Math.floor(days / 30)} months ago`;
  return `${Math.floor(days / 365)} years ago`;
}

async function load() {
  const { data } = await api.get(`/patients/${route.params.id}`);
  patient.value = data;
}

async function uploadXray(e, visit) {
  const file = e.target.files?.[0];
  if (!file) return;
  uploading.value = true;
  const fd = new FormData();
  fd.append('xray', file);
  try {
    await api.post(`/visits/${visit.id}/xray`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    await load();
  } finally {
    uploading.value = false;
    // Clear so re-picking the same file still fires a change event.
    e.target.value = '';
  }
}

function openGallery() {
  toast.info('Photo upload coming soon - backend support needed');
}

function openPhotoDetail(photo) {
  window.open(photoUrl(photo.path), '_blank');
}

/* ---- Profile header ---- */
const initials = computed(() =>
  (patient.value?.name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase(),
);

/* ---- Allergies & conditions ---- */
const conditions = computed(() => patient.value?.conditions || []);
const severeAllergy = computed(() =>
  conditions.value.some((c) => c.type === 'allergy' && c.severity === 'severe'),
);

const severityClass = (s) =>
  s === 'severe' ? 'bg-red-100 text-red-700'
    : s === 'moderate' ? 'bg-amber-100 text-amber-700'
      : 'bg-slate-100 text-slate-600';

const showConditions       = ref(false);
const savingCondition      = ref(false);
const editingConditionId   = ref(null);
const conditionForm        = ref(emptyConditionForm());
const conditionErrors      = ref({});
const showConfirmCondition = ref(false);
const pendingCondition     = ref(null);
const confirmDeleteConditionMsg = ref('');
const showConfirmDeleteCondition = ref(false);
const confirmDeletePatientMsg    = ref('');
const showConfirmDeletePatient   = ref(false);

function emptyConditionForm() {
  return { type: 'allergy', name: '', severity: 'mild', note: '' };
}

function openConditions() {
  editingConditionId.value = null;
  conditionForm.value = emptyConditionForm();
  conditionErrors.value = {};
  showConditions.value = true;
}

function editCondition(c) {
  editingConditionId.value = c.id;
  conditionForm.value = { type: c.type, name: c.name, severity: c.severity, note: c.note || '' };
  conditionErrors.value = {};
}

function askSaveCondition() {
  if (!conditionForm.value.name.trim()) {
    conditionErrors.value = { name: t('patient.condition_name_required') };
    return;
  }
  conditionErrors.value = {};
  showConfirmCondition.value = true;
}

async function saveCondition() {
  savingCondition.value = true;
  try {
    if (editingConditionId.value) {
      await api.patch(`/conditions/${editingConditionId.value}`, conditionForm.value);
    } else {
      await api.post(`/patients/${patient.value.id}/conditions`, conditionForm.value);
    }
    editingConditionId.value = null;
    conditionForm.value = emptyConditionForm();
    await load();
  } finally {
    savingCondition.value = false;
  }
}

function askDeleteCondition(c) {
  pendingCondition.value = c;
  confirmDeleteConditionMsg.value = `"${c.name}"`;
  showConfirmDeleteCondition.value = true;
}

async function deleteCondition() {
  await api.delete(`/conditions/${pendingCondition.value.id}`);
  pendingCondition.value = null;
  await load();
}

/* ---- Delete patient (profile header trash button) ---- */
function askDeletePatient() {
  confirmDeletePatientMsg.value = `"${patient.value.name}"`;
  showConfirmDeletePatient.value = true;
}

async function deletePatient() {
  await api.delete(`/patients/${patient.value.id}`);
  router.push('/patients');
}

/* ---- Add to Queue ---- */
const addingToQueue = ref(false);
async function addToQueue() {
  addingToQueue.value = true;
  try {
    await api.post('/visits', { patient_id: patient.value.id, visit_type: 'walk_in' });
    toast.success('Patient added to queue');
    await load();
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to add to queue');
  } finally {
    addingToQueue.value = false;
  }
}

onMounted(load);
</script>
