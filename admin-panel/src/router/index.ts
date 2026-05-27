import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import LoginView from '../views/auth/LoginView.vue';
import DashboardView from '../views/dashboard/DashboardView.vue';
import UsersView from '../views/users/UsersView.vue';
import DriversView from '../views/drivers/DriversView.vue';
import VehiclesView from '../views/vehicles/VehiclesView.vue';
import WorkOrdersView from '../views/work-orders/WorkOrdersView.vue';
import WaybillsView from '../views/waybills/WaybillsView.vue';
import MedicalInspectionsView from '../views/medical-inspections/MedicalInspectionsView.vue';
import TechnicalInspectionsView from '../views/technical-inspections/TechnicalInspectionsView.vue';
import MapView from '../views/map/MapView.vue';
import MovementHistoryView from '../views/movement-history/MovementHistoryView.vue';
import FuelView from '../views/fuel/FuelView.vue';
import ReportsView from '../views/reports/ReportsView.vue';
import AuditView from '../views/audit/AuditView.vue';
import SettingsView from '../views/settings/SettingsView.vue';

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView },
    { path: '/', name: 'dashboard', component: DashboardView, meta: { requiresAuth: true } },
    { path: '/users', name: 'users', component: UsersView, meta: { requiresAuth: true, roles: ['admin'] } },
    { path: '/drivers', name: 'drivers', component: DriversView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher'] } },
    { path: '/vehicles', name: 'vehicles', component: VehiclesView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher', 'mechanic'] } },
    { path: '/work-orders', name: 'work-orders', component: WorkOrdersView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher'] } },
    { path: '/waybills', name: 'waybills', component: WaybillsView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher'] } },
    { path: '/medical-inspections', name: 'medical-inspections', component: MedicalInspectionsView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher', 'medic'] } },
    { path: '/technical-inspections', name: 'technical-inspections', component: TechnicalInspectionsView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher', 'mechanic'] } },
    { path: '/map', name: 'map', component: MapView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher'] } },
    { path: '/movement-history', name: 'movement-history', component: MovementHistoryView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher'] } },
    { path: '/fuel', name: 'fuel', component: FuelView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher'] } },
    { path: '/reports', name: 'reports', component: ReportsView, meta: { requiresAuth: true, roles: ['admin', 'dispatcher'] } },
    { path: '/audit', name: 'audit', component: AuditView, meta: { requiresAuth: true, roles: ['admin'] } },
    { path: '/settings', name: 'settings', component: SettingsView, meta: { requiresAuth: true, roles: ['admin'] } },
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  if (auth.token && !auth.user) {
    await auth.loadMe();
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' };
  }

  const roles = to.meta.roles as string[] | undefined;

  if (roles && auth.user && !roles.includes(auth.user.role)) {
    return { name: 'dashboard' };
  }
});

