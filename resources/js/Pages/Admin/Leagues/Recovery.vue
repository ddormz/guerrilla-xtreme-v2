<template>
  <AppLayout>
    <Head title="Recuperación de Fechas" />

    <div class="page-content">
      <div class="page-header flex justify-between items-center mb-xl">
        <div>
          <h1 class="page-title text-gradient">🔄 Recuperación de Fechas</h1>
          <p class="text-secondary">Gestiona los matches de jugadores que recuperan fechas pasadas.</p>
        </div>
        <div class="flex gap-sm">
          <Link :href="route('admin.league.index')" class="btn btn-secondary">Liga Interna</Link>
        </div>
      </div>

      <!-- Context Selection -->
      <div class="card p-lg mb-xl stagger">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg items-end">
          <div class="form-group">
            <label class="form-label">Temporada</label>
            <select v-model="form.season_id" @change="updateEvents" class="form-input">
              <option value="">-- Seleccionar Temporada --</option>
              <option v-for="s in seasons" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Fecha Actual (Referencia)</label>
            <select v-model="form.event_id" @change="refreshPlayers" class="form-input" :disabled="!form.season_id">
              <option value="">-- Seleccionar Fecha --</option>
              <option v-for="e in events" :key="e.id" :value="e.id">
                {{ e.name }} ({{ formatDate(e.event_date) }})
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Eligible Players -->
      <div v-if="form.event_id && eligiblePlayers.length > 0" class="card p-lg mb-xl stagger">
        <h2 class="text-lg font-bold mb-md">Jugadores que pueden recuperar hoy</h2>
        <p class="text-sm text-secondary mb-lg italic">
          Se listan jugadores PRESENTES hoy que faltaron a alguna fecha anterior de esta temporada.
        </p>

        <form @submit.prevent="generateMatches">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-md mb-xl">
            <label v-for="p in eligiblePlayers" :key="p.id" class="eligible-player-card">
              <input type="checkbox" v-model="generateForm.player_ids" :value="p.id" class="mr-sm">
              <div class="flex-1">
                <div class="font-bold text-sm">{{ p.blader_name || p.real_name }}</div>
                <div class="text-[10px] text-accent-red">Debe: {{ p.missed_dates }}</div>
              </div>
            </label>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-sm text-secondary">{{ generateForm.player_ids.length }} seleccionados</span>
            <button type="submit" class="btn btn-primary" :disabled="generateForm.player_ids.length < 2 || generateForm.processing">
              🎲 Generar Partidas de Recuperación
            </button>
          </div>
        </form>
      </div>
      <div v-else-if="form.event_id" class="card p-xl text-center mb-xl stagger opacity-60">
        <p class="text-secondary italic">No hay jugadores presentes hoy que deban fechas anteriores.</p>
      </div>

      <!-- Recovery Matches List -->
      <div v-if="recoveryMatches.length > 0" class="card stagger overflow-hidden">
        <div class="card-header border-b border-border bg-white/5 p-md">
          <h2 class="text-lg font-bold">Matches de Recuperación - Temporada</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="gx-table w-full text-left">
            <thead>
              <tr class="bg-black/20 text-xs font-bold uppercase text-secondary">
                <th class="p-md">ID</th>
                <th class="p-md">Fecha/Evento</th>
                <th class="p-md">Match</th>
                <th class="p-md">Estado</th>
                <th class="p-md">Árbitro</th>
                <th class="p-md text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <tr v-for="m in recoveryMatches" :key="m.id" class="border-b border-border hover:bg-white/5 transition-colors">
                <td class="p-md text-secondary">#{{ m.id }}</td>
                <td class="p-md font-medium">{{ m.event?.name }}</td>
                <td class="p-md">
                  <div class="flex items-center gap-xs">
                    <span class="text-blue-400 font-bold">{{ m.player_a?.blader_name }}</span>
                    <span class="text-secondary text-xs italic">vs</span>
                    <span class="text-purple-400 font-bold">{{ m.player_b?.blader_name }}</span>
                  </div>
                </td>
                <td class="p-md">
                  <span class="badge" :class="m.concluded ? 'badge-gray' : 'badge-green'">
                    {{ m.concluded ? 'Finalizado' : 'Pendiente' }}
                  </span>
                </td>
                <td class="p-md text-secondary">
                  {{ m.referee?.name || 'Sin asignar' }}
                </td>
                <td class="p-md text-right">
                  <Link :href="route('referee.match.panel', m.id)" class="btn btn-sm btn-ghost text-xs">Panel</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  seasons: Array,
  events: Array,
  eligiblePlayers: Array,
  recoveryMatches: Array,
  filters: Object,
});

const form = reactive({
  season_id: props.filters.season_id || '',
  event_id: props.filters.event_id || '',
});

const generateForm = useForm({
  player_ids: [],
  event_id: form.event_id,
});

const updateEvents = () => {
  form.event_id = '';
  router.get(route('admin.recovery.index'), { season_id: form.season_id }, { preserveState: true });
};

const refreshPlayers = () => {
  generateForm.event_id = form.event_id;
  router.get(route('admin.recovery.index'), { season_id: form.season_id, event_id: form.event_id }, { preserveState: true });
};

const generateMatches = () => {
  generateForm.post(route('admin.recovery.generate'), {
    onSuccess: () => {
      generateForm.player_ids = [];
    }
  });
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit' });
};
</script>

<style scoped>
.eligible-player-card {
  display: flex;
  align-items: flex-start;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  cursor: pointer;
  transition: all 0.2s;
}

.eligible-player-card:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.2);
}

.badge {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.70rem;
  font-weight: bold;
}
.badge-gray { background: rgba(255,255,255,0.1); color: var(--text-secondary); }
.badge-blue { background: rgba(59, 130, 246, 0.15); color: #93c5fd; }
.badge-purple { background: rgba(168, 85, 247, 0.15); color: #d8b4fe; }
.badge-orange { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-green { background: rgba(16, 185, 129, 0.15); color: #10b981; }

.gx-table th {
  border-bottom: 1px solid var(--border-color);
}
</style>
