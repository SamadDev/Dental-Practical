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
  localStorage.removeItem(STORAGE_KEY);
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

export function useAuth() {
  return {
    token,
    user,
    permissions,
    isAuthenticated,
    isReady,
    can,
    hasRole,
    login,
    logout,
    fetchMe,
    setSession,
    clear,
  };
}
