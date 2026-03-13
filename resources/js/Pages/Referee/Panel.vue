<template>
  <AppLayout>
    <Head :title="`Arbitraje Match #${matchData.id}`" />

    <div class="referee-panel-container page-content">
      <div class="panel-top card">
        <div class="top-left">
          <Link :href="route('referee.dashboard', { filter: 'assigned' })" class="btn btn-secondary btn-sm">← Volver</Link>
        </div>

        <div class="top-center">
          <h1>Match #{{ matchData.id }}</h1>
          <p>{{ matchData.event?.name }}</p>
          <div class="top-badges">
            <span class="badge" :class="eventType === 'torneo' ? 'badge-amber' : 'badge-blue'">{{ eventType.toUpperCase() }}</span>
            <span v-if="matchData.is_recovery" class="badge badge-amber">RECUPERACIÓN</span>
            <span v-if="matchData.concluded" class="badge badge-green">FINALIZADO</span>
            <span v-if="matchData.referee" class="badge badge-indigo">ÁRBITRO: {{ matchData.referee.blader_name || matchData.referee.name }}</span>
          </div>
        </div>

        <div class="top-right">
          <span v-if="!canArbitrate" class="badge badge-blue">Solo lectura</span>
        </div>
      </div>

      <div class="arena-grid">
        <section class="fighter-card card side-a" :class="{ winner: isWinner('a'), loser: isLoser('a') }">
          <div class="fighter-head">
            <div class="avatar-shell">
              <img v-if="matchData.player_a?.user?.avatar_path" :src="`/storage/${matchData.player_a.user.avatar_path}`" class="avatar" @error="handleImageError">
              <span v-else>{{ (matchData.player_a?.blader_name || 'A').substring(0, 2).toUpperCase() }}</span>
            </div>
            <div>
              <h3>{{ matchData.player_a?.blader_name }}</h3>
              <small>{{ matchData.player_a?.user?.name }}</small>
            </div>
          </div>

          <div class="score-box">
            <strong>{{ matchData.score_a }}</strong>
            <span>Puntos</span>
          </div>

          <div v-if="canArbitrate && !matchData.concluded" class="actions-grid">
            <button @click="addAction('a', 'spin')" class="action-btn spin" type="button">+1 Spin</button>
            <button @click="addAction('a', 'over')" class="action-btn over" type="button">+2 Over</button>
            <button @click="addAction('a', 'burst')" class="action-btn burst" type="button">+2 Burst</button>
            <button @click="addAction('a', 'xtreme')" class="action-btn xtreme" type="button">+3 Xtreme</button>
            <button @click="addAction('a', 'strike')" class="action-btn strike" type="button">Strike</button>
          </div>

          <div class="mini-stats">
            <span>Spin {{ matchData.spin_a }}</span>
            <span>Over {{ matchData.over_a }}</span>
            <span>Burst {{ matchData.burst_a }}</span>
            <span>Xtreme {{ matchData.xtreme_a }}</span>
            <span>Strikes {{ matchData.strikes_a }}</span>
          </div>
        </section>

        <div class="vs-col">
          <div class="vs">VS</div>
          <div class="live-score" v-if="matchData.concluded">
            {{ matchData.score_a }} - {{ matchData.score_b }}
          </div>
        </div>

        <section class="fighter-card card side-b" :class="{ winner: isWinner('b'), loser: isLoser('b') }">
          <div class="fighter-head reverse">
            <div class="avatar-shell">
              <img v-if="matchData.player_b?.user?.avatar_path" :src="`/storage/${matchData.player_b.user.avatar_path}`" class="avatar" @error="handleImageError">
              <span v-else>{{ (matchData.player_b?.blader_name || 'B').substring(0, 2).toUpperCase() }}</span>
            </div>
            <div>
              <h3>{{ matchData.player_b?.blader_name }}</h3>
              <small>{{ matchData.player_b?.user?.name }}</small>
            </div>
          </div>

          <div class="score-box">
            <strong>{{ matchData.score_b }}</strong>
            <span>Puntos</span>
          </div>

          <div v-if="canArbitrate && !matchData.concluded" class="actions-grid">
            <button @click="addAction('b', 'spin')" class="action-btn spin" type="button">+1 Spin</button>
            <button @click="addAction('b', 'over')" class="action-btn over" type="button">+2 Over</button>
            <button @click="addAction('b', 'burst')" class="action-btn burst" type="button">+2 Burst</button>
            <button @click="addAction('b', 'xtreme')" class="action-btn xtreme" type="button">+3 Xtreme</button>
            <button @click="addAction('b', 'strike')" class="action-btn strike" type="button">Strike</button>
          </div>

          <div class="mini-stats">
            <span>Spin {{ matchData.spin_b }}</span>
            <span>Over {{ matchData.over_b }}</span>
            <span>Burst {{ matchData.burst_b }}</span>
            <span>Xtreme {{ matchData.xtreme_b }}</span>
            <span>Strikes {{ matchData.strikes_b }}</span>
          </div>
        </section>
      </div>

      <div v-if="canArbitrate" class="control-bar card">
        <button v-if="!matchData.concluded" @click="undoLast" class="btn btn-secondary" :disabled="processing" type="button">Deshacer última acción</button>
        <button v-if="!matchData.concluded" @click="finalizeMatch" class="btn btn-primary" :disabled="processing" type="button">Finalizar match</button>
        <button v-else @click="undoLast" class="btn btn-outline text-red-400 border-red-500/40" :disabled="processing" type="button">Reabrir / deshacer</button>
        <button @click="resetMatch" class="btn btn-ghost text-red-500 font-bold ml-auto" :disabled="processing" type="button">Reiniciar Match (Borrar todo)</button>
      </div>

      <section class="card action-log-card">
        <h4>Registro de acciones</h4>
        <div class="log-list">
          <div v-if="!sortedActions.length" class="text-secondary text-sm">No hay acciones registradas.</div>
          <article v-for="act in sortedActions" :key="act.id" class="log-row" :class="act.side === 'a' ? 'a' : 'b'">
            <div class="flex items-center gap-xs">
              <strong>{{ act.side === 'a' ? matchData.player_a?.blader_name : matchData.player_b?.blader_name }}</strong>
              <span class="text-secondary mx-xs font-bold">-</span>
              <span class="badge-mini" :class="act.action_type">{{ act.action_type.toUpperCase() }}</span>
            </div>
            <small>{{ formatTime(act.created_at) }}</small>
          </article>
        </div>
      </section>

      <div v-if="showWinnerModal" class="winner-modal-overlay" @click.self="goToMatchList">
        <div class="winner-modal card">
          <div class="winner-icon">🏆</div>
          <h2 class="winner-title">Partida finalizada</h2>
          <p class="winner-name">{{ winnerModal.winnerName }}</p>
          <p class="winner-score">Marcador: {{ winnerModal.scoreA }} - {{ winnerModal.scoreB }}</p>

          <div class="winner-actions">
            <button
              type="button"
              class="btn btn-primary"
              @click="goToNextAssignedMatch"
            >
              {{ winnerModal.nextMatchUrl ? 'Seguir al siguiente match asignado' : 'No hay siguiente, ver listado' }}
            </button>
            <button type="button" class="btn btn-outline" @click="goToMatchList">Ver listado de partidas</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  matchData: Object,
  actionsList: Array,
  canArbitrate: Boolean,
});

const { ask } = useConfirm();
const { warning: toastWarning, success: toastSuccess, error: toastError } = useToast();
const processing = ref(false);
const showWinnerModal = ref(false);
const winnerModal = ref({
  winnerName: '',
  scoreA: 0,
  scoreB: 0,
  nextMatchUrl: null,
  listUrl: route('referee.dashboard', { filter: 'assigned' }),
});

const eventType = computed(() => props.matchData.event?.event_type?.value || props.matchData.event?.event_type || 'liga');

const sortedActions = computed(() => {
  return [...props.actionsList].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const isWinner = (side) => {
  if (!props.matchData.concluded || !props.matchData.winner_id) return false;
  return side === 'a' ? props.matchData.winner_id === props.matchData.player_a_id : props.matchData.winner_id === props.matchData.player_b_id;
};

const isLoser = (side) => {
  if (!props.matchData.concluded || !props.matchData.winner_id) return false;
  return side === 'a' ? props.matchData.winner_id !== props.matchData.player_a_id : props.matchData.winner_id !== props.matchData.player_b_id;
};

const addAction = (side, type) => {
  if (processing.value) return;
  processing.value = true;
  router.post(route('referee.match.action', props.matchData.id), {
    side,
    action_type: type,
  }, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false;
    },
  });
};

const undoLast = async () => {
  if (processing.value) return;

  processing.value = true;
  router.post(route('referee.match.undo', props.matchData.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false;
    },
  });
};

const resetMatch = async () => {
  if (processing.value) return;

  const confirmed = await ask({
    title: 'Reiniciar Match',
    message: 'Esta acción borrará todas las acciones y volverá el puntaje a cero. ¿Estás seguro?',
    confirmText: 'Sí, reiniciar',
    tone: 'danger',
  });

  if (!confirmed) return;

  processing.value = true;
  router.post(route('referee.match.reset', props.matchData.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false;
    },
  });
};

const finalizeMatch = async () => {
  if (processing.value) return;

  if (props.matchData.score_a === props.matchData.score_b) {
    toastWarning('No puedes finalizar en empate. Registra una accion de desempate.');
    return;
  }

  const winnerId = props.matchData.score_a > props.matchData.score_b ? props.matchData.player_a_id : props.matchData.player_b_id;
  const winnerName = props.matchData.score_a > props.matchData.score_b ? props.matchData.player_a?.blader_name : props.matchData.player_b?.blader_name;

  const confirmed = await ask({
    title: 'Finalizar match',
    message: `Ganador: ${winnerName}\nPuntaje: ${props.matchData.score_a} - ${props.matchData.score_b}\n\n¿Deseas finalizar?`,
    confirmText: 'Finalizar',
    tone: 'primary',
  });

  if (!confirmed) return;

  processing.value = true;
  try {
    const { data } = await axios.post(
      route('referee.match.finalize', props.matchData.id),
      {
        score_a: props.matchData.score_a,
        score_b: props.matchData.score_b,
        winner_id: winnerId,
      },
      {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    );

    winnerModal.value = {
      winnerName: data?.winner_name || winnerName,
      scoreA: data?.score_a ?? props.matchData.score_a,
      scoreB: data?.score_b ?? props.matchData.score_b,
      nextMatchUrl: data?.next_match_url || null,
      listUrl: data?.list_url || route('referee.dashboard', { filter: 'assigned' }),
    };
    showWinnerModal.value = true;
    toastSuccess(data?.message || 'Partida finalizada correctamente.');
  } catch (error) {
    const message = error?.response?.data?.message || 'No se pudo finalizar la partida.';
    toastError(message);
  } finally {
    processing.value = false;
  }
};

const goToNextAssignedMatch = () => {
  const nextUrl = winnerModal.value.nextMatchUrl || winnerModal.value.listUrl;
  showWinnerModal.value = false;
  router.visit(nextUrl);
};

const goToMatchList = () => {
  showWinnerModal.value = false;
  router.visit(winnerModal.value.listUrl || route('referee.dashboard', { filter: 'assigned' }));
};

const formatTime = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.referee-panel-container {
  max-width: 1200px;
  margin: 0 auto;
  padding-bottom: calc(var(--bottom-nav-height) + var(--space-xl));
}

.panel-top {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: var(--space-md);
  align-items: center;
  margin-bottom: var(--space-md);
}

.top-center {
  text-align: center;
}

.top-center h1 {
  margin: 0;
  font-size: 1.1rem;
}

.top-center p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 0.86rem;
}

.top-badges {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.arena-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
  gap: var(--space-md);
  align-items: start;
}

.fighter-card {
  display: grid;
  gap: var(--space-sm);
}

.fighter-card.winner {
  border-color: #10b981;
}

.fighter-card.loser {
  opacity: 0.7;
}

.fighter-head {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.fighter-head.reverse {
  flex-direction: row-reverse;
  text-align: right;
}

.avatar-shell {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  border: 2px solid var(--border-color-light);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
}

.avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.score-box {
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
  padding: var(--space-sm);
  text-align: center;
  background: var(--bg-secondary);
}

.score-box strong {
  display: block;
  font-family: var(--font-display);
  font-size: 2.4rem;
  line-height: 1;
}

.score-box span {
  font-size: 0.68rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-xs);
}

.action-btn {
  border: 1px solid var(--border-color-light);
  border-radius: 10px;
  padding: 10px;
  font-size: 0.78rem;
  font-weight: 800;
  color: #fff;
  background: var(--bg-card);
  cursor: pointer;
}

.action-btn.spin { background: #0891b2; border-color: #06b6d4; }
.action-btn.over { background: #ea580c; border-color: #f97316; }
.action-btn.burst { background: #dc2626; border-color: #ef4444; }
.action-btn.xtreme { background: #9333ea; border-color: #a855f7; }
.action-btn.strike { background: #ca8a04; border-color: #eab308; }

.action-btn:active {
  transform: scale(0.95);
  filter: brightness(1.2);
}

.mini-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 4px;
  font-size: 0.72rem;
  color: var(--text-secondary);
}

.vs-col {
  display: grid;
  place-items: center;
  gap: var(--space-xs);
  min-width: 64px;
  margin-top: 80px;
}

.vs {
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 2.2rem;
  color: rgba(255, 255, 255, 0.2);
}

.live-score {
  font-weight: 900;
  font-family: var(--font-display);
}

.control-bar {
  margin-top: var(--space-md);
  display: flex;
  gap: var(--space-sm);
  flex-wrap: wrap;
}

.action-log-card {
  margin-top: var(--space-md);
}

.action-log-card h4 {
  margin-bottom: var(--space-sm);
}

.log-list {
  display: grid;
  gap: 6px;
  max-height: 260px;
  overflow-y: auto;
}

.log-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border-radius: 10px;
  border-left: 3px solid transparent;
  background: var(--bg-secondary);
}

.log-row.a {
  border-left-color: #3b82f6;
}

.log-row.b {
  border-left-color: #a855f7;
}

.winner-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: calc(var(--z-modal) + 30);
  background: rgba(0, 0, 0, 0.86);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-md);
}

.winner-modal {
  max-width: 520px;
  width: 100%;
  text-align: center;
  display: grid;
  gap: var(--space-sm);
}

.winner-icon {
  width: 64px;
  height: 64px;
  border-radius: 999px;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.35);
  display: grid;
  place-items: center;
  font-size: 1.8rem;
  margin: 0 auto;
}

.winner-title {
  margin: 0;
  font-size: 1.3rem;
}

.winner-name {
  margin: 0;
  font-weight: 800;
  color: #10b981;
}

.winner-score {
  margin: 0;
  color: var(--text-secondary);
}

.winner-actions {
  display: grid;
  gap: 8px;
  margin-top: 8px;
}

@media (max-width: 900px) {
  .arena-grid {
    grid-template-columns: 1fr;
  }

  .vs-col {
    margin-top: 0;
  }

  .panel-top {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .top-left,
  .top-right {
    display: flex;
    justify-content: center;
  }
}
</style>
