import { ref, computed } from "vue";
import api from "../utils/axios";

const STORAGE_KEY = "dps_auth";

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return { token: null, user: null, permissions: [] };
    const parsed = JSON.parse(raw);
    return {
      token: parsed.token || null,
      user: parsed.user || null,
      permissions: Array.isArray(parsed.permissions) ? parsed.permissions : [],
    };
  } catch {
    return { token: null, user: null, permissions: [] };
  }
}

const initial = loadFromStorage();
const token = ref(initial.token);
const user = ref(initial.user);
const permissions = ref(new Set(initial.permissions));
const isAuthenticated = computed(() => !!token.value);
const isReady = ref(false);
let mePromise = null;

// Current doctor context for receptionists who are assigned to multiple doctors.
// - `currentDoctorId` is null for admin/doctor/hygienist (they only see their own).
// - For receptionists, it's a number or null (= "all my assigned doctors").
// Persisted in localStorage so it survives page reloads.
const currentDoctorId = ref(
  localStorage.getItem("dps_current_doctor_id")
    ? Number(localStorage.getItem("dps_current_doctor_id"))
    : null
);

function persistCurrentDoctor() {
  if (currentDoctorId.value === null) {
    localStorage.removeItem("dps_current_doctor_id");
  } else {
    localStorage.setItem("dps_current_doctor_id", String(currentDoctorId.value));
  }
}

function persist() {
  localStorage.setItem(
    STORAGE_KEY,
    JSON.stringify({
      token: token.value,
      user: user.value,
      permissions: [...permissions.value],
    })
  );
}

function clear() {
  token.value = null;
  user.value = null;
  permissions.value = new Set();
  currentDoctorId.value = null;
  localStorage.removeItem(STORAGE_KEY);
  localStorage.removeItem("dps_current_doctor_id");
}

function setSession(payload) {
  token.value = payload.token;
  user.value = payload.user;
  permissions.value = new Set(payload.user?.permissions || []);
  persist();
}

async function login(email, password, deviceName = "web") {
  const { data } = await api.post("/login", {
    email,
    password,
    device_name: deviceName,
  });
  setSession({ token: data.token, user: data.user });
  return data.user;
}

async function logout() {
  try {
    if (token.value) await api.post("/logout");
  } catch {
    /* ignore */
  }
  clear();
}

async function fetchMe() {
  if (!token.value) return null;
  if (mePromise) return mePromise;
  mePromise = api
    .get("/me")
    .then(({ data }) => {
      user.value = data;
      permissions.value = new Set(data.permissions || []);
      persist();

      // If currentDoctorId is no longer valid (admin removed assignments), reset it
      const validIds = (data.assigned_doctors || []).map((d) => d.id);
      if (currentDoctorId.value !== null && !validIds.includes(currentDoctorId.value)) {
        currentDoctorId.value = null;
        persistCurrentDoctor();
      }
      return data;
    })
    .catch((err) => {
      if (err.response?.status === 401) clear();
      return null;
    })
    .finally(() => {
      mePromise = null;
    });
  return mePromise;
}

function can(permission) {
  if (!permission) return true;
  if (Array.isArray(permission)) return permission.some((p) => permissions.value.has(p));
  return permissions.value.has(permission);
}

function hasRole(role) {
  return user.value?.role === role;
}

function hasAnyRole(roles) {
  return roles.includes(user.value?.role);
}

/** Doctors a receptionist is assigned to (empty for non-receptionists). */
const assignedDoctors = computed(() => user.value?.assigned_doctors || []);

/** Doctors a doctor user IS (one entry) or empty. */
const doctorProfile = computed(() => user.value?.doctor_profile || null);

/** True if user can pick from multiple doctors (only for receptionists). */
const canSelectDoctor = computed(
  () => hasRole("receptionist") && assignedDoctors.value.length > 1
);

/** Currently selected doctor (or null for "all my doctors"). */
const currentDoctor = computed(() => {
  if (currentDoctorId.value === null) return null;
  return assignedDoctors.value.find((d) => d.id === currentDoctorId.value) || null;
});

function setCurrentDoctorId(id) {
  currentDoctorId.value = id === null || id === undefined ? null : Number(id);
  persistCurrentDoctor();
}

export function useAuth() {
  return {
    token,
    user,
    permissions,
    isAuthenticated,
    isReady,
    can,
    hasRole,
    hasAnyRole,
    login,
    logout,
    fetchMe,
    setSession,
    clear,
    assignedDoctors,
    doctorProfile,
    canSelectDoctor,
    currentDoctor,
    currentDoctorId,
    setCurrentDoctorId,
  };
}
