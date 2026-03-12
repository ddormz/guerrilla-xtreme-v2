<template>
  <AppLayout>
    <Head title="Clasificacion" />

    <div class="standings-page page-content">
      <div class="page-header mb-xl animate-fade-in-up">
        <h1 class="page-title league-title">Tabla de Posiciones</h1>
        <p class="text-secondary">Ranking oficial de bladers de la temporada actual.</p>
      </div>

      <section class="card mb-xl stagger hidden-mobile-rules">
        <details>
          <summary class="section-title text-gx-red cursor-pointer select-none flex justify-between items-center">
            <span>📜 Reglas de la Liga</span>
            <span class="text-sm font-normal text-secondary opacity-70">(Click para expandir/ocultar)</span>
          </summary>
          <div class="rules-grid mt-lg">
            <div class="rules-column">
              <h3 class="rules-group-title">General</h3>
              <ul class="rules-list">
                <li><span class="rule-icon">🚫</span> NO SE USA REGLAS JAPO.</li>
                <li><span class="rule-icon">🛡️</span> NO HAY SELF-KO.</li>
                <li><span class="rule-icon">🧑‍⚖️</span> El árbitro tiene la decisión final; jugadores o terceros no intervienen.</li>
                <li><span class="rule-icon">📹</span> Grabar partidas en todo momento. Se puede pedir ver video.</li>
                <li><span class="rule-icon">❓</span> Cualquier duda debe decirse al principio del encuentro.</li>
              </ul>
            </div>
            <div class="rules-column">
              <h3 class="rules-group-title">Strikes y Penalizaciones</h3>
              <ul class="rules-list">
                <li><span class="rule-icon">⏱️</span> Destiempo = Strike</li>
                <li><span class="rule-icon">🕳️</span> Directo al Over o Xtreme = Strike</li>
                <li><span class="rule-icon">🏟️</span> Tocar la cúpula o mover el estadio = Se repite y Strike</li>
                <li><span class="rule-icon">🖐️</span> Contacto a la mano del rival = Se repite y Strike</li>
                <li><span class="rule-icon">⚠️</span> Cae al Over/Xtreme sin contacto = Advertencia (2da vez = Strike).</li>
              </ul>
            </div>
            <div class="rules-column">
              <h3 class="rules-group-title">Jugabilidad</h3>
              <ul class="rules-list">
                <li><span class="rule-icon">⚔️</span> Debe existir contacto entre Beys para validar el finish.</li>
                <li><span class="rule-icon">🔄</span> Sale de la cúpula o cae encima de otro Bey = Se repite.</li>
                <li><span class="rule-icon">⏭️</span> Luego de 2 repeticiones = se pasa al siguiente combo.</li>
                <li><span class="rule-icon">🏆</span> Mínimo participar en 6 fechas para acceder a los premios.</li>
                <li><span class="rule-icon">♻️</span> Recuperación: solo hasta 1 fecha anterior (ej: fecha 5 recupera la 4).</li>
                <li><span class="rule-icon">🔄</span> Over sin contacto: si el bey entra al over, sale y luego toca al rival, se cuenta como válido.</li>
                <li><span class="rule-icon">📉</span> Over con contacto: si el bey entra al over y sale (volteado o girando) y termina en Spin Finish = 1 punto.</li>
              </ul>
            </div>
          </div>
        </details>
      </section>

      <div class="events-preview-grid mb-xl stagger">
        <section class="card events-panel">
          <h2 class="section-title text-sm mb-md">Próximas Fechas</h2>
          <div v-if="upcomingEvents?.length" class="space-y-sm">
            <div v-for="ev in upcomingEvents" :key="ev.id" class="event-mini-card upcoming">
              <div class="ev-date">
                <span class="day">{{ new Date(ev.date).getDate() }}</span>
                <span class="month">{{ new Date(ev.date).toLocaleString('es-CL', { month: 'short' }).toUpperCase() }}</span>
              </div>
              <div class="ev-info">
                <strong>{{ ev.name }}</strong>
                <span class="text-xs text-secondary">{{ ev.location || 'Por definir' }}</span>
              </div>
            </div>
          </div>
          <p v-else class="text-secondary text-sm italic">No hay próximas fechas programadas.</p>
        </section>

        <section class="card events-panel">
          <h2 class="section-title text-sm mb-md">Fechas Jugadas</h2>
          <div v-if="pastEvents?.length" class="space-y-sm">
            <div v-for="ev in pastEvents" :key="ev.id" class="event-mini-card past">
              <div class="ev-date">
                <span class="day">{{ new Date(ev.date).getDate() }}</span>
                <span class="month">{{ new Date(ev.date).toLocaleString('es-CL', { month: 'short' }).toUpperCase() }}</span>
              </div>
              <div class="ev-info">
                <strong>{{ ev.name }}</strong>
                <span class="text-xs text-accent-green">Finalizado ✓</span>
              </div>
            </div>
          </div>
          <p v-else class="text-secondary text-sm italic">Aún no se ha jugado ninguna fecha.</p>
        </section>
      </div>

      <section class="card stats-wrapper mb-xl stagger">
        <div class="stats-head">Resumen de Temporada</div>
        <div class="stats-grid">
          <div class="pool-card">
            <div class="pool-icon">💰</div>
            <div>
              <div class="label">Pozo recaudado</div>
              <div class="value">${{ formatPrice(totalCollected || 0) }}</div>
            </div>
          </div>
          <div class="pool-card">
            <div class="pool-icon">🗓️</div>
            <div>
              <div class="label">Fechas programadas</div>
              <div class="value">{{ upcomingEvents?.length || 0 }}</div>
            </div>
          </div>
          <div class="pool-card">
            <div class="pool-icon">✅</div>
            <div>
              <div class="label">Fechas jugadas</div>
              <div class="value">{{ pastEvents?.length || 0 }}</div>
            </div>
          </div>
        </div>
      </section>

      <div class="filter-bar mb-lg stagger">
        <div class="form-group mb-0">
          <label class="form-label sr-only">Temporada</label>
          <select class="form-input season-select" disabled>
            <option v-if="season">{{ season.name }}</option>
            <option v-else>Sin temporada</option>
          </select>
        </div>
        <div class="current-season-info" v-if="season">
          <span class="badge" :class="season.status === 'en_curso' ? 'badge-green' : 'badge-red'">
            {{ season.status === 'en_curso' ? 'TEMPORADA ACTIVA' : season.status.toUpperCase() }}
          </span>
        </div>
      </div>

      <div class="card p-0 overflow-hidden stagger">
        <div class="table-container pb-sm">
          <table class="gx-table min-w-[600px] md:min-w-full">
            <thead class="bg-gx-red text-white">
              <tr>
                <th class="text-center w-16 px-4 py-4 text-xs tracking-widest uppercase">Rank</th>
                <th class="text-left px-4 py-4 text-base tracking-wide uppercase">Blader</th>
                <th class="text-center stats-col px-4 py-4 text-sm tracking-wider uppercase">Puntos</th>
                <th class="text-center stats-col px-4 py-4 text-sm tracking-wider uppercase">V</th>
                <th class="text-center stats-col px-4 py-4 text-sm tracking-wider uppercase">D</th>
                <th class="text-center stats-col px-4 py-4 text-sm tracking-wider uppercase">Xtreme</th>
              </tr>
            </thead>
            <tbody class="text-sm md:text-base">
              <tr v-for="(row, index) in standings" :key="row.id" class="table-row">
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
                <td class="text-center font-bold" :class="pointsClass(index)">{{ row.points }}</td>
                <td class="text-center stat-wins font-bold">{{ row.wins }}</td>
                <td class="text-center stat-losses font-bold">{{ row.losses }}</td>
                <td class="text-center stat-xtremes font-black">⚡ {{ row.xtremes }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="standings.length === 0" class="p-2xl text-center text-muted">
          No hay datos de clasificación para esta temporada aún.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  season: Object,
  standings: Array,
  seasons: Array,
  totalCollected: Number,
  upcomingEvents: Array,
  pastEvents: Array,
});

const rankClass = (index) => {
  if (index === 0) return 'rank-1';
  if (index === 1) return 'rank-2';
  if (index === 2) return 'rank-3';
  return '';
};

const pointsClass = (index) => {
  if (index === 0) return 'stat-points rank-1-text';
  if (index === 1) return 'stat-points rank-2-text';
  if (index === 2) return 'stat-points rank-3-text';
  return 'stat-points-default';
};

const podiumMessage = (index) => {
  if (index === 0) return 'Papá de la Guerrilla';
  if (index === 1) return 'Tu hijo casi te alcanza papá';
  if (index === 2) return 'No se descuiden mis lideres';
  return '';
};

const formatPrice = (price) => new Intl.NumberFormat('es-CL').format(price);

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.league-title {
  color: var(--gx-red-light);
}

.stats-wrapper {
  border: 1px solid rgba(255, 255, 255, 0.09);
}

.stats-head {
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-secondary);
  margin-bottom: var(--space-md);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: var(--space-md);
}

.pool-card {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-md);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.03);
}

.pool-icon {
  font-size: 1.6rem;
}

.label {
  font-size: 0.72rem;
  text-transform: uppercase;
  color: var(--text-secondary);
  letter-spacing: 0.08em;
}

.value {
  font-size: 1.4rem;
  font-weight: 800;
}

.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-secondary);
  padding: var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
}

.season-select {
  min-width: 220px;
}

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

.stat-points {
  font-weight: 800;
}

.rank-1-text {
  color: #ffd700;
}

.rank-2-text {
  color: #d1d5db;
}

.rank-3-text {
  color: #cd7f32;
}

.stat-points-default {
  color: #fff;
}

.stat-wins {
  color: #22c55e;
  font-weight: 700;
}

.stat-losses {
  color: #ef4444;
  font-weight: 700;
}

.stat-xtremes {
  color: #ff3e3e;
  text-shadow: 0 0 10px rgba(225, 6, 0, 0.4);
}

.rules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--space-xl);
}

.rules-group-title {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--gx-red);
  margin-bottom: var(--space-md);
  font-weight: 800;
  border-bottom: 2px solid rgba(225, 6, 0, 0.2);
  padding-bottom: 4px;
}

.rules-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.rules-list li {
  font-size: 0.9rem;
  line-height: 1.4;
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.rule-icon {
  flex-shrink: 0;
}

details > summary {
  list-style: none;
}
details > summary::-webkit-details-marker {
  display: none;
}

.events-preview-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);
}

@media (min-width: 768px) {
  .events-preview-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.event-mini-card {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm);
  background: rgba(0,0,0,0.2);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: var(--radius-md);
}

.event-mini-card.upcoming {
  border-left: 3px solid var(--accent-blue);
}

.event-mini-card.past {
  border-left: 3px solid var(--accent-green);
  opacity: 0.8;
}

.ev-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--bg-secondary);
  border-radius: var(--radius-sm);
  width: 44px;
  height: 44px;
}

.ev-date .day {
  font-size: 1.1rem;
  font-family: var(--font-display);
  font-weight: 800;
  line-height: 1;
}

.ev-date .month {
  font-size: 0.6rem;
  color: var(--gx-red);
  font-weight: 700;
}

.ev-info {
  display: flex;
  flex-direction: column;
}

@media (max-width: 640px) {
  .filter-bar {
    flex-direction: column;
    gap: var(--space-sm);
    align-items: stretch;
  }
}
</style>
