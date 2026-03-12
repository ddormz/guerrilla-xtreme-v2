<template>
  <AppLayout>
    <Head :title="`Registro Match #${match.id}`" />

    <div class="page-content referee-show-page">
      <div class="top-actions">
        <Link :href="route('referee.dashboard', { filter: 'finalized' })" class="btn btn-secondary btn-sm">← Volver</Link>
      </div>

      <section class="card summary-card prestigious-border">
        <div class="summary-head">
          <div class="flex items-center gap-md">
            <div class="gx-logo-small">
              <img src="/img/logo.png" alt="GX">
            </div>
            <div>
              <h1 class="text-gradient">Registro Oficial de Combate</h1>
              <p class="text-xs tracking-widest uppercase font-black opacity-60">Match #{{ match.id }} | {{ match.event?.name || 'Evento' }}</p>
            </div>
          </div>
          <div class="summary-badges">
            <span class="badge" :class="eventType === 'torneo' ? 'badge-amber' : 'badge-blue'">{{ eventType.toUpperCase() }}</span>
            <span v-if="match.is_recovery" class="badge badge-amber">RECUPERACION</span>
            <span class="badge badge-green">VERIFICADO</span>
          </div>
        </div>

        <div class="scoreboard-ultra">
          <div class="fighter-display left" :class="{ winner: isWinner('a') }">
            <div class="fighter-meta">
              <h3>{{ match.player_a?.blader_name || 'Jugador A' }}</h3>
              <p>{{ match.player_a?.user?.name || '-' }}</p>
            </div>
            <div class="fighter-avatar-large">
              <img v-if="match.player_a?.avatar_path || match.player_a?.user?.avatar_path" 
                   :src="`/storage/${match.player_a.avatar_path || match.player_a.user.avatar_path}`" 
                   @error="handleImageError">
              <span v-else>{{ (match.player_a?.blader_name || 'A').charAt(0) }}</span>
              <div v-if="isWinner('a')" class="winner-crown">🏆</div>
            </div>
          </div>

          <div class="score-container">
            <div class="vs-label">VS</div>
            <div class="main-score-display">
              <span class="score-digit" :class="{ 'text-accent-green': isWinner('a') }">{{ match.score_a }}</span>
              <span class="score-divider">-</span>
              <span class="score-digit" :class="{ 'text-accent-green': isWinner('b') }">{{ match.score_b }}</span>
            </div>
            <div class="match-duration">
              <span>Arbitrado por</span>
              <strong>{{ match.referee?.name || 'GXTREME STAFF' }}</strong>
            </div>
          </div>

          <div class="fighter-display right" :class="{ winner: isWinner('b') }">
            <div class="fighter-avatar-large">
              <img v-if="match.player_b?.avatar_path || match.player_b?.user?.avatar_path" 
                   :src="`/storage/${match.player_b.avatar_path || match.player_b.user.avatar_path}`" 
                   @error="handleImageError">
              <span v-else>{{ (match.player_b?.blader_name || 'B').charAt(0) }}</span>
              <div v-if="isWinner('b')" class="winner-crown">🏆</div>
            </div>
            <div class="fighter-meta">
              <h3>{{ match.player_b?.blader_name || 'Jugador B' }}</h3>
              <p>{{ match.player_b?.user?.name || '-' }}</p>
            </div>
          </div>
        </div>

        <div class="detailed-stats-grid">
          <div class="stat-row">
            <div class="stat-label">SPIN FINISH</div>
            <div class="stat-values">
              <span class="val">{{ match.spin_a }}</span>
              <div class="stat-bar"><div class="bar-fill a" :style="{ width: percent(match.spin_a, match.spin_a + match.spin_b) }"></div><div class="bar-fill b" :style="{ width: percent(match.spin_b, match.spin_a + match.spin_b) }"></div></div>
              <span class="val">{{ match.spin_b }}</span>
            </div>
          </div>
          <div class="stat-row">
            <div class="stat-label">OVER FINISH</div>
            <div class="stat-values">
              <span class="val">{{ match.over_a }}</span>
              <div class="stat-bar"><div class="bar-fill a" :style="{ width: percent(match.over_a, match.over_a + match.over_b) }"></div><div class="bar-fill b" :style="{ width: percent(match.over_b, match.over_a + match.over_b) }"></div></div>
              <span class="val">{{ match.over_b }}</span>
            </div>
          </div>
          <div class="stat-row">
            <div class="stat-label">BURST FINISH</div>
            <div class="stat-values">
              <span class="val">{{ match.burst_a }}</span>
              <div class="stat-bar"><div class="bar-fill a" :style="{ width: percent(match.burst_a, match.burst_a + match.burst_b) }"></div><div class="bar-fill b" :style="{ width: percent(match.burst_b, match.burst_a + match.burst_b) }"></div></div>
              <span class="val">{{ match.burst_b }}</span>
            </div>
          </div>
          <div class="stat-row">
            <div class="stat-label">XTREME FINISH</div>
            <div class="stat-values">
              <span class="val">{{ match.xtreme_a }}</span>
              <div class="stat-bar"><div class="bar-fill a" :style="{ width: percent(match.xtreme_a, match.xtreme_a + match.xtreme_b) }"></div><div class="bar-fill b" :style="{ width: percent(match.xtreme_b, match.xtreme_a + match.xtreme_b) }"></div></div>
              <span class="val">{{ match.xtreme_b }}</span>
            </div>
          </div>
        </div>
      </section>

      <div class="logs-section">
        <section class="card detail-card">
          <div class="flex justify-between items-center mb-md">
            <h2 class="section-title">DETALLE DE COMBATE</h2>
            <div class="date-stamp">{{ formatDate(match.concluded_at) }}</div>
          </div>
          <div class="timeline-v2">
            <div v-for="(act, idx) in sortedActions" :key="act.id" class="timeline-entry" :class="act.side">
              <div class="entry-time">{{ formatTime(act.created_at) }}</div>
              <div class="entry-content">
                <div class="entry-player">{{ act.side === 'a' ? match.player_a?.blader_name : match.player_b?.blader_name }}</div>
                <div class="entry-action" :class="act.action_type">
                  {{ actionText(act.action_type) }}
                </div>
              </div>
              <div class="entry-dot" :class="act.action_type"></div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  match: Object,
  actions: Array,
});

const eventType = computed(() => props.match.event?.event_type?.value || props.match.event?.event_type || 'liga');

const sortedActions = computed(() => {
  return [...(props.actions || [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const winnerName = computed(() => {
  if (!props.match?.winner_id) return '-';
  if (props.match.winner_id === props.match.player_a_id) return props.match.player_a?.blader_name || 'Jugador A';
  if (props.match.winner_id === props.match.player_b_id) return props.match.player_b?.blader_name || 'Jugador B';
  return '-';
});

const isWinner = (side) => {
  if (!props.match?.winner_id) return false;
  return side === 'a'
    ? props.match.winner_id === props.match.player_a_id
    : props.match.winner_id === props.match.player_b_id;
};

const actionText = (type) => {
  const map = {
    spin: 'Punto por Spin Finish',
    over: 'Punto por Over Finish',
    burst: 'Punto por Burst Finish',
    xtreme: 'Punto por Xtreme Finish',
    strike: 'Strike Recibido',
  };
  return map[type] || type;
};

const percent = (val, total) => {
  if (total === 0) return '50%';
  return (val / total * 100) + '%';
};

const formatDate = (value) => {
  if (!value) return '-';
  return new Date(value).toLocaleString('es-CL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatTime = (value) => {
  if (!value) return '--:--';
  return new Date(value).toLocaleTimeString('es-CL', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.summary-card {
  padding: var(--space-xl);
}

.prestigious-border {
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: linear-gradient(135deg, rgba(23, 23, 23, 0.95) 0%, rgba(13, 13, 13, 1) 100%);
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
}

.gx-logo-small {
  width: 50px;
  height: 50px;
}

.gx-logo-small img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.scoreboard-ultra {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: var(--space-xl);
  margin: var(--space-xl) 0;
  padding: var(--space-lg);
  background: rgba(255,255,255,0.02);
  border-radius: var(--radius-lg);
}

.fighter-display {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
}

.fighter-display.right {
  flex-direction: row-reverse;
  text-align: right;
}

.fighter-avatar-large {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  border: 4px solid var(--border-color);
  background: var(--bg-card);
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 900;
  box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}

.fighter-display.winner .fighter-avatar-large {
  border-color: var(--accent-green);
  box-shadow: 0 0 30px rgba(16, 185, 129, 0.2);
}

.fighter-avatar-large img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.winner-crown {
  position: absolute;
  top: -5px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 1.5rem;
}

.fighter-meta h3 {
  font-size: 1.4rem;
  font-weight: 900;
  margin: 0;
  background: linear-gradient(to bottom, #fff, #aaa);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.fighter-meta p {
  margin: 2px 0 0;
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.score-container {
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.vs-label {
  font-weight: 900;
  opacity: 0.3;
  letter-spacing: 0.2em;
  font-size: 1rem;
}

.main-score-display {
  font-size: 4rem;
  font-weight: 900;
  font-family: var(--font-display);
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  line-height: 1;
}

.score-digit {
  width: 70px;
}

.score-divider {
  opacity: 0.2;
}

.match-duration {
  font-size: 0.65rem;
  color: var(--text-secondary);
  text-transform: uppercase;
}

.match-duration strong {
  display: block;
  font-size: 0.8rem;
  color: var(--text-primary);
}

.detailed-stats-grid {
  display: grid;
  gap: var(--space-md);
  max-width: 600px;
  margin: 0 auto;
}

.stat-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 0.65rem;
  font-weight: 900;
  letter-spacing: 0.1em;
  color: var(--text-secondary);
  text-align: center;
}

.stat-values {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.stat-bar {
  flex: 1;
  height: 8px;
  background: rgba(255,255,255,0.05);
  border-radius: 4px;
  display: flex;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
}

.bar-fill.a { background: var(--accent-blue); }
.bar-fill.b { background: var(--accent-purple); }

.val {
  font-weight: 900;
  font-family: var(--font-display);
  width: 20px;
  text-align: center;
}

.section-title {
  font-size: 0.8rem;
  font-weight: 900;
  letter-spacing: 0.2em;
  color: var(--text-secondary);
}

.date-stamp {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.timeline-v2 {
  display: grid;
  gap: var(--space-sm);
}

.timeline-entry {
  display: grid;
  grid-template-columns: 80px 1fr 20px;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  background: rgba(255,255,255,0.02);
  border-left: 3px solid transparent;
}

.timeline-entry.a { border-left-color: var(--accent-blue); }
.timeline-entry.b { border-left-color: var(--accent-purple); }

.entry-time {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--text-muted);
}

.entry-player {
  font-size: 0.75rem;
  font-weight: 900;
  text-transform: uppercase;
}

.entry-action {
  font-size: 0.9rem;
  font-weight: 600;
}

.entry-action.spin { color: var(--accent-blue); }
.entry-action.over { color: var(--accent-amber); }
.entry-action.burst { color: var(--gx-red); }
.entry-action.xtreme { color: var(--accent-green); }
.entry-action.strike { color: var(--text-muted); }

.entry-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--border-color);
}

.entry-dot.spin { background: var(--accent-blue); }
.entry-dot.over { background: var(--accent-amber); }
.entry-dot.burst { background: var(--gx-red); }
.entry-dot.xtreme { background: var(--accent-green); }

@media (max-width: 800px) {
  .scoreboard-ultra {
    grid-template-columns: 1fr;
    gap: var(--space-lg);
  }
  .fighter-display, .fighter-display.right {
    flex-direction: column;
    text-align: center;
  }
  .score-container { order: -1; }
  .main-score-display { justify-content: center; }
}
</style>

