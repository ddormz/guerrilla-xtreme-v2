<template>
  <AppLayout>
    <Head title="Panel de Arbitraje" />

    <div class="referee-index page-content">
      <div class="page-header mb-xl animate-fade-in-up">
        <h1 class="text-gradient">Panel de Árbitro</h1>
        <p class="text-secondary">Selecciona un match asignado para comenzar el arbitraje.</p>
      </div>

      <div v-if="matches.length > 0" class="matches-list stagger">
        <div v-for="match in matches" :key="match.id" class="match-card card mb-lg card-hover">
          <div class="match-event-info mb-sm">
            <span class="badge badge-amber">{{ match.event?.name }}</span>
            <span class="text-muted text-sm ml-sm">Ronda {{ match.round_no }}</span>
          </div>
          
          <div class="match-players">
            <div class="player">
              <span class="player-name">{{ match.player_a?.blader_name || 'TBD' }}</span>
            </div>
            <div class="vs">VS</div>
            <div class="player text-right">
              <span class="player-name">{{ match.player_b?.blader_name || 'TBD' }}</span>
            </div>
          </div>

          <div class="match-footer mt-lg">
            <Link :href="route('referee.show', match.id)" class="btn btn-primary btn-block">
              Arbitrar Ahora
            </Link>
          </div>
        </div>
      </div>

      <div v-else class="empty-state card text-center p-2xl">
        <div class="empty-icon text-4xl mb-md">⚖️</div>
        <h3>No tienes matches pendientes</h3>
        <p class="text-secondary">Los matches asignados aparecerán aquí cuando el organizador los genere.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  matches: Array
});
</script>

<style scoped>
.match-card {
  border-left: 4px solid var(--accent-amber);
}

.match-players {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-md) 0;
}

.player {
  flex: 1;
}

.player-name {
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 700;
}

.vs {
  font-family: var(--font-display);
  font-weight: 800;
  color: var(--gx-red);
  font-size: 0.9rem;
  background: var(--bg-secondary);
  padding: 4px 8px;
  border-radius: var(--radius-sm);
}
</style>
