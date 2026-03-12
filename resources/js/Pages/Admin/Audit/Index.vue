<template>
  <AppLayout>
    <Head title="Auditoría de Acciones" />

    <div class="page-content">
      <div class="page-header mb-xl">
        <h1 class="page-title text-gradient">Registro de Auditoría</h1>
        <p class="text-secondary m-0">Historial completo de acciones realizadas por administradores y sistema.</p>
      </div>

      <!-- Filters -->
      <div class="card mb-lg p-md">
        <form @submit.prevent="applyFilters" class="flex gap-md flex-wrap items-end">
          <div class="form-group mb-0">
            <label class="form-label text-xs uppercase font-bold text-secondary">Buscar Acción/Entidad</label>
            <input v-model="filtersForm.search" type="text" class="form-input form-sm" placeholder="Ej: create_raffle" />
          </div>
          <div class="form-group mb-0">
            <label class="form-label text-xs uppercase font-bold text-secondary">Usuario</label>
            <select v-model="filtersForm.actor" class="form-input form-sm">
              <option value="">Todos</option>
              <option v-for="a in actors" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>
          <div class="flex gap-sm">
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            <button type="button" @click="clearFilters" class="btn btn-ghost btn-sm">Limpiar</button>
          </div>
        </form>
      </div>

      <!-- Logs Table -->
      <div class="card overflow-hidden">
        <div class="table-container">
          <table class="data-table w-full text-sm">
            <thead>
              <tr>
                <th>Fecha/Hora</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Entidad</th>
                <th>ID</th>
                <th>IP / Dispositivo</th>
                <th class="text-right">Detalles</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs.data" :key="log.id" class="hover:bg-white/5 transition-colors">
                <td class="whitespace-nowrap font-mono text-secondary">{{ formatDate(log.created_at) }}</td>
                <td>
                  <div class="flex items-center gap-xs">
                    <span class="font-bold text-white">{{ log.actor?.name || 'Sistema' }}</span>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="getActionClass(log.action)">{{ humanAction(log.action) }}</span>
                </td>
                <td class="text-secondary">{{ log.entity_type }}</td>
                <td class="font-mono text-xs opacity-50">#{{ log.entity_id || '-' }}</td>
                <td class="text-[10px] text-secondary">
                  <div class="truncate max-w-[150px]" :title="log.user_agent">
                    {{ log.ip_address }} / {{ log.user_agent }}
                  </div>
                </td>
                <td class="text-right">
                  <button @click="viewPayload(log)" class="btn btn-ghost btn-xs">👁️ Ver JSON</button>
                </td>
              </tr>
              <tr v-if="!logs.data.length">
                <td colspan="7" class="text-center py-xl text-secondary">No hay registros que coincidan.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <PaginationLinks :links="logs.links" />
      </div>

      <!-- Payload Modal -->
      <div v-if="modalOpen" class="modal-overlay" @click.self="modalOpen = false">
        <div class="modal-content card max-w-2xl w-full stagger">
           <div class="card-header flex justify-between items-center">
             <h3 class="m-0">Detalles del Movimiento</h3>
             <button @click="modalOpen = false" class="btn btn-ghost p-xs">✕</button>
           </div>
           <div class="card-body p-0">
             <pre class="bg-black/40 p-xl overflow-auto text-xs font-mono text-accent-blue max-h-[60vh]">{{ JSON.stringify(selectedLog.payload_json, null, 2) }}</pre>
           </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';

const props = defineProps({
  logs: Object,
  filters: Object,
  actors: Array
});

const filtersForm = ref({
  search: props.filters.search || '',
  actor: props.filters.actor || '',
});

const modalOpen = ref(false);
const selectedLog = ref(null);

const applyFilters = () => {
  router.get(route('admin.audit.index'), filtersForm.value, { preserveState: true, replace: true });
};

const clearFilters = () => {
  filtersForm.value.search = '';
  filtersForm.value.actor = '';
  applyFilters();
};

const viewPayload = (log) => {
  selectedLog.value = log;
  modalOpen.value = true;
};

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleString('es-ES', { 
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit'
  });
};

const actionLabels = {
  create_raffle: 'Creó rifa',
  update_raffle: 'Editó rifa',
  delete_raffle: 'Eliminó rifa',
  create_movement: 'Registró movimiento',
  delete_movement: 'Eliminó movimiento',
  create_category: 'Creó categoría',
  update_category: 'Editó categoría',
  delete_category: 'Eliminó categoría',
  create_wallet: 'Creó fondo',
  update_wallet: 'Editó fondo',
  delete_wallet: 'Eliminó fondo',
  update_role: 'Cambió rol',
  update_user: 'Editó usuario',
  delete_user: 'Eliminó usuario',
  approve_reservation: 'Aprobó reserva',
  create_event: 'Creó evento',
  update_event: 'Editó evento',
  delete_event: 'Eliminó evento',
  create_season: 'Creó temporada',
  generate_matches: 'Generó partidas',
  assign_referee: 'Asignó árbitro',
  finalize_match: 'Finalizó partida',
};

const humanAction = (action) => actionLabels[action] || action.replace(/_/g, ' ');

const getActionClass = (action) => {
  if (action.includes('create') || action.includes('approve')) return 'badge-green';
  if (action.includes('update') || action.includes('assign') || action.includes('generate') || action.includes('finalize')) return 'badge-orange';
  if (action.includes('delete')) return 'badge-red';
  return 'badge-gray';
};
</script>

<style scoped>
.data-table th { background: rgba(0,0,0,0.2); }
.badge { font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; }
.badge-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.badge-orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.badge-red { background: rgba(225, 6, 0, 0.1); color: var(--gx-red); }
.badge-gray { background: rgba(255, 255, 255, 0.05); color: var(--text-secondary); }

.pagination-item {
  padding: 8px 14px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-color);
  border-radius: 6px;
  text-decoration: none;
  color: var(--text-primary);
  font-size: 0.8rem;
  transition: all 0.2s;
}
.pagination-item.active { background: var(--gx-red); border-color: var(--gx-red); color: white; }
.pagination-item.disabled { opacity: 0.3; cursor: not-allowed; }

.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px);
  z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 2rem;
}
</style>
