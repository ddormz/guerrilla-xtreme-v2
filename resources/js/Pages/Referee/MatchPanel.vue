<template>
  <AppLayout>
    <Head :title="`Arbitrando: ${match.player_a.blader_name} vs ${match.player_b.blader_name}`" />

    <div class="match-panel page-content">
      <!-- Minimal Header -->
      <div class="panel-header mb-md">
        <Link :href="route('referee.index')" class="back-btn">✕</Link>
        <div class="match-meta text-center">
          <span class="badge badge-red" v-if="match.concluded">FINALIZADO</span>
          <span class="badge badge-green" v-else>LIVE</span>
          <p class="text-xs text-secondary mt-1">{{ match.event.name }} • Ronda {{ match.round_no }}</p>
        </div>
        <button @click="undo" class="undo-btn" :disabled="processing">⟲</button>
      </div>

      <!-- Score Display -->
      <div class="score-board mb-lg stagger">
        <div class="score-side side-a" :class="{ winner: match.winner_id === match.player_a_id }">
          <div class="player-info">
            <h2 class="blader-name">{{ match.player_a.blader_name }}</h2>
            <div class="strikes" v-if="match.strikes_a > 0">
              <span v-for="s in match.strikes_a" :key="s" class="strike">!</span>
            </div>
          </div>
          <div class="score-value">{{ match.score_a }}</div>
        </div>

        <div class="score-divider">VS</div>

        <div class="score-side side-b" :class="{ winner: match.winner_id === match.player_b_id }">
          <div class="score-value">{{ match.score_b }}</div>
          <div class="player-info text-right">
            <h2 class="blader-name">{{ match.player_b.blader_name }}</h2>
            <div class="strikes" v-if="match.strikes_b > 0">
              <span v-for="s in match.strikes_b" :key="s" class="strike">!</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Area -->
      <div v-if="!match.concluded" class="action-grid stagger">
        <!-- Player A Column -->
        <div class="action-column side-a">
          <button @click="addAction('a', 'spin')" class="action-btn spin-btn" :disabled="processing">
            <span class="btn-points">+1</span> SP
          </button>
          <button @click="addAction('a', 'over')" class="action-btn over-btn" :disabled="processing">
            <span class="btn-points">+2</span> OV
          </button>
          <button @click="addAction('a', 'burst')" class="action-btn burst-btn" :disabled="processing">
            <span class="btn-points">+2</span> BU
          </button>
          <button @click="addAction('a', 'xtreme')" class="action-btn xtreme-btn" :disabled="processing">
            <span class="btn-points">+3</span> XT
          </button>
          <button @click="addAction('a', 'strike')" class="action-btn strike-btn" :disabled="processing">
            STRIKE
          </button>
        </div>

        <!-- Middle Column (Draw/Game) -->
        <div class="action-column mid">
          <div class="current-game">P{{ match.game_no }}</div>
        </div>

        <!-- Player B Column -->
        <div class="action-column side-b">
          <button @click="addAction('b', 'spin')" class="action-btn spin-btn" :disabled="processing">
            <span class="btn-points">+1</span> SP
          </button>
          <button @click="addAction('b', 'over')" class="action-btn over-btn" :disabled="processing">
            <span class="btn-points">+2</span> OV
          </button>
          <button @click="addAction('b', 'burst')" class="action-btn burst-btn" :disabled="processing">
            <span class="btn-points">+2</span> BU
          </button>
          <button @click="addAction('b', 'xtreme')" class="action-btn xtreme-btn" :disabled="processing">
            <span class="btn-points">+3</span> XT
          </button>
          <button @click="addAction('b', 'strike')" class="action-btn strike-btn" :disabled="processing">
            STRIKE
          </button>
        </div>
      </div>

      <!-- Winner Proclamation -->
      <div v-else class="winner-proclamation card animate-fade-in-up">
        <h2 class="text-gradient">¡Victoria para {{ winnerName }}!</h2>
        <p class="mb-lg">Match finalizado con éxito.</p>
        <button @click="undo" class="btn btn-secondary mr-md">Corregir (Undo)</button>
        <Link :href="route('referee.index')" class="btn btn-primary">Volver al Panel</Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
  match: Object
});

const page = usePage();
const processing = ref(false);
const localMatch = ref({ ...props.match });

const winnerName = computed(() => {
  return localMatch.value.winner_id === localMatch.value.player_a_id 
    ? localMatch.value.player_a.blader_name 
    : localMatch.value.player_b.blader_name;
});

// Real-time listener via Laravel Echo
onMounted(() => {
  window.Echo.private(`match.${localMatch.value.id}`)
    .listen('MatchUpdated', (e) => {
      // Sync local state if updated from elsewhere (not common for referees, but good for consistency)
      Object.assign(localMatch.value, e);
    });
});

onUnmounted(() => {
  window.Echo.leave(`match.${localMatch.value.id}`);
});

const addAction = async (side, type) => {
  if (processing.value) return;
  processing.value = true;
  
  try {
    await axios.post(route('referee.action', localMatch.value.id), { side, type });
    // Local optimistic update could go here, but since it's Inertia, 
    // the backend will refresh the props automatically if we use router.post, 
    // but here we use axios for "silent" high-performance updates.
    // So we rely on Echo or manual props sync. 
    // Actually, let's use router.post for simplicity unless it's too slow.
    // router.post(...) 
    // Wait, the user wants "less engorroso" and specialized UI.
    // I'll manually update local state and let Echo handle others.
    
    // For this prototype, I'll use axios + manual local update for SNAPPY feel
    const pointsMap = { 'spin': 1, 'over': 2, 'burst': 2, 'xtreme': 3, 'strike': 0 };
    if (side === 'a') {
      localMatch.value.score_a += pointsMap[type];
      if (type === 'strike') localMatch.value.strikes_a++;
    } else {
      localMatch.value.score_b += pointsMap[type];
      if (type === 'strike') localMatch.value.strikes_b++;
    }
    
    // Check for win locally for immediate UI feedback
    if (localMatch.value.score_a >= localMatch.value.best_of || localMatch.value.score_b >= localMatch.value.best_of) {
      localMatch.value.concluded = true;
      localMatch.value.winner_id = localMatch.value.score_a > localMatch.value.score_b ? localMatch.value.player_a_id : localMatch.value.player_b_id;
    }
    
  } catch (err) {
    console.error(err);
  } finally {
    processing.value = false;
  }
};

const undo = async () => {
  if (processing.value) return;
  processing.value = true;
  try {
    await axios.post(route('referee.undo', localMatch.value.id));
    // Full refresh after undo to ensure consistency
    window.location.reload();
  } catch (err) {
    console.error(err);
  } finally {
    processing.value = false;
  }
};
</script>

<style scoped>
.match-panel {
  max-width: 600px;
  margin: 0 auto;
  padding-bottom: env(safe-area-inset-bottom, 0px);
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.back-btn, .undo-btn {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-full);
  color: var(--text-primary);
  font-size: 1.2rem;
  cursor: pointer;
}

.undo-btn:disabled { opacity: 0.5; }

/* Score Board */
.score-board {
  display: flex;
  align-items: stretch;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  overflow: hidden;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

.score-side {
  flex: 1;
  padding: var(--space-lg);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  text-align: center;
  transition: all var(--transition-base);
}

.side-a { background: linear-gradient(135deg, rgba(225, 6, 0, 0.1), transparent); }
.side-b { background: linear-gradient(-135deg, rgba(59, 130, 246, 0.1), transparent); }

.winner { background: var(--gx-red); }
.winner .score-value, .winner .blader-name { color: white; }

.blader-name {
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.score-value {
  font-family: var(--font-display);
  font-size: 4rem;
  font-weight: 800;
  line-height: 1;
  color: var(--text-primary);
}

.score-divider {
  width: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 900;
  color: var(--text-muted);
  background: var(--bg-primary);
}

.strikes {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.strike {
  color: #ff0;
  font-weight: 900;
  text-shadow: 0 0 5px rgba(255, 255, 0, 0.5);
}

/* Action Grid */
.action-grid {
  display: grid;
  grid-template-columns: 1fr 50px 1fr;
  gap: 12px;
}

.action-column {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.action-btn {
  height: 64px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color-light);
  background: var(--bg-card);
  color: var(--text-primary);
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: transform 0.05s active;
}

.action-btn:active {
  transform: scale(0.95);
  background: var(--border-color);
}

.btn-points {
  font-size: 0.8rem;
  background: rgba(0,0,0,0.3);
  padding: 2px 6px;
  border-radius: 4px;
}

.spin-btn { border-bottom: 4px solid var(--accent-blue); }
.over-btn { border-bottom: 4px solid var(--accent-amber); }
.burst-btn { border-bottom: 4px solid var(--accent-purple); }
.xtreme-btn { border-bottom: 4px solid var(--gx-red); }
.strike-btn { border-color: #ff0; color: #ff0; font-size: 0.8rem; height: 44px; }

.mid {
  display: flex;
  align-items: center;
  justify-content: center;
}

.current-game {
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 1.2rem;
  color: var(--text-muted);
  writing-mode: vertical-lr;
  text-orientation: upright;
}

.winner-proclamation {
  text-align: center;
  padding: var(--space-2xl);
}
</style>
