import axios from "axios";
import router from "../router";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || "/api/v1",
  timeout: 15000,
  headers: { Accept: "application/json" },
});

// Attach auth token + current doctor context to every request
api.interceptors.request.use((config) => {
  const raw = localStorage.getItem("dps_auth");
  if (raw) {
    try {
      const { token } = JSON.parse(raw);
      if (token) config.headers.Authorization = `Bearer ${token}`;
    } catch {}
  }

  // Inject current doctor context into patient/visit/queue queries when a
  // specific doctor is selected (receptionists switching between doctors).
  const doctorIdRaw = localStorage.getItem("dps_current_doctor_id");
  const doctorId = doctorIdRaw ? Number(doctorIdRaw) : null;
  const url = config.url || "";

  if (doctorId !== null && (url.includes("/patients") || url.includes("/visits") || url.includes("/queue"))) {
    config.params = { ...(config.params || {}), doctor_id: doctorId };
  }

  return config;
});

// Handle 401 -> redirect to login
api.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem("dps_auth");
      router.push("/login");
    }

    err.userMessage =
      err.response?.data?.message ||
      (err.response?.status === 422
        ? Object.values(err.response.data.errors ?? {}).flat().join(" ")
        : "") ||
      err.message ||
      "Network error";

    return Promise.reject(err);
  }
);

export default api;
