<template>
  <AppLayout>
    <Head title="Eventos" />

    <div class="events-page page-content">
      <div class="page-header mb-xl animate-fade-in-up">
        <h1 class="text-gradient">Cronograma de Eventos</h1>
        <p class="text-secondary">Próximos torneos, ligas y encuentros de la comunidad.</p>
      </div>

      <div class="events-list stagger">
        <div v-for="event in events" :key="event.id" class="event-card card mb-lg" :class="{ 'live-card': event.is_live }">
          <div class="event-main">
            <div class="event-date">
              <span class="date-day">{{ formatDay(event.date) }}</span>
              <span class="date-month">{{ formatMonth(event.date) }}</span>
            </div>
            <div class="event-content">
              <div class="event-type-badge mb-xs">
                <span class="badge" :class="getTypeClass(event.type)">{{ event.type }}</span>
                <span v-if="event.is_live" class="live-indicator ml-sm">
                  <span class="dot"></span> EN VIVO
                </span>
              </div>
              <h2 class="event-name">{{ event.name }}</h2>
              <p class="event-meta text-secondary flex-wrap">
                <span class="meta-season" v-if="event.season_name">🏆 {{ event.season_name }}</span>
                <span class="meta-time" v-if="event.date">🕒 {{ formatTime(event.date) }}</span>
                <span class="meta-location" v-if="event.location">📍 {{ event.location }}</span>
                <span class="meta-cost text-accent-green font-bold" v-if="event.registration_cost > 0">💰 ${{ formatCLP(event.registration_cost) }}</span>
              </p>
              <div v-if="event.prizes" class="mt-sm p-sm bg-black/20 rounded border border-white/5">
                <div class="text-xs uppercase tracking-wider text-gold font-bold mb-xs">🎁 Premios</div>
                <div class="text-sm text-secondary">{{ event.prizes }}</div>
              </div>
              <div v-if="event.description" class="text-sm text-secondary mt-sm line-clamp-2 max-w-2xl">
                {{ event.description }}
              </div>
            </div>
            <div class="event-actions flex flex-col gap-sm">
              <Link :href="route('tournaments.register', event.id)" class="btn btn-primary" v-if="event.type === 'torneo'">
                Pre-registrar
              </Link>
              <Link v-if="event.is_live" href="/live" class="btn btn-outline btn-sm">
                Ver Live
              </Link>
            </div>
          </div>
        </div>

        <div v-if="events.length === 0" class="empty-state card text-center p-2xl">
          <div class="empty-icon text-4xl mb-md">📅</div>
          <h3>Sin eventos próximos</h3>
          <p class="text-secondary">Estamos planeando el siguiente gran encuentro. ¡Atento!</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  events: Array
});

const getTypeClass = (type) => {
  return type === 'torneo' ? 'badge-amber' : 'badge-blue';
};

const formatDay = (dateStr) => {
  if (!dateStr) return '??';
  const date = parseDate(dateStr);
  return date.getDate();
};

const formatMonth = (dateStr) => {
  if (!dateStr) return '???';
  const date = parseDate(dateStr);
  return date.toLocaleString('es-CL', { month: 'short' }).toUpperCase();
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  const date = parseDate(dateStr);
  return date.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
};

const formatCLP = (value) => {
  return new Intl.NumberFormat('es-CL').format(Math.floor(value));
};

const parseDate = (dateStr) => {
  // Format is "d/m/Y H:i"
  const [datePart, timePart] = dateStr.split(' ');
  const [d, m, y] = datePart.split('/');
  return new Date(`${y}-${m}-${d}T${timePart || '00:00'}`);
};
</script>

<style scoped>
.event-card {
  transition: all var(--transition-base);
  border-left: 4px solid var(--border-color);
}

.event-card:hover {
  border-left-color: var(--gx-red);
}

.live-card {
  border-left-color: var(--gx-red);
  box-shadow: var(--shadow-glow);
}

.event-main {
  display: flex;
  align-items: center;
  gap: var(--space-xl);
}

.event-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
  flex-shrink: 0;
}

.date-day {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
}

.date-month {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--gx-red-light);
}

.event-content {
  flex: 1;
}

.event-name {
  font-size: 1.25rem;
  margin-bottom: 4px;
}

.event-meta {
  font-size: 0.85rem;
  display: flex;
  gap: var(--space-md);
}

.live-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--gx-red-light);
  font-weight: 700;
  font-size: 0.7rem;
}

.dot {
  width: 8px;
  height: 8px;
  background: var(--gx-red);
  border-radius: 50%;
  animation: pulse 1s infinite;
}

.event-actions {
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .event-main {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: var(--space-lg);
  }
  
  .event-meta {
    justify-content: center;
  }

  .event-actions {
    width: 100%;
  }

  .event-actions .btn {
    width: 100%;
  }
}
</style>
