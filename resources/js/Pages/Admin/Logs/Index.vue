<template>
  <AppLayout>
    <Head title="Admin - Logs de Auditoría" />

    <div class="page-content">
      <div class="page-header mb-lg">
        <h1 class="page-title text-gradient">Audit Log</h1>
        <p class="text-secondary">Registro histórico de acciones administrativas y cambios en la base de datos.</p>
      </div>

      <div class="card stagger">
        <div class="card-body p-0">
          <div class="table-container overflow-x-auto">
            <table class="data-table w-full">
              <thead>
                <tr>
                  <th>Actor</th>
                  <th>Acción</th>
                  <th>Entidad</th>
                  <th>ID</th>
                  <th>Device ID</th>
                  <th>Fecha</th>
                  <th>IP</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs.data" :key="log.id">
                  <td>
                    <div class="font-bold">{{ log.actor }}</div>
                  </td>
                  <td>
                    <span :class="['action-badge', log.action]">
                      {{ formatAction(log.action) }}
                    </span>
                  </td>
                  <td>{{ log.entity_type }}</td>
                  <td><code>#{{ log.entity_id || '-' }}</code></td>
                  <td class="text-xs font-mono" :class="log.device_id && log.device_id !== 'N/A' ? 'text-amber-400' : 'text-secondary'">{{ log.device_id || '-' }}</td>
                  <td class="text-sm">{{ log.date }}</td>
                  <td class="text-xs text-secondary font-mono">{{ log.ip }}</td>
                  <td>
                    <button v-if="log.payload" @click="viewPayload(log)" class="btn btn-outline btn-xs">👁️ Ver Datos</button>
                    <span v-else class="text-muted text-xs">-</span>
                  </td>
                </tr>
                <tr v-if="logs.data.length === 0">
                  <td colspan="8" class="text-center py-xl text-secondary">No hay registros de auditoría</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="pagination flex justify-center gap-sm p-4" v-if="logs.links.length > 3">
            <template v-for="(link, p) in logs.links" :key="p">
              <div v-if="link.url === null" class="pagination-link disabled" v-html="link.label" />
              <Link v-else class="pagination-link" :class="{ 'active': link.active }" :href="link.url" v-html="link.label" />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Payload Modal -->
    <div v-if="payloadModalOpen" class="modal-overlay" @click.self="payloadModalOpen = false">
      <div class="modal-content card max-w-2xl">
        <div class="modal-header">
          <h3>Detalle del Cambio</h3>
          <button @click="payloadModalOpen = false" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <div class="mb-md flex justify-between">
            <div><strong>Entidad:</strong> {{ selectedLog.entity_type }} #{{ selectedLog.entity_id }}</div>
            <div><strong>Acción:</strong> {{ selectedLog.action }}</div>
          </div>
          <pre class="payload-box">{{ JSON.stringify(selectedLog.payload, null, 2) }}</pre>
        </div>
        <div class="modal-footer flex justify-end p-md">
          <button @click="payloadModalOpen = false" class="btn btn-primary">Cerrar</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  logs: Object,
});

const selectedLog = ref(null);
const payloadModalOpen = ref(false);

const viewPayload = (log) => {
  selectedLog.value = log;
  payloadModalOpen.value = true;
};

const formatAction = (action) => {
  return action.replace(/_/g, ' ').toUpperCase();
};
</script>

<style scoped>
.action-badge {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: bold;
}

.action-badge.delete_user { background: rgba(225, 6, 0, 0.1); color: var(--gx-red); }
.action-badge.update_role { background: rgba(30, 64, 175, 0.1); color: #1e40af; }
.action-badge.update_user { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.action-badge.register_tournament { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.action-badge.blocked_device { background: rgba(225, 6, 0, 0.2); color: #f87171; }
.action-badge.blocked_profanity { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

.payload-box {
  background: #1a1a1a;
  color: #a5d6ff;
  padding: var(--space-md);
  border-radius: var(--radius-md);
  font-family: 'Consolas', monospace;
  font-size: 0.85rem;
  max-height: 400px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

.pagination-link {
  padding: 8px 12px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 4px;
  color: var(--text-primary);
}

.pagination-link.active {
  background: var(--gx-red);
  color: white;
  border-color: var(--gx-red);
}

.pagination-link.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal Simple Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  backdrop-filter: blur(4px);
}

.modal-content {
  width: 90%;
  max-width: 600px;
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
}

.modal-header {
  padding: var(--space-md) var(--space-lg);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--text-secondary);
  cursor: pointer;
}

.modal-body {
  padding: var(--space-lg);
}
</style>
