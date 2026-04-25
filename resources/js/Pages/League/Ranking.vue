<template>
  <AppLayout>
    <Head title="Ranking Global" />

    <div class="standings-page page-content">
      <div class="page-header mb-xl animate-fade-in-up">
        <h1 class="page-title ranking-title">
          <span class="title-icon">🏅</span>
          Ranking Global
        </h1>
        <p class="text-secondary">Clasificación global basada en diferencia de puntos. Desde Junio 2026.</p>
      </div>

      <!-- How it works -->
      <section class="card mb-xl stagger info-card">
        <div class="info-header">
          <span class="info-icon">📊</span>
          <div>
            <h3>¿Cómo funciona?</h3>
            <p class="text-secondary text-sm">El puntaje se calcula como <strong class="text-accent-green">puntos hechos</strong> menos <strong class="text-accent-red">puntos recibidos</strong>. Desempate por Xtremes.</p>
          </div>
        </div>
      </section>

      <!-- Ranking Table -->
      <div class="card p-0 overflow-hidden stagger">
        <div class="table-container pb-sm">
          <table class="gx-table min-w-[600px] md:min-w-full">
            <thead class="ranking-header">
              <tr>
                <th class="text-center w-16 px-4 py-4 text-xs tracking-widest uppercase">Rank</th>
                <th class="text-left px-4 py-4 text-base tracking-wide uppercase">Blader</th>
                <th class="text-center stats-col px-4 py-4" title="Victorias">
                  <span class="stat-icon win-icon">↑</span>
                </th>
                <th class="text-center stats-col px-4 py-4" title="Derrotas">
                  <span class="stat-icon loss-icon">↓</span>
                </th>
                <th class="text-center stats-col px-4 py-4" title="Puntos Hechos">
                  <span class="stat-icon pf-icon">⚔</span>
                </th>
                <th class="text-center stats-col px-4 py-4" title="Puntos en Contra">
                  <span class="stat-icon pa-icon">🛡</span>
                </th>
                <th class="text-center stats-col px-4 py-4" title="Diferencial">
                  <span class="stat-icon diff-icon">↕</span>
                </th>
                <th class="text-center stats-col px-4 py-4" title="Xtremes">
                  <span class="stat-icon xt-icon">⚡</span>
                </th>
              </tr>
            </thead>
            <tbody class="text-sm md:text-base">
              <tr v-for="(row, index) in standings" :key="row.id" class="table-row" :class="{ 'top-row': index < 3 }">
                <td class="text-center rank-col">
                  <span class="rank-badge" :class="rankClass(index)">{{ index + 1 }}</span>
                </td>
                <td>
                  <Link :href="route('league.players.show', row.player_id)" class="player-cell-link">
                    <div class="player-cell">
                      <div class="player-avatar" :class="rankClass(index)">
                        <img v-if="row.avatar_url" :src="row.avatar_url" alt="" @error="handleImageError" />
                        <span v-else>{{ row.player_name.charAt(0) }}</span>
                      </div>
                      <div class="flex flex-col">
                        <span class="player-name" :class="{ 'text-gold': index === 0 }">{{ row.player_name }}</span>
                        <span class="podium-message" :class="rankClass(index)">{{ podiumMessage(index) }}</span>
                      </div>
                    </div>
                  </Link>
                </td>
                <td class="text-center stat-wins font-bold">{{ row.wins }}</td>
                <td class="text-center stat-losses font-bold">{{ row.losses }}</td>
                <td class="text-center font-bold pf-value">{{ row.points_for }}</td>
                <td class="text-center font-bold pa-value">{{ row.points_against }}</td>
                <td class="text-center font-black">
                  <span class="diff-badge" :class="diffClass(row.differential)">
                    {{ row.differential > 0 ? '+' : '' }}{{ row.differential }}
                  </span>
                </td>
                <td class="text-center stat-xtremes font-black">⚡ {{ row.xtremes }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="standings.length === 0" class="p-2xl text-center text-muted empty-state">
          <div class="empty-icon">🏅</div>
          <p class="mt-md">Aún no hay datos de ranking.</p>
          <p class="text-sm text-secondary">El Ranking Global comenzará a registrar resultados desde Junio 2026 con los eventos <strong>Torneo + Ranking</strong>.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  standings: { type: Array, default: () => [] },
});

const rankClass = (index) => {
  if (index === 0) return 'rank-1';
  if (index === 1) return 'rank-2';
  if (index === 2) return 'rank-3';
  return '';
};

const diffClass = (diff) => {
  if (diff > 0) return 'diff-positive';
  if (diff < 0) return 'diff-negative';
  return 'diff-neutral';
};

const podiumMessage = (index) => {
  if (index === 0) return 'Líder del Ranking';
  if (index === 1) return 'Subcampeón';
  if (index === 2) return 'Tercera posición';
  return '';
};

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.ranking-title {
  color: var(--gx-red-light);
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.title-icon {
  font-size: 1.5rem;
}

.info-card {
  border-left: 4px solid var(--gx-red);
}

.info-header {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.info-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

/* Table Header */
.ranking-header {
  background: linear-gradient(135deg, #1a1a2e, #16213e);
  border-bottom: 2px solid var(--gx-red);
}

.ranking-header th {
  color: rgba(255, 255, 255, 0.85);
  font-weight: 700;
  font-size: 0.75rem;
  letter-spacing: 0.08em;
}

.stat-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 900;
}

.win-icon {
  color: #22c55e;
  background: rgba(34, 197, 94, 0.12);
}

.loss-icon {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.12);
}

.pf-icon {
  color: #a78bfa;
  background: rgba(167, 139, 250, 0.12);
}

.pa-icon {
  color: #f97316;
  background: rgba(249, 115, 22, 0.12);
}

.diff-icon {
  color: #06b6d4;
  background: rgba(6, 182, 212, 0.12);
}

.xt-icon {
  color: #ff3e3e;
  background: rgba(225, 6, 0, 0.15);
}

/* Table Styles */
.table-container {
  overflow-x: auto;
}

.gx-table {
  width: 100%;
  border-collapse: collapse;
}

.gx-table th,
.gx-table td {
  padding: var(--space-md);
  border-bottom: 1px solid var(--border-color);
}

.table-row:hover {
  background: rgba(225, 6, 0, 0.05);
}

.top-row {
  background: rgba(255, 215, 0, 0.02);
}

.player-cell-link {
  color: inherit;
}

.player-cell {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.player-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.player-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.player-name {
  font-weight: 800;
}

.text-gold {
  color: #ffd700;
}

.podium-message {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.09em;
  color: var(--text-muted);
}

.rank-badge {
  display: inline-flex;
  width: 36px;
  height: 36px;
  align-items: center;
  justify-content: center;
  background: #ef4444;
  border-radius: 8px;
  font-weight: 900;
  color: white;
}

.rank-1 {
  background: linear-gradient(135deg, #ffd700, #b8860b);
  color: #1f1f1f;
}

.rank-2 {
  background: linear-gradient(135deg, #e5e7eb, #9ca3af);
  color: #1f1f1f;
}

.rank-3 {
  background: linear-gradient(135deg, #cd7f32, #8b4513);
  color: #1f1f1f;
}

/* Stats */
.stat-wins {
  color: #22c55e;
  font-weight: 700;
}

.stat-losses {
  color: #ef4444;
  font-weight: 700;
}

.pf-value {
  color: #e2e8f0;
}

.pa-value {
  color: #94a3b8;
}

.stat-xtremes {
  color: #ff3e3e;
  text-shadow: 0 0 10px rgba(225, 6, 0, 0.4);
}

/* Differential Badge */
.diff-badge {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 900;
  letter-spacing: 0.02em;
}

.diff-positive {
  color: #22c55e;
  background: rgba(34, 197, 94, 0.12);
}

.diff-negative {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.12);
}

.diff-neutral {
  color: #94a3b8;
  background: rgba(148, 163, 184, 0.12);
}

/* Empty State */
.empty-state {
  padding: var(--space-2xl);
}

.empty-icon {
  font-size: 3rem;
  opacity: 0.5;
}

@media (max-width: 640px) {
  .info-header {
    flex-direction: column;
    text-align: center;
  }
}
</style>
