import { createRouter, createWebHashHistory } from 'vue-router';
import QueueView     from '../views/QueueView.vue';
import PatientsView  from '../views/PatientsView.vue';
import PatientView   from '../views/PatientView.vue';
import ArchiveView   from '../views/ArchiveView.vue';
import DashboardView from '../views/DashboardView.vue';
import ExpensesView  from '../views/ExpensesView.vue';

// Hash history works perfectly when serving a static dist/ from any local file path.
export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/',                 redirect: '/queue' },
    { path: '/queue',            component: QueueView,     name: 'queue' },
    { path: '/patients',         component: PatientsView,  name: 'patients' },
    { path: '/patients/:id',     component: PatientView,   name: 'patient', props: true },
    { path: '/archive',          component: ArchiveView,   name: 'archive' },
    { path: '/dashboard',        component: DashboardView, name: 'dashboard' },
    { path: '/expenses',         component: ExpensesView,  name: 'expenses' },
  ],
});
