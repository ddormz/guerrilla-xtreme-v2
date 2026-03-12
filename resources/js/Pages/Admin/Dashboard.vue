<template>
  <AppLayout>
    <Head title="Admin Dashboard" />

    <div class="admin-dashboard page-content">
      <div class="page-header mb-xl animate-fade-in-up">
        <h1 class="text-gradient">Panel de Administración</h1>
        <p class="text-secondary">Visión general y gestión global de Guerrilla Xtrem.</p>
      </div>

      <!-- Quick Stats -->
      <div class="stats-grid stagger mb-3xl">
        <div class="stat-card card">
          <div class="stat-icon users-bg">👥</div>
          <div class="stat-info">
            <span class="stat-label">Usuarios</span>
            <h2 class="stat-value">{{ stats.users_count }}</h2>
          </div>
        </div>
        <div class="stat-card card">
          <div class="stat-icon raffle-bg">🎟️</div>
          <div class="stat-info">
            <span class="stat-label">Reservas Pendientes</span>
            <h2 class="stat-value">{{ stats.pending_reservations }}</h2>
          </div>
        </div>
        <div class="stat-card card">
          <div class="stat-icon event-bg">🏆</div>
          <div class="stat-info">
            <span class="stat-label">Siguiente Evento</span>
            <h2 class="stat-value text-sm-display">{{ stats.next_event }}</h2>
          </div>
        </div>
        <div class="stat-card card">
          <div class="stat-icon finance-bg">💰</div>
          <div class="stat-info">
            <span class="stat-label">Balance Total</span>
            <h2 class="stat-value">${{ formatAmount(stats.total_balance) }}</h2>
          </div>
        </div>
        <div class="stat-card card">
          <div class="stat-icon league-bg">⚽</div>
          <div class="stat-info">
            <span class="stat-label">Recaudación Liga</span>
            <h2 class="stat-value">${{ formatAmount(stats.league_revenue) }}</h2>
          </div>
        </div>
      </div>

        <!-- Main Actions Hub -->
        <div class="admin-main">
          <h2 class="mb-lg">Módulos de Gestión</h2>
          <div class="modules-grid stagger">
            <Link :href="route('admin.raffles.index')" class="module-card card card-hover">
              <div class="module-icon">🎟️</div>
              <div class="module-content">
                <h3>Rifas</h3>
                <p>Crear sorteos, validar pagos y realizar lanzamientos.</p>
              </div>
            </Link>
            <Link :href="route('admin.finance.index')" class="module-card card card-hover">
              <div class="module-icon">💰</div>
              <div class="module-content">
                <h3>Finanzas</h3>
                <p>Cajas, billeteras, y auditoría de ingresos y egresos.</p>
              </div>
            </Link>
            <Link :href="route('admin.league.index')" class="module-card card card-hover">
              <div class="module-icon">🏆</div>
              <div class="module-content">
                <h3>Liga & Torneos</h3>
                <p>Gestionar temporadas, eventos y brackets.</p>
              </div>
            </Link>
            <Link :href="route('admin.users.index')" class="module-card card card-hover">
              <div class="module-icon">👤</div>
              <div class="module-content">
                <h3>Usuarios</h3>
                <p>Ver cuentas, cambiar roles y resetear contraseñas.</p>
              </div>
            </Link>
            <Link :href="route('admin.members.index')" class="module-card card card-hover">
              <div class="module-icon">🥋</div>
              <div class="module-content">
                <h3>Roster del Equipo</h3>
                <p>Gestionar sección de miembros que aparecen en el Home.</p>
              </div>
            </Link>
            <Link :href="route('admin.audit.index')" class="module-card card card-hover">
              <div class="module-icon">📄</div>
              <div class="module-content">
                <h3>Auditoría de Acciones</h3>
                <p>Historial detallado de cambios y acciones administrativas.</p>
              </div>
            </Link>
      </div>
    </div>
  </div>
</AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  stats: Object,
  recentUsers: Array
});

const formatAmount = (val) => {
  return new Intl.NumberFormat('es-CL').format(val);
};
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: var(--space-xl);
}

.stat-card {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  border-bottom: 2px solid transparent;
}

.stat-card:hover { border-bottom-color: var(--gx-red); }

.stat-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  border-radius: var(--radius-md);
  background: var(--bg-secondary);
}

.users-bg { background: rgba(59, 130, 246, 0.1); }
.raffle-bg { background: rgba(225, 6, 0, 0.1); }
.event-bg { background: rgba(245, 158, 11, 0.1); }
.finance-bg { background: rgba(16, 185, 129, 0.1); }
.league-bg { background: rgba(245, 158, 11, 0.1); }

.stat-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
}

.stat-value {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.5rem;
}

.text-sm-display { font-size: 1rem; }

.admin-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);
}

@media (min-width: 992px) {
  .admin-layout {
    grid-template-columns: 1fr 300px;
  }
}

.modules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--space-lg);
}

.module-card {
  display: flex;
  gap: var(--space-md);
  text-decoration: none;
  color: inherit;
}

.module-icon {
  font-size: 2rem;
  background: var(--bg-secondary);
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  flex-shrink: 0;
}

.user-item {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: 8px 0;
  border-bottom: 1px solid var(--border-color);
}

.user-avatar-small {
  width: 32px;
  height: 32px;
  background: var(--bg-elevated);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.8rem;
}

.user-name { font-weight: 600; font-size: 0.9rem; display: block; }
.user-date { font-size: 0.7rem; color: var(--text-muted); }
</style>
