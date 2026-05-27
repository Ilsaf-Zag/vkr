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
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

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

async function logout() {
  await auth.logout();
  await router.push('/login');
}
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
          <span>{{ auth.user?.role }}</span>
        </div>
        <button class="button secondary" type="button" @click="logout">
          <LogOut :size="18" />
          Выйти
        </button>
      </header>

      <section class="content-inner">
        <slot />
      </section>
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

