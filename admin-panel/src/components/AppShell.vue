<script setup lang="ts">
import {
  Activity,
  ClipboardList,
  FileText,
  Fuel,
  Gauge,
  HeartPulse,
  History,
  LayoutDashboard,
  LogOut,
  Map,
  Settings,
  ShieldCheck,
  Truck,
  UserCog,
  Users,
  Wrench,
} from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiClient } from '../api/client';
import { useAuthStore } from '../stores/auth';
import { enumLabel, listItems } from '../utils/api';

const auth = useAuthStore();
const router = useRouter();
const notifications = ref<{ id: string; title: string; text: string; to: string }[]>([]);
const knownMedical = new Set<number>();
const knownTechnical = new Set<number>();
let firstNotificationScan = true;
let notificationTimer: number | undefined;

const navItems = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/users', label: 'Пользователи', icon: UserCog, roles: ['admin'] },
  { to: '/drivers', label: 'Водители', icon: Users, roles: ['admin', 'dispatcher'] },
  { to: '/vehicles', label: 'Автомобили', icon: Truck, roles: ['admin', 'dispatcher', 'mechanic'] },
  { to: '/work-orders', label: 'План-наряды', icon: ClipboardList, roles: ['admin', 'dispatcher'] },
  { to: '/waybills', label: 'Путевые листы', icon: FileText, roles: ['admin', 'dispatcher'] },
  { to: '/medical-inspections', label: 'Медосмотры', icon: HeartPulse, roles: ['admin', 'dispatcher', 'medic'] },
  { to: '/technical-inspections', label: 'Техосмотры', icon: Wrench, roles: ['admin', 'dispatcher', 'mechanic'] },
  { to: '/map', label: 'Карта', icon: Map, roles: ['admin', 'dispatcher'] },
  { to: '/movement-history', label: 'История движения', icon: History, roles: ['admin', 'dispatcher'] },
  { to: '/fuel', label: 'Заправки', icon: Fuel, roles: ['admin', 'dispatcher'] },
  { to: '/reports', label: 'Отчеты', icon: Gauge, roles: ['admin', 'dispatcher'] },
  { to: '/audit', label: 'Журнал действий', icon: Activity, roles: ['admin'] },
  { to: '/settings', label: 'Настройки', icon: Settings, roles: ['admin'] },
];

const canSee = (roles?: string[]) => !roles || (auth.user && roles.includes(auth.user.role));
const canPoll = (roles: string[]) => !!auth.user && roles.includes(auth.user.role);

async function logout() {
  await auth.logout();
  await router.push('/login');
}

function pushNotification(title: string, text: string, to: string) {
  const id = `${to}-${Date.now()}-${Math.random()}`;
  notifications.value = [{ id, title, text, to }, ...notifications.value].slice(0, 4);
  window.setTimeout(() => dismissNotification(id), 12000);
}

function dismissNotification(id: string) {
  notifications.value = notifications.value.filter((item) => item.id !== id);
}

async function openNotification(notification: { id: string; to: string }) {
  dismissNotification(notification.id);
  await router.push(notification.to);
}

async function pollInspectionNotifications() {
  const tasks: Promise<void>[] = [];

  if (canPoll(['admin', 'dispatcher', 'medic'])) {
    tasks.push(apiClient.get('/admin/medical-inspections', { params: { status: 'pending' } } as any).then(({ data }) => {
      for (const item of listItems(data)) {
        if (knownMedical.has(item.id)) continue;
        knownMedical.add(item.id);
        if (!firstNotificationScan) {
          pushNotification(
            'Новая заявка на медосмотр',
            `${item.driver?.full_name ?? 'Водитель'} · ${enumLabel(item.type)}`,
            '/medical-inspections',
          );
        }
      }
    }));
  }

  if (canPoll(['admin', 'dispatcher', 'mechanic'])) {
    tasks.push(apiClient.get('/admin/technical-inspections', { params: { status: 'pending' } } as any).then(({ data }) => {
      for (const item of listItems(data)) {
        if (knownTechnical.has(item.id)) continue;
        knownTechnical.add(item.id);
        if (!firstNotificationScan) {
          pushNotification(
            'Новая заявка на техосмотр',
            `${item.driver?.full_name ?? 'Водитель'} · ${item.vehicle?.plate_number ?? 'Автомобиль'}`,
            '/technical-inspections',
          );
        }
      }
    }));
  }

  await Promise.allSettled(tasks);
  firstNotificationScan = false;
}

onMounted(() => {
  pollInspectionNotifications();
  notificationTimer = window.setInterval(pollInspectionNotifications, 10000);
});

onBeforeUnmount(() => {
  if (notificationTimer) {
    window.clearInterval(notificationTimer);
  }
});
</script>

<template>
  <div class="shell">
    <aside class="sidebar">
      <div class="brand">
        <ShieldCheck :size="24" />
        <div>
          <strong>ООО АЗЫК</strong>
          <span>Автотранспорт</span>
        </div>
      </div>

      <nav class="nav">
        <RouterLink
          v-for="item in navItems.filter((navItem) => canSee(navItem.roles))"
          :key="item.to"
          :to="item.to"
          class="nav-link"
        >
          <component :is="item.icon" :size="18" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
    </aside>

    <main class="content">
      <header class="topbar">
        <div>
          <strong>{{ auth.user?.full_name }}</strong>
          <span>{{ enumLabel(auth.user?.role) }}</span>
        </div>
        <button class="button secondary" type="button" @click="logout">
          <LogOut :size="18" />
          Выйти
        </button>
      </header>

      <section class="content-inner">
        <slot />
      </section>

      <div class="toast-stack" aria-live="polite">
        <button
          v-for="notification in notifications"
          :key="notification.id"
          class="toast"
          type="button"
          @click="openNotification(notification)"
        >
          <strong>{{ notification.title }}</strong>
          <span>{{ notification.text }}</span>
        </button>
      </div>
    </main>
  </div>
</template>

<style scoped>
.shell {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 100vh;
}

.sidebar {
  border-right: 1px solid #d9e1e8;
  background: #fff;
  padding: 18px 14px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 8px 18px;
  border-bottom: 1px solid #e4eaf0;
}

.brand strong,
.brand span {
  display: block;
}

.brand span {
  color: #687486;
  font-size: 12px;
}

.nav {
  display: grid;
  gap: 4px;
  margin-top: 14px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 38px;
  padding: 0 10px;
  border-radius: 6px;
  color: #354154;
  text-decoration: none;
}

.nav-link.router-link-active {
  background: #e7f3f3;
  color: #155e66;
  font-weight: 700;
}

.content {
  min-width: 0;
  position: relative;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 64px;
  padding: 0 22px;
  border-bottom: 1px solid #d9e1e8;
  background: #fff;
}

.topbar span {
  display: block;
  color: #687486;
  font-size: 12px;
}

.content-inner {
  padding: 22px;
}

.toast-stack {
  position: fixed;
  top: 78px;
  right: 22px;
  z-index: 40;
  display: grid;
  gap: 10px;
  width: min(360px, calc(100vw - 32px));
}

.toast {
  display: grid;
  gap: 4px;
  width: 100%;
  padding: 13px 14px;
  border: 1px solid #b7d7dc;
  border-radius: 8px;
  background: #f1fbfb;
  color: #18212f;
  text-align: left;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
}

.toast strong {
  font-size: 14px;
}

.toast span {
  color: #4a5567;
  font-size: 13px;
}

@media (max-width: 1000px) {
  .shell {
    grid-template-columns: 1fr;
  }

  .sidebar {
    position: static;
  }

  .nav {
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  }
}
</style>
