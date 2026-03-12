<template>
  <AppLayout>
    <Head title="Dashboard Árbitro" />

    <div class="page-content referee-dashboard">
      <div class="page-header flex justify-between items-center mb-md flex-wrap gap-md">
        <div>
          <h1 class="page-title text-gradient">Panel de Arbitraje</h1>
          <p class="text-secondary">Filtra tus partidas para mantener el arbitraje ordenado.</p>
        </div>
      </div>

      <div class="stats-strip mb-xl stagger">
        <div class="stat-mini card">
          <span class="k">Asignadas pendientes</span>
          <strong class="v">{{ stats.assigned_pending }}</strong>
        </div>
        <div class="stat-mini card">
          <span class="k">Pendientes de otros</span>
          <strong class="v text-accent-amber">{{ stats.others_pending }}</strong>
        </div>
        <div class="stat-mini card">
          <span class="k">Finalizadas</span>
          <strong class="v text-accent-green">{{ stats.completed }}</strong>
        </div>
      </div>

      <div class="filter-pills mb-lg stagger">
        <Link
          :href="route('referee.dashboard', { filter: 'assigned' })"
          class="btn btn-sm"
          :class="filter === 'assigned' ? 'btn-primary' : 'btn-ghost'"
        >
          Mis pendientes
        </Link>
        <Link
          :href="route('referee.dashboard', { filter: 'others' })"
          class="btn btn-sm"
          :class="filter === 'others' ? 'btn-primary' : 'btn-ghost'"
        >
          Pendientes de otros
        </Link>
        <Link
          :href="route('referee.dashboard', { filter: 'finalized' })"
          class="btn btn-sm"
          :class="filter === 'finalized' ? 'btn-primary' : 'btn-ghost'"
        >
          Finalizadas
        </Link>
      </div>

      <div v-if="!matches.length" class="card text-center py-2xl stagger">
        <div class="text-4xl mb-sm">📂</div>
        <p class="text-secondary">No hay partidas en este filtro.</p>
      </div>

      <div v-else class="match-grid stagger">
        <article v-for="m in matches" :key="m.id" class="referee-match-card card" :class="{ concluded: m.concluded }">
          <div class="match-top">
            <div class="left-meta">
              <span class="badge badge-blue">Ronda {{ m.round_no }}</span>
              <span v-if="m.is_recovery" class="badge badge-amber">Recuperación</span>
            </div>
            <span class="badge" :class="m.concluded ? 'badge-green' : 'badge-red'">
              {{ m.concluded ? 'Finalizada' : 'Pendiente' }}
            </span>
          </div>

          <p class="event-name">{{ m.event?.name }}</p>

          <div class="battle-row">
            <div class="fighter">
              <span class="name">{{ m.player_a?.blader_name || '-' }}</span>
              <small>{{ m.score_a }}</small>
            </div>
            <span class="versus">VS</span>
            <div class="fighter right">
              <span class="name">{{ m.player_b?.blader_name || '-' }}</span>
              <small>{{ m.score_b }}</small>
            </div>
          </div>

          <div class="card-actions">
            <button
              v-if="filter === 'others'"
              @click="takeMatch(m)"
              class="btn btn-primary btn-sm"
              :disabled="takingMatch === m.id"
              type="button"
            >
              {{ takingMatch === m.id ? 'Tomando...' : 'Tomar arbitraje' }}
            </button>

            <template v-else>
              <Link v-if="m.concluded" :href="route('referee.show', m.id)" class="btn btn-outline btn-sm">Ver registro</Link>
              <Link v-else :href="route('referee.match.panel', m.id)" class="btn btn-primary btn-sm">Arbitrar</Link>
            </template>
          </div>
        </article>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  matches: Array,
  filter: String,
  stats: Object,
});

const takingMatch = ref(null);

const takeMatch = (match) => {
  takingMatch.value = match.id;
  router.post(route('referee.match.take', match.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      takingMatch.value = null;
    },
  });
};
</script>

<style scoped>
.stats-strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-sm);
}

.stat-mini {
  display: grid;
  gap: 4px;
}

.stat-mini .k {
  font-size: 0.7rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.stat-mini .v {
  font-family: var(--font-display);
  font-size: 1.5rem;
}

.filter-pills {
  display: flex;
  gap: var(--space-sm);
  flex-wrap: wrap;
}

.match-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: var(--space-md);
}

.referee-match-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.referee-match-card.concluded {
  border-color: rgba(16, 185, 129, 0.35);
}

.match-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.left-meta {
  display: flex;
  gap: var(--space-xs);
  align-items: center;
}

.event-name {
  font-weight: 700;
  margin: 0;
}

.battle-row {
  display: grid;
  grid-template-columns: 1.2fr auto 1.2fr;
  align-items: center;
  gap: var(--space-md);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  padding: var(--space-sm);
}

.fighter {
  display: flex;
  justify-content: space-between;
  gap: var(--space-xs);
  align-items: center;
}

.fighter .name {
  font-weight: 800;
  font-size: 0.9rem;
}

.fighter.right {
  text-align: right;
}

.versus {
  opacity: 0.55;
  font-weight: 800;
}

.card-actions {
  margin-top: auto;
}

.card-actions .btn {
  width: 100%;
}

@media (max-width: 680px) {
  .stats-strip {
    grid-template-columns: 1fr;
  }
}
</style>
