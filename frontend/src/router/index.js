import { createRouter, createWebHashHistory } from 'vue-router';
import QueueView          from '../views/QueueView.vue';
import PatientsView       from '../views/PatientsView.vue';
import PatientView        from '../views/PatientView.vue';
import ArchiveView        from '../views/ArchiveView.vue';
import DashboardView      from '../views/DashboardView.vue';
import ExpensesView       from '../views/ExpensesView.vue';
import PaymentPlansView   from '../views/PaymentPlansView.vue';
import InventoryView      from '../views/InventoryView.vue';
import VendorsView        from '../views/VendorsView.vue';
import CashFlowView       from '../views/CashFlowView.vue';
import LoginView          from '../views/LoginView.vue';
import HomeView           from '../views/HomeView.vue';
import { useAuthStore } from '../store/auth';

// Hash history works perfectly when serving a static dist/ from any local file path.
const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/login',           component: LoginView,         name: 'login', meta: { public: true } },
    { path: '/',                redirect: '/home' },
    { path: '/home',            component: HomeView,          name: 'home' },
    { path: '/queue',           component: QueueView,         name: 'queue' },
    { path: '/patients',        component: PatientsView,      name: 'patients' },
    { path: '/patients/:id',    component: PatientView,       name: 'patient', props: true },
    { path: '/archive',         component: ArchiveView,       name: 'archive' },
    { path: '/dashboard',       component: DashboardView,     name: 'dashboard' },
    { path: '/expenses',        component: ExpensesView,      name: 'expenses' },
    { path: '/payment-plans',   component: PaymentPlansView,  name: 'plans' },
    { path: '/inventory',       component: InventoryView,     name: 'inventory' },
    { path: '/vendors',         component: VendorsView,       name: 'vendors' },
    { path: '/cash-flow',       component: CashFlowView,      name: 'cashflow' },
  ],
});

router.beforeEach((to) => {
  const auth = useAuthStore();
  if (!to.meta.public && !auth.isLoggedIn) return { name: 'login' };
  if (to.name === 'login' && auth.isLoggedIn) return { path: '/' };
});

export default router;
