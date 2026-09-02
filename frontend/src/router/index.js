import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import QueueView          from '../views/QueueView.vue';
import PatientsView       from '../views/PatientsView.vue';
import PatientView        from '../views/PatientView.vue';
import ArchiveView        from '../views/ArchiveView.vue';
import DashboardView      from '../views/DashboardView.vue';
import ExpensesView       from '../views/ExpensesView.vue';
import PaymentPlansView   from '../views/PaymentPlansView.vue';
import InventoryView      from '../views/InventoryView.vue';
import VendorsView        from '../views/VendorsView.vue';
import HomeView           from '../views/HomeView.vue';
import LoginView          from '../views/Login.vue';

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/login',           component: LoginView,         name: 'login',    meta: { public: true } },
    { path: '/',                redirect: '/home' },
    { path: '/home',            component: HomeView,          name: 'home',     meta: { permission: 'dashboard.view' } },
    { path: '/queue',           component: QueueView,         name: 'queue',    meta: { permission: 'queue.view' } },
    { path: '/patients',        component: PatientsView,      name: 'patients', meta: { permission: 'patients.view' } },
    { path: '/patients/:id',    component: PatientView,       name: 'patient',  props: true, meta: { permission: 'patients.view' } },
    { path: '/archive',         component: ArchiveView,       name: 'archive',  meta: { permission: 'archive.view' } },
    { path: '/dashboard',       component: DashboardView,     name: 'dashboard', meta: { permission: 'dashboard.view' } },
    { path: '/expenses',        component: ExpensesView,      name: 'expenses', meta: { permission: 'expenses.view' } },
    { path: '/payment-plans',   component: PaymentPlansView,  name: 'plans',    meta: { permission: 'payment_plans.view' } },
    { path: '/inventory',       component: InventoryView,     name: 'inventory', meta: { permission: 'inventory.view' } },
    { path: '/vendors',         component: VendorsView,       name: 'vendors',  meta: { permission: 'vendors.view' } },
  ],
});

router.beforeEach((to) => {
  const { isAuthenticated, can } = useAuth();
  if (to.meta.public) return true;
  if (!isAuthenticated.value) return '/login';
  const permission = to.meta.permission;
  if (permission && !can(permission)) return '/home';
  return true;
});

export default router;
