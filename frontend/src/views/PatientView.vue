<template>
  <section v-if="patient" class="patient-detail">
    <!-- Back link -->
    <router-link to="/patients" class="back-link">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
      </svg>
      {{ $t('common.back_to_patients') }}
    </router-link>

    <!-- Profile header card -->
    <div class="profile-card">
      <div class="profile-header">
        <div class="profile-info">
          <div class="profile-avatar" :class="severeAllergy ? 'avatar--alert' : 'avatar--primary'">
            {{ initials }}
          </div>
          <div class="profile-details">
            <div class="profile-name-row">
              <h2 class="profile-name">{{ patient.name }}</h2>
              <span v-if="severeAllergy" class="allergy-badge">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2L1 21h22L12 2zm0 3.5L19.5 19H4.5L12 5.5zM11 10v4h2v-4h-2zm0 6v2h2v-2h-2z"/>
                </svg>
                Allergies
              </span>
            </div>
            <div class="profile-meta">
              <span v-if="patient.patient_code" class="meta-item">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="16" rx="2"/>
                  <path d="M7 8h10M7 12h6"/>
                </svg>
                {{ patient.patient_code }}
              </span>
              <span v-if="patient.gender" class="meta-item">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/>
                  <path v-if="patient.gender === 'female'" d="M12 8v8M8 12h8"/>
                  <path v-else d="M8 12h8"/>
                </svg>
                {{ patient.gender === 'female' ? 'Female' : 'Male' }}
              </span>
              <span v-if="patient.age" class="meta-item">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/>
                  <path d="M12 6v6l4 2"/>
                </svg>
                {{ patient.age }} yrs
              </span>
              <span class="meta-item">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="16" rx="2"/>
                  <path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                {{ daysAsPatient }} days
              </span>
            </div>
          </div>
        </div>

        <div class="profile-stats">
          <div v-if="appointmentCountdown !== null" class="stat-box" :class="appointmentCountdown <= 1 ? 'stat-box--danger' : 'stat-box--primary'">
            <span class="stat-value">{{ appointmentCountdown === 0 ? 'Today!' : appointmentCountdown + 'd' }}</span>
            <span class="stat-label">until appt</span>
          </div>
          <div class="stat-box stat-box--visits">
            <span class="stat-value">{{ totalVisits }}</span>
            <span class="stat-label">Visits</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="profile-actions">
        <button v-if="can('queue.manage')" type="button" class="action-btn action-btn--primary" @click="addToQueue">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Add to Queue
        </button>
        <button v-if="can('patients.edit')" type="button" class="action-btn action-btn--secondary" @click="openEdit">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Edit
        </button>
        <button v-if="can('patients.delete')" type="button" class="action-btn action-btn--danger" @click="askDeletePatient">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
          </svg>
        </button>
        <a v-if="patient.phone" :href="formatPhoneForWhatsApp(patient.phone)" target="_blank" rel="noopener noreferrer"
           class="action-btn action-btn--whatsapp">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
          </svg>
          WhatsApp
        </a>
        <button v-if="patient.phone && upcomingFollowup" type="button" class="action-btn action-btn--call" @click="callPatient">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
          </svg>
          Call
        </button>
      </div>
    </div>

    <!-- Alerts -->
    <div v-if="patient.outstanding_short_term_debt > 0 || upcomingFollowup" class="alerts-row">
      <p v-if="patient.outstanding_short_term_debt > 0" class="alert alert--warning">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <path d="M12 8v4M12 16h.01"/>
        </svg>
        Outstanding: {{ format(patient.outstanding_short_term_debt) }} {{ $t('currency') }}
      </p>
      <p v-if="upcomingFollowup" class="alert alert--info">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="16" rx="2"/>
          <path d="M16 2v4M8 2v4M3 10h18"/>
        </svg>
        Next: {{ formatDateTime(patient.appointment_date) }}
      </p>
      <a v-if="patient.phone && upcomingFollowup"
         :href="reminderWhatsAppLink"
         target="_blank" rel="noopener noreferrer"
         class="alert alert--success">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
        Send Reminder
      </a>
    </div>

    <!-- Quick stats grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--calendar">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <path d="M16 2v4M8 2v4M3 10h18"/>
          </svg>
        </div>
        <div class="stat-card-content">
          <span class="stat-card-label">Last Visit</span>
          <span class="stat-card-value">{{ lastVisitDisplay || '—' }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--success">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
          </svg>
        </div>
        <div class="stat-card-content">
          <span class="stat-card-label">Total Paid</span>
          <span class="stat-card-value stat-card-value--success">{{ format(totalPaid) }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--warning">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 8v4M12 16h.01"/>
          </svg>
        </div>
        <div class="stat-card-content">
          <span class="stat-card-label">Outstanding</span>
          <span class="stat-card-value stat-card-value--warning">{{ format(patient.outstanding_short_term_debt || 0) }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--danger">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M2 12h20M12 2a10 10 0 100 20 10 10 0 000-20z"/>
            <path d="M12 6v6l4 2"/>
          </svg>
        </div>
        <div class="stat-card-content">
          <span class="stat-card-label">Long Term Debt</span>
          <span class="stat-card-value stat-card-value--danger">{{ format(patient.long_term_debt || 0) }}</span>
        </div>
      </div>
    </div>

    <!-- Conditions -->
    <div class="detail-card">
      <div class="detail-card-header">
        <h3 class="detail-card-title">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
          </svg>
          Conditions & Allergies
        </h3>
        <span v-if="conditions.length" class="detail-card-count">{{ conditions.length }}</span>
      </div>
      <div class="detail-card-body">
        <ul v-if="conditions.length" class="conditions-list">
          <li v-for="c in conditions" :key="c.id" class="condition-item">
            <span class="condition-icon" :class="severityClass(c.severity)">
              <svg v-if="c.type === 'allergy'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
              <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
              </svg>
            </span>
            <div class="condition-content">
              <p class="condition-name">{{ c.name }}</p>
              <p v-if="c.note" class="condition-note">{{ c.note }}</p>
            </div>
            <span class="condition-severity" :class="severityClass(c.severity)">{{ c.severity }}</span>
          </li>
        </ul>
        <div v-else class="empty-state">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p>No conditions recorded</p>
        </div>
        <div v-if="can('patients.edit')" class="detail-card-footer">
          <button type="button" class="btn-secondary btn-sm w-full" @click="openConditions">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Manage Conditions
          </button>
        </div>
      </div>
    </div>

    <!-- Contact Info -->
    <div class="detail-card">
      <div class="detail-card-header">
        <h3 class="detail-card-title">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          Contact Info
        </h3>
      </div>
      <div class="detail-card-body">
        <dl class="info-list">
          <div class="info-row">
            <dt class="info-label">Phone</dt>
            <dd class="info-value">
              <a v-if="patient.phone" :href="formatPhoneForWhatsApp(patient.phone)" target="_blank" rel="noopener noreferrer" class="info-link">
                {{ formatPhoneForDisplay(patient.phone) }}
              </a>
              <span v-else class="text-slate-400">—</span>
            </dd>
          </div>
          <div class="info-row">
            <dt class="info-label">Address</dt>
            <dd class="info-value">{{ patient.address || '—' }}</dd>
          </div>
          <div class="info-row">
            <dt class="info-label">Age</dt>
            <dd class="info-value">{{ patient.age ? `${patient.age} years` : '—' }}</dd>
          </div>
          <div class="info-row">
            <dt class="info-label">Gender</dt>
            <dd class="info-value">{{ patient.gender ? $t('patient.gender_' + patient.gender) : '—' }}</dd>
          </div>
          <div v-if="patient.medical_notes" class="info-row info-row--full">
            <dt class="info-label">Medical Notes</dt>
            <dd class="info-value info-value--notes">{{ patient.medical_notes }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Payment Plans -->
    <div v-if="patient.aqsat_contracts?.length" class="detail-card">
      <div class="detail-card-header">
        <h3 class="detail-card-title">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="4" width="22" height="16" rx="2"/>
            <path d="M1 10h22"/>
          </svg>
          Payment Plans
        </h3>
      </div>
      <div class="detail-card-body">
        <div class="payment-plans">
          <div v-for="c in patient.aqsat_contracts" :key="c.id" class="payment-plan">
            <div class="payment-plan-info">
              <p class="payment-plan-name">{{ c.treatment_name }}</p>
              <span class="payment-plan-status" :class="'status--' + c.status">{{ c.status }}</span>
            </div>
            <div class="payment-plan-amounts">
              <span class="payment-plan-remaining">{{ format(c.remaining_balance) }}</span>
              <span class="payment-plan-total">of {{ format(c.total_amount) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dental Chart -->
    <div class="detail-card">
      <div class="detail-card-header">
        <h3 class="detail-card-title">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
            <path d="M12 6v6l4 2"/>
          </svg>
          Dental Chart
        </h3>
      </div>
      <div class="detail-card-body">
        <DentalChart v-if="patient" :patient-id="patient.id" />
      </div>
    </div>

    <!-- X-Ray -->
    <div v-if="activeVisit" class="detail-card">
      <div class="detail-card-header">
        <h3 class="detail-card-title">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
            <circle cx="12" cy="13" r="4"/>
          </svg>
          X-Ray
        </h3>
      </div>
      <div class="detail-card-body">
        <label class="upload-zone">
          <input type="file" accept="image/*" capture="environment" class="sr-only" :disabled="uploading" @change="uploadXray($event, activeVisit)" />
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
            <circle cx="12" cy="13" r="4"/>
          </svg>
          <span class="upload-text">{{ $t('visit.upload_xray') }}</span>
        </label>
        <img v-if="activeVisit.xray_path" :src="xrayUrl(activeVisit.xray_path)" :alt="$t('visit.xray')" class="xray-preview" />
      </div>
    </div>

    <!-- Visit History Timeline -->
    <div class="detail-card">
      <div class="detail-card-header">
        <h3 class="detail-card-title">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12,6 12,12 16,14"/>
          </svg>
          Visit History
        </h3>
        <span class="detail-card-count">{{ patient.visits?.length || 0 }} visits</span>
      </div>
      <div class="detail-card-body">
        <div v-if="!patient.visits?.length" class="empty-state">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <path d="M16 2v4M8 2v4M3 10h18"/>
          </svg>
          <p>No visits yet</p>
        </div>
        <div v-else class="timeline">
          <div class="timeline-line"></div>
          <div v-for="visit in sortedVisits" :key="visit.id" class="timeline-item">
            <div class="timeline-dot" :class="visit.queue_status === 'completed' ? 'timeline-dot--completed' : visit.queue_status === 'active' ? 'timeline-dot--active' : 'timeline-dot--pending'"></div>
            <div class="timeline-card">
              <div class="timeline-card-header">
                <div class="timeline-card-badges">
                  <span v-if="visit.treatment_name" class="treatment-badge">{{ visit.treatment_name }}</span>
                  <StatusBadge kind="queue_status" :value="visit.queue_status" />
                </div>
                <span class="timeline-date">
                  {{ formatDateTime(visit.created_at) }}
                  <span class="timeline-relative">({{ relativeTime(visit.created_at) }})</span>
                </span>
              </div>
              <p v-if="visit.treatment_notes" class="timeline-notes">{{ visit.treatment_notes }}</p>
              <div class="timeline-card-footer">
                <div class="timeline-costs">
                  <span class="timeline-total">{{ format(visit.total_cost) }}</span>
                  <span class="timeline-paid" :class="visit.amount_paid >= visit.total_cost ? 'paid--full' : 'paid--partial'">
                    Paid: {{ format(visit.amount_paid) }}
                  </span>
                  <span v-if="visit.short_term_debt > 0" class="timeline-due">
                    Due: {{ format(visit.short_term_debt) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Treatment Gallery -->
    <div class="detail-card">
      <div class="detail-card-header">
        <h3 class="detail-card-title">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <path d="M21 15l-5-5L5 21"/>
          </svg>
          Treatment Gallery
        </h3>
        <button v-if="can('patients.edit')" type="button" class="btn-secondary btn-sm" @click="openGallery">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Add Photos
        </button>
      </div>
      <div class="detail-card-body">
        <div v-if="!galleryPhotos.length" class="empty-state">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <path d="M21 15l-5-5L5 21"/>
          </svg>
          <p>No treatment photos yet</p>
        </div>
        <div v-else class="gallery-grid">
          <div v-for="photo in galleryPhotos" :key="photo.id" class="gallery-item" @click="openPhotoDetail(photo)">
            <img :src="photoUrl(photo.path)" :alt="photo.note || 'Treatment photo'" class="gallery-img" />
            <div v-if="photo.type" class="gallery-overlay">
              <span class="gallery-type">{{ photo.type }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit patient modal -->
    <Modal v-model="showEdit" :title="$t('common.edit')" size="md">
      <div class="space-y-4">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
          <div class="avatar avatar--primary">{{ initials }}</div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-900">{{ patient.name }}</p>
            <p class="text-xs text-slate-500">{{ patient.patient_code || 'No code' }}</p>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
            <input v-model="editForm.name" type="text" class="form-input" :class="{ 'border-red-400': errors.name }" />
            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">🇮🇶 +964</span>
              <input :value="formatPhoneInput(editForm.phone)" @input="editForm.phone = sanitizePhoneInput($event.target.value)" type="tel" dir="ltr" inputmode="tel" placeholder="770 123 4567" class="form-input form-input--phone" />
            </div>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Age</label>
            <input v-model.number="editForm.age" type="number" min="0" max="120" inputmode="numeric" placeholder="—" class="form-input" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Date of Birth</label>
            <input v-model="editForm.date_of_birth" type="date" class="form-input" />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Gender</label>
            <div class="flex rounded-lg border border-slate-200 bg-slate-50 overflow-hidden">
              <button type="button" @click="editForm.gender = editForm.gender === 'male' ? '' : 'male'"
                      :class="editForm.gender === 'male' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                      class="flex-1 px-3 py-2 text-sm font-medium transition-colors">
                ♂ Male
              </button>
              <button type="button" @click="editForm.gender = editForm.gender === 'female' ? '' : 'female'"
                      :class="editForm.gender === 'female' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                      class="flex-1 px-3 py-2 text-sm font-medium border-l border-slate-200 transition-colors">
                ♀ Female
              </button>
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Address</label>
            <input v-model="editForm.address" type="text" placeholder="Street address" class="form-input" />
          </div>
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Medical Notes</label>
          <textarea v-model="editForm.medical_notes" rows="2" placeholder="Any medical conditions, allergies, or notes..." class="form-input form-input--textarea"></textarea>
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Next Appointment</label>
          <input v-model="editForm.appointment_date" type="datetime-local" class="form-input" />
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn-secondary" @click="showEdit = false">Cancel</button>
        <button type="button" class="btn-primary" @click="askSaveEdit">Save Changes</button>
      </template>
    </Modal>

    <!-- Manage conditions modal -->
    <Modal v-model="showConditions" :title="$t('patient.manage_conditions')" size="md">
      <div class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Add New</h4>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-slate-600">Type</label>
              <select v-model="conditionForm.type" class="form-input">
                <option value="allergy">⚠ Allergy</option>
                <option value="condition">🩺 Condition</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-slate-600">Severity</label>
              <select v-model="conditionForm.severity" class="form-input">
                <option value="mild">Mild</option>
                <option value="moderate">Moderate</option>
                <option value="severe">Severe</option>
              </select>
            </div>
          </div>
          <div class="mt-3">
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
            <input v-model="conditionForm.name" type="text" placeholder="e.g., Penicillin allergy" class="form-input" :class="{ 'border-red-400': conditionErrors.name }" />
            <p v-if="conditionErrors.name" class="mt-1 text-xs text-red-500">{{ conditionErrors.name[0] }}</p>
          </div>
          <div class="mt-3">
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Note (optional)</label>
            <input v-model="conditionForm.note" type="text" placeholder="Additional details..." class="form-input" />
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
            <li v-for="c in conditions" :key="c.id" class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5">
              <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs" :class="severityClass(c.severity)">
                {{ c.type === 'allergy' ? '⚠' : '🩺' }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900">{{ c.name }}</p>
                <p class="text-xs text-slate-500">{{ c.type === 'allergy' ? 'Allergy' : 'Condition' }} · {{ c.severity }}</p>
              </div>
              <button type="button" class="btn-ghost btn-sm" @click="editCondition(c)">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button type="button" class="btn-ghost btn-sm text-red-500" @click="askDeleteCondition(c)">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                </svg>
              </button>
            </li>
          </ul>
          <p v-else class="text-sm text-slate-400 text-center py-4">No conditions recorded</p>
        </div>
      </div>
    </Modal>

    <ConfirmDialog v-model="showConfirmCondition" :title="$t('common.confirm_save')" :message="$t('common.confirm_save_msg')" :confirm-label="$t('common.save')" :danger="false" @confirmed="saveCondition" />
    <ConfirmDialog v-model="showConfirmDeleteCondition" :title="$t('common.confirm_delete')" :message="confirmDeleteConditionMsg" :confirm-label="$t('common.delete')" @confirmed="deleteCondition" />
    <ConfirmDialog v-model="showConfirmDeletePatient" :title="$t('common.confirm_delete')" :message="confirmDeletePatientMsg" :confirm-label="$t('common.delete')" @confirmed="deletePatient" />
    <ConfirmDialog v-model="showConfirmSave" :title="$t('common.confirm_save')" :message="$t('common.confirm_save_msg')" :confirm-label="$t('common.save')" :danger="false" @confirmed="saveEdit" />
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

const activeVisit = computed(() => patient.value?.visits?.find((v) => v.queue_status === 'active'));
const pendingVisits = computed(() => (patient.value?.visits || []).filter((v) => v.queue_status !== 'completed'));
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

const apiOrigin = (import.meta.env.VITE_API_BASE || 'http://192.168.1.50:8000/api/v1').replace(/\/api\/v1\/?$/, '');

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
    await api.post(`/visits/${visit.id}/xray`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    await load();
  } finally {
    uploading.value = false;
    e.target.value = '';
  }
}

function openGallery() {
  toast.info('Photo upload coming soon - backend support needed');
}

function openPhotoDetail(photo) {
  window.open(photoUrl(photo.path), '_blank');
}

const initials = computed(() =>
  (patient.value?.name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase(),
);

const conditions = computed(() => patient.value?.conditions || []);
const severeAllergy = computed(() => conditions.value.some((c) => c.type === 'allergy' && c.severity === 'severe'));

const severityClass = (s) =>
  s === 'severe' ? 'severity--severe' : s === 'moderate' ? 'severity--moderate' : 'severity--mild';

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

function askDeletePatient() {
  confirmDeletePatientMsg.value = `"${patient.value.name}"`;
  showConfirmDeletePatient.value = true;
}

async function deletePatient() {
  await api.delete(`/patients/${patient.value.id}`);
  router.push('/patients');
}

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

<style scoped>
.patient-detail {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  text-decoration: none;
  transition: color 0.2s;
}

.back-link:hover {
  color: #E73F1E;
}

.profile-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

html.dark .profile-card {
  background: #1e293b;
  border-color: #334155;
}

.profile-header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1rem;
}

.profile-info {
  display: flex;
  gap: 1rem;
  min-width: 0;
}

.profile-avatar {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar--primary {
  background: linear-gradient(135deg, #E73F1E 0%, #dc2626 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(231, 63, 30, 0.3);
}

.avatar--alert {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
  50% { box-shadow: 0 4px 20px rgba(220, 38, 38, 0.5); }
}

.profile-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.profile-name-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}

.profile-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

html.dark .profile-name {
  color: #f1f5f9;
}

.allergy-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  background: #FEE2E2;
  color: #dc2626;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 9999px;
  border: 1px solid #FECACA;
}

html.dark .allergy-badge {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.3);
  color: #fca5a5;
}

.profile-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  color: #64748b;
}

html.dark .meta-item {
  color: #94a3b8;
}

.profile-stats {
  display: flex;
  gap: 0.75rem;
}

.stat-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.75rem 1.25rem;
  border-radius: 12px;
  min-width: 80px;
}

.stat-box--primary {
  background: #FEF3C7;
  border: 1px solid #FDE68A;
}

.stat-box--danger {
  background: #FEE2E2;
  border: 1px solid #FECACA;
}

.stat-box--visits {
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
}

html.dark .stat-box--primary {
  background: rgba(251, 191, 36, 0.1);
  border-color: rgba(251, 191, 36, 0.2);
}

html.dark .stat-box--danger {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.2);
}

html.dark .stat-box--visits {
  background: rgba(34, 197, 94, 0.1);
  border-color: rgba(34, 197, 94, 0.2);
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
}

.stat-box--primary .stat-value { color: #D97706; }
.stat-box--danger .stat-value { color: #DC2626; }
.stat-box--visits .stat-value { color: #16A34A; }

html.dark .stat-box--primary .stat-value { color: #FBBF24; }
html.dark .stat-box--danger .stat-value { color: #F87171; }
html.dark .stat-box--visits .stat-value { color: #4ADE80; }

.stat-label {
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stat-box--primary .stat-label { color: #92400E; }
.stat-box--danger .stat-label { color: #991B1B; }
.stat-box--visits .stat-label { color: #166534; }

html.dark .stat-box--primary .stat-label { color: #D97706; }
html.dark .stat-box--danger .stat-label { color: #EF4444; }
html.dark .stat-box--visits .stat-label { color: #22C55E; }

.profile-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
}

html.dark .profile-actions {
  border-color: #334155;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 10px;
  transition: all 0.2s;
  cursor: pointer;
  border: none;
}

.action-btn--primary {
  background: linear-gradient(135deg, #E73F1E 0%, #dc2626 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(231, 63, 30, 0.3);
}

.action-btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(231, 63, 30, 0.4);
}

.action-btn--secondary {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.action-btn--secondary:hover {
  background: #e2e8f0;
  color: #1e293b;
}

html.dark .action-btn--secondary {
  background: #334155;
  border-color: #475569;
  color: #cbd5e1;
}

html.dark .action-btn--secondary:hover {
  background: #475569;
  color: #f1f5f9;
}

.action-btn--danger {
  background: transparent;
  color: #dc2626;
  padding: 0.5rem;
}

.action-btn--danger:hover {
  background: #FEE2E2;
}

html.dark .action-btn--danger:hover {
  background: rgba(239, 68, 68, 0.2);
}

.action-btn--whatsapp {
  background: #25D366;
  color: white;
}

.action-btn--whatsapp:hover {
  background: #128C7E;
}

.action-btn--call {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

html.dark .action-btn--call {
  background: #334155;
  color: #cbd5e1;
  border-color: #475569;
}

/* Alerts */
.alerts-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.alert {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
  font-weight: 500;
  border-radius: 9999px;
  text-decoration: none;
}

.alert--warning {
  background: #FEF3C7;
  color: #92400E;
  border: 1px solid #FDE68A;
}

.alert--info {
  background: #DBEAFE;
  color: #1E40AF;
  border: 1px solid #BFDBFE;
}

.alert--success {
  background: #D1FAE5;
  color: #065F46;
  border: 1px solid #A7F3D0;
}

html.dark .alert--warning {
  background: rgba(251, 191, 36, 0.1);
  color: #FBBF24;
  border-color: rgba(251, 191, 36, 0.2);
}

html.dark .alert--info {
  background: rgba(59, 130, 246, 0.1);
  color: #60A5FA;
  border-color: rgba(59, 130, 246, 0.2);
}

html.dark .alert--success {
  background: rgba(34, 197, 94, 0.1);
  color: #4ADE80;
  border-color: rgba(34, 197, 94, 0.2);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

@media (min-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.2s;
}

.stat-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

html.dark .stat-card {
  background: #1e293b;
  border-color: #334155;
}

html.dark .stat-card:hover {
  border-color: #475568;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.stat-card-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-card-icon--calendar {
  background: #DBEAFE;
  color: #2563EB;
}

.stat-card-icon--success {
  background: #D1FAE5;
  color: #059669;
}

.stat-card-icon--warning {
  background: #FEF3C7;
  color: #D97706;
}

.stat-card-icon--danger {
  background: #FEE2E2;
  color: #DC2626;
}

html.dark .stat-card-icon--calendar { background: rgba(59, 130, 246, 0.2); color: #60A5FA; }
html.dark .stat-card-icon--success { background: rgba(34, 197, 94, 0.2); color: #4ADE80; }
html.dark .stat-card-icon--warning { background: rgba(251, 191, 36, 0.2); color: #FBBF24; }
html.dark .stat-card-icon--danger { background: rgba(239, 68, 68, 0.2); color: #F87171; }

.stat-card-content {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  min-width: 0;
}

.stat-card-label {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
}

html.dark .stat-card-label {
  color: #94a3b8;
}

.stat-card-value {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

html.dark .stat-card-value {
  color: #f1f5f9;
}

.stat-card-value--success { color: #059669; }
.stat-card-value--warning { color: #D97706; }
.stat-card-value--danger { color: #DC2626; }

html.dark .stat-card-value--success { color: #4ADE80; }
html.dark .stat-card-value--warning { color: #FBBF24; }
html.dark .stat-card-value--danger { color: #F87171; }

/* Detail Cards */
.detail-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
}

html.dark .detail-card {
  background: #1e293b;
  border-color: #334155;
}

.detail-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

html.dark .detail-card-header {
  background: #0f172a;
  border-color: #334155;
}

.detail-card-title {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1e293b;
}

html.dark .detail-card-title {
  color: #f1f5f9;
}

.detail-card-count {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.625rem;
  background: #e2e8f0;
  color: #475569;
  border-radius: 9999px;
}

html.dark .detail-card-count {
  background: #334155;
  color: #cbd5e1;
}

.detail-card-body {
  padding: 1.25rem;
}

.detail-card-footer {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

html.dark .detail-card-footer {
  border-color: #334155;
}

/* Conditions List */
.conditions-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.condition-item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 12px;
}

html.dark .condition-item {
  background: #0f172a;
}

.condition-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.severity--severe {
  background: #FEE2E2;
  color: #DC2626;
}

.severity--moderate {
  background: #FEF3C7;
  color: #D97706;
}

.severity--mild {
  background: #f1f5f9;
  color: #64748b;
}

html.dark .severity--severe { background: rgba(239, 68, 68, 0.2); color: #F87171; }
html.dark .severity--moderate { background: rgba(251, 191, 36, 0.2); color: #FBBF24; }
html.dark .severity--mild { background: #334155; color: #94a3b8; }

.condition-content {
  flex: 1;
  min-width: 0;
}

.condition-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

html.dark .condition-name {
  color: #f1f5f9;
}

.condition-note {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.125rem;
}

html.dark .condition-note {
  color: #94a3b8;
}

.condition-severity {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
}

/* Info List */
.info-list {
  display: flex;
  flex-direction: column;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
  border-bottom: none;
}

html.dark .info-row {
  border-color: #1e293b;
}

.info-row--full {
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
}

.info-label {
  font-size: 0.8125rem;
  color: #64748b;
  font-weight: 500;
}

html.dark .info-label {
  color: #94a3b8;
}

.info-value {
  font-size: 0.875rem;
  color: #1e293b;
  font-weight: 500;
  text-align: end;
}

html.dark .info-value {
  color: #f1f5f9;
}

.info-value--notes {
  text-align: start;
  font-weight: 400;
  color: #475569;
  background: #f8fafc;
  padding: 0.75rem;
  border-radius: 8px;
  margin-top: 0.25rem;
}

html.dark .info-value--notes {
  background: #0f172a;
  color: #cbd5e1;
}

.info-link {
  color: #E73F1E;
  text-decoration: none;
  font-weight: 600;
}

.info-link:hover {
  text-decoration: underline;
}

/* Payment Plans */
.payment-plans {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.payment-plan {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.875rem;
  background: #f8fafc;
  border-radius: 12px;
}

html.dark .payment-plan {
  background: #0f172a;
}

.payment-plan-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.payment-plan-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

html.dark .payment-plan-name {
  color: #f1f5f9;
}

.payment-plan-status {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
  width: fit-content;
}

.status--active { background: #D1FAE5; color: #059669; }
.status--completed { background: #f1f5f9; color: #64748b; }
.status--pending { background: #FEF3C7; color: #D97706; }

html.dark .status--active { background: rgba(34, 197, 94, 0.2); color: #4ADE80; }
html.dark .status--completed { background: #334155; color: #94a3b8; }
html.dark .status--pending { background: rgba(251, 191, 36, 0.2); color: #FBBF24; }

.payment-plan-amounts {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.125rem;
}

.payment-plan-remaining {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #1e293b;
  font-family: monospace;
}

html.dark .payment-plan-remaining {
  color: #f1f5f9;
}

.payment-plan-total {
  font-size: 0.6875rem;
  color: #64748b;
}

html.dark .payment-plan-total {
  color: #94a3b8;
}

/* Upload Zone */
.upload-zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  border: 2px dashed #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  color: #64748b;
}

.upload-zone:hover {
  border-color: #E73F1E;
  background: rgba(231, 63, 30, 0.05);
  color: #E73F1E;
}

html.dark .upload-zone {
  border-color: #334155;
  color: #94a3b8;
}

html.dark .upload-zone:hover {
  border-color: #E73F1E;
  background: rgba(231, 63, 30, 0.1);
  color: #f87171;
}

.upload-text {
  font-size: 0.875rem;
  font-weight: 500;
}

.xray-preview {
  margin-top: 1rem;
  max-height: 200px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

html.dark .xray-preview {
  border-color: #334155;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 2rem;
  color: #94a3b8;
  text-align: center;
}

html.dark .empty-state {
  color: #64748b;
}

/* Timeline */
.timeline {
  position: relative;
  padding-left: 1.5rem;
}

.timeline-line {
  position: absolute;
  left: 0.375rem;
  top: 0.5rem;
  bottom: 0.5rem;
  width: 2px;
  background: #e2e8f0;
}

html.dark .timeline-line {
  background: #334155;
}

.timeline-item {
  position: relative;
  padding-bottom: 1.25rem;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-dot {
  position: absolute;
  left: -1.25rem;
  top: 0.375rem;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

html.dark .timeline-dot {
  border-color: #1e293b;
}

.timeline-dot--completed {
  background: #22C55E;
}

.timeline-dot--active {
  background: #E73F1E;
  animation: timeline-pulse 2s infinite;
}

.timeline-dot--pending {
  background: #94A3B8;
}

@keyframes timeline-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(231, 63, 30, 0.4); }
  50% { box-shadow: 0 0 0 6px rgba(231, 63, 30, 0); }
}

.timeline-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  transition: all 0.2s;
}

.timeline-card:hover {
  border-color: #cbd5e1;
}

html.dark .timeline-card {
  background: #0f172a;
  border-color: #334155;
}

html.dark .timeline-card:hover {
  border-color: #475568;
}

.timeline-card-header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
}

.timeline-card-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.treatment-badge {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  background: #EDE9FE;
  color: #7C3AED;
  border-radius: 6px;
}

html.dark .treatment-badge {
  background: rgba(139, 92, 246, 0.2);
  color: #A78BFA;
}

.timeline-date {
  font-size: 0.6875rem;
  color: #64748b;
  white-space: nowrap;
}

html.dark .timeline-date {
  color: #94a3b8;
}

.timeline-relative {
  color: #94a3b8;
  margin-left: 0.25rem;
}

html.dark .timeline-relative {
  color: #64748b;
}

.timeline-notes {
  margin-top: 0.5rem;
  font-size: 0.8125rem;
  color: #475569;
  background: white;
  padding: 0.625rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

html.dark .timeline-notes {
  background: #1e293b;
  border-color: #334155;
  color: #cbd5e1;
}

.timeline-card-footer {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e2e8f0;
}

html.dark .timeline-card-footer {
  border-color: #334155;
}

.timeline-costs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: baseline;
}

.timeline-total {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  font-family: monospace;
}

html.dark .timeline-total {
  color: #f1f5f9;
}

.timeline-paid {
  font-size: 0.75rem;
  font-weight: 500;
}

.paid--full { color: #059669; }
.paid--partial { color: #D97706; }

html.dark .paid--full { color: #4ADE80; }
html.dark .paid--partial { color: #FBBF24; }

.timeline-due {
  font-size: 0.75rem;
  font-weight: 600;
  color: #DC2626;
}

html.dark .timeline-due {
  color: #F87171;
}

/* Gallery */
.gallery-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.gallery-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid #e2e8f0;
}

html.dark .gallery-item {
  border-color: #334155;
}

.gallery-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.2s;
}

.gallery-item:hover .gallery-img {
  transform: scale(1.05);
}

.gallery-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0.5rem;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
}

.gallery-type {
  font-size: 0.625rem;
  font-weight: 600;
  color: white;
  text-transform: uppercase;
}

/* Form Inputs */
.form-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #1e293b;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.1);
}

html.dark .form-input {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

html.dark .form-input:focus {
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.2);
}

.form-input--phone {
  padding-left: 4rem;
  font-family: monospace;
}

.form-input--textarea {
  resize: none;
}
</style>
