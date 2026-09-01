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
import HomeView           from '../views/HomeView.vue';

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/',                redirect: '/home' },
    { path: '/home',            component: HomeView,          name: 'home' },
    { path: '/queue',           component: QueueView,         name: 'queue' },
    { path: '/patients',        component: PatientsView,      name: 'patients' },
    { path: '/patients/:id',    component: PatientView,       name: 'patient', props: true },
    { path: '/archive',         component: ArchiveView,       name: 'archive' },
    { path: '/dashboard',       component: DashboardView,     name: 'dashboard' },
    { path: '/cash-flow',       component: DashboardView,     name: 'cashflow' },
    { path: '/expenses',        component: ExpensesView,      name: 'expenses' },
    { path: '/payment-plans',   component: PaymentPlansView,  name: 'plans' },
    { path: '/inventory',       component: InventoryView,     name: 'inventory' },
    { path: '/vendors',         component: VendorsView,       name: 'vendors' },
  ],
});

export default router;