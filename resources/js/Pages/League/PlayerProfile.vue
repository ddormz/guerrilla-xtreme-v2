<template>
  <AppLayout>
    <Head :title="`Perfil - ${player.display_name}`" />

    <div class="page-content">
      <div class="mb-lg">
        <Link :href="route('league.standings')" class="btn btn-ghost btn-sm">← Volver a la liga</Link>
      </div>

      <section class="card profile-header mb-xl">
        <div class="avatar-wrap">
          <img v-if="player.avatar_url" :src="player.avatar_url" class="avatar" alt="avatar" @error="handleImageError" />
          <div v-else class="avatar avatar-fallback">{{ player.display_name.charAt(0) }}</div>
        </div>
        <div>
          <h1 class="text-gradient">{{ player.display_name }}</h1>
          <div class="flex items-center gap-sm mt-xs">
            <p class="text-secondary">{{ player.name }}</p>
            <span v-if="player.win_rate !== undefined" class="badge badge-indigo">Win Rate: {{ player.win_rate }}%</span>
          </div>
        </div>
      </section>

      <section class="stats-grid mb-xl">
        <div class="card stat-item">
          <div class="label">Puntos</div>
          <div class="value gold">{{ stats.points }}</div>
        </div>
        <div class="card stat-item">
          <div class="label">Ganadas</div>
          <div class="value green">{{ stats.wins }}</div>
        </div>
        <div class="card stat-item">
          <div class="label">Perdidas</div>
          <div class="value red">{{ stats.losses }}</div>
        </div>
        <div class="card stat-item">
          <div class="label">Xtremes</div>
          <div class="value gold">{{ stats.xtremes }}</div>
        </div>
        <div class="card stat-item">
          <div class="label">Win Rate</div>
          <div class="value indigo">{{ player.win_rate }}%</div>
        </div>
      </section>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-xl mb-xl">
        <section class="card family-card">
          <h2 class="section-title-sm mb-md">⚔️ Rivalidades (Kinship)</h2>
          <div class="space-y-md">
            <div class="family-item">
              <span class="family-label text-red-400">HIJO DE (Nemesis):</span>
              <div v-if="player.nemesis" class="family-node active nemesis">
                <Link :href="route('league.players.show', player.nemesis.id)" class="family-link">
                  <span class="family-icon">💀</span> {{ player.nemesis.name }}
                </Link>
                <small class="text-xs opacity-60 block mt-xs">Quien más veces te ha ganado</small>
              </div>
              <div v-else class="family-node empty">Ninguno (¡Invicto!)</div>
            </div>
            
            <div class="family-item">
              <span class="family-label text-green-400">PAPÁ DE (Víctima):</span>
              <div v-if="player.victim" class="family-node active victim">
                <Link :href="route('league.players.show', player.victim.id)" class="family-link">
                  <span class="family-icon">👑</span> {{ player.victim.name }}
                </Link>
                <small class="text-xs opacity-60 block mt-xs">A quien más veces le has ganado</small>
              </div>
              <div v-else class="family-node empty">Nadie aún</div>
            </div>
          </div>
        </section>

        <section class="card achievements-card">
          <h2 class="section-title-sm mb-md">🏆 Logros y Récords</h2>
          <div class="flex flex-wrap gap-sm">
            <!-- Global Records -->
            <span v-if="globalLeaders?.xtremes" class="badge badge-gold" title="Máximo anotador de Xtremes">👑 REY XTREME</span>
            <span v-if="globalLeaders?.overs" class="badge badge-amber" title="Máximo anotador de Overs">🚜 MÁQUINA OVER</span>
            <span v-if="globalLeaders?.bursts" class="badge badge-red" title="Máximo anotador de Bursts">💥 DEMOLEDOR BURST</span>
            <span v-if="globalLeaders?.wins" class="badge badge-indigo" title="Líder en Victorias">🎖️ GENERAL DE GUERRA</span>
            
            <!-- Milestones -->
            <span v-if="stats.wins >= 50" class="badge badge-gold" title="50+ Victorias">Veterano de Guerra</span>
            <span v-if="stats.xtremes >= 20" class="badge badge-red" title="20+ Xtremes">Maestro Xtreme</span>
            <span v-if="player.win_rate >= 75 && stats.wins >= 10" class="badge badge-indigo" title="75%+ Win Rate">Invencible</span>
            <span v-if="rankingHistory.some(r => r.rank === 1)" class="badge badge-amber">CAMPEÓN DE TEMPORADA</span>
            
            <div v-if="!stats.wins && !Object.values(globalLeaders || {}).some(v => v)" class="text-secondary text-sm">Aún no hay logros registrados.</div>
          </div>
        </section>
      </div>

      <section class="card mb-xl">
        <h2 class="mb-md">Historial de Ranking</h2>
        <div class="table-wrap">
          <table class="gx-table">
            <thead>
              <tr>
                <th>Temporada</th>
                <th>Ranking</th>
                <th>Puntos</th>
                <th>G/P</th>
                <th>Xtremes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rankingHistory" :key="`${row.season}-${row.rank}`">
                <td>{{ row.season }}</td>
                <td>{{ row.rank ? `${row.rank}°` : '-' }}</td>
                <td>{{ row.points }}</td>
                <td>{{ row.wins }} / {{ row.losses }}</td>
                <td>{{ row.xtremes }}</td>
              </tr>
              <tr v-if="rankingHistory.length === 0">
                <td colspan="5" class="text-center text-secondary">Sin historial disponible.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card">
        <h2 class="mb-md">Partidas recientes</h2>
        <div class="space-y-sm">
          <div v-for="match in recentMatches" :key="match.id" class="recent-item">
            <div>
              <div class="font-bold">vs {{ match.opponent }}</div>
              <div class="text-xs text-secondary">{{ match.event_name }} · {{ match.event_date }}</div>
            </div>
            <div class="text-right">
              <span class="badge" :class="match.result === 'W' ? 'badge-green' : 'badge-red'">
                {{ match.result === 'W' ? 'Victoria' : 'Derrota' }}
              </span>
              <div class="font-bold mt-xs">{{ match.score }}</div>
            </div>
          </div>
          <div v-if="recentMatches.length === 0" class="text-secondary">No hay partidas recientes.</div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  player: Object,
  stats: Object,
  rankingHistory: Array,
  recentMatches: Array,
  globalLeaders: Object,
});

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.profile-header {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
}

.avatar-wrap {
  width: 96px;
  height: 96px;
}

.avatar {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--gx-red);
}

.avatar-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  background: rgba(255, 255, 255, 0.08);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--space-md);
}

.label {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.value {
  font-size: 2rem;
  font-weight: 900;
}

.value.gold { color: #ffd700; }
.value.red { color: #ef4444; }
.value.indigo { color: #818cf8; }

.section-title-sm {
  font-size: 1rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
}

.family-card {
  border-left: 4px solid var(--gx-red);
}

.achievements-card {
  padding-top: var(--space-xl) !important;
}

.nemesis { border-left: 3px solid #ef4444; }
.victim { border-left: 3px solid #22c55e; }

.family-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.family-label {
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.family-node {
  padding: 10px 14px;
  border-radius: var(--radius-md);
  font-size: 0.95rem;
  transition: all 0.2s ease;
}

.family-node.active {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.family-node.active:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--gx-red);
}

.family-node.empty {
  background: rgba(0, 0, 0, 0.2);
  color: var(--text-muted);
  border: 1px dashed var(--border-color);
  font-size: 0.85rem;
}

.family-link {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
}

.family-icon {
  opacity: 0.6;
}

.children-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.table-wrap {
  overflow-x: auto;
}

.gx-table {
  width: 100%;
  border-collapse: collapse;
}

.gx-table th,
.gx-table td {
  padding: var(--space-sm);
  border-bottom: 1px solid var(--border-color);
}

.recent-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  padding: var(--space-sm);
  background: rgba(255, 255, 255, 0.02);
}
</style>
