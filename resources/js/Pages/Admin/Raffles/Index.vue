<template>
  <AppLayout>
    <Head title="Gestionar Rifas" />

    <div class="admin-raffles page-content">
      <nav class="mb-lg">
        <Link href="/admin/ligas" class="btn btn-ghost btn-sm">← Admin</Link>
      </nav>

      <div class="page-header mb-xl">
        <h1 class="text-gradient">Gestión de Rifas</h1>
        <Link :href="route('admin.raffles.create')" class="btn btn-primary">+ Nueva Rifa</Link>
      </div>

      <div class="stats-grid mb-xl stagger">
        <div class="card stat-card">
          <span class="k">Reservas por validar</span>
          <strong class="v text-amber-400">{{ pendingReservations.length }}</strong>
        </div>
        <div class="card stat-card">
          <span class="k">Reservas validadas</span>
          <strong class="v text-accent-green">{{ validatedReservations.length }}</strong>
        </div>
        <div class="card stat-card">
          <span class="k">Monto pendiente</span>
          <strong class="v text-amber-400">${{ formatAmount(totalPendingAmount) }}</strong>
        </div>
        <div class="card stat-card">
          <span class="k">Monto validado</span>
          <strong class="v text-accent-green">${{ formatAmount(totalValidatedAmount) }}</strong>
        </div>
      </div>

      <div class="raffles-layout stagger">
        <section class="card p-0 overflow-hidden reservations-panel">
          <div class="panel-head">
            <h2 class="m-0">Historial de Reservas</h2>
            <div class="flex gap-sm flex-wrap">
              <button
                class="btn btn-sm"
                :class="historyTab === 'pending' ? 'btn-primary' : 'btn-ghost'"
                @click="historyTab = 'pending'"
                type="button"
              >
                Por validar ({{ pendingReservations.length }})
              </button>
              <button
                class="btn btn-sm"
                :class="historyTab === 'validated' ? 'btn-primary' : 'btn-ghost'"
                @click="historyTab = 'validated'"
                type="button"
              >
                Validadas ({{ validatedReservations.length }})
              </button>
            </div>
          </div>

          <div class="panel-filters">
            <input
              v-model.trim="search"
              type="text"
              class="form-input form-input-sm w-full"
              placeholder="Buscar comprador, blader, correo o números..."
            />
          </div>

          <div v-if="activeReservations.length" class="table-wrap">
            <table class="gx-table">
              <thead>
                <tr>
                  <th>Comprador</th>
                  <th>Rifa</th>
                  <th>Contacto</th>
                  <th>Números</th>
                  <th>Comprobante</th>
                  <th class="text-right">Monto</th>
                  <th>Fecha</th>
                  <th class="text-right">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="res in activeReservations" :key="`${historyTab}-${res.id}`" class="table-row">
                  <td>
                    <div class="font-bold">{{ res.buyer_name }}</div>
                    <div class="text-xs text-secondary">{{ res.blader_name || 'Sin blader' }}</div>
                  </td>
                  <td>
                    <Link :href="route('admin.raffles.show', res.raffle_id)" class="font-bold hover:underline">
                      {{ res.raffle?.name || `Rifa #${res.raffle_id}` }}
                    </Link>
                  </td>
                  <td class="text-sm">
                    <div>{{ res.email || 'Sin correo' }}</div>
                    <div class="text-secondary">{{ res.phone || 'Sin teléfono' }}</div>
                  </td>
                  <td>
                    <div class="numbers-wrap">
                      <span v-for="n in res.numbers" :key="`${res.id}-${n}`" class="number-chip">{{ n }}</span>
                    </div>
                  </td>
                  <td>
                    <button
                      v-if="res.proof_url"
                      class="btn btn-outline btn-xs"
                      type="button"
                      @click="openProof(res)"
                    >
                      Ver
                    </button>
                    <span v-else class="text-xs text-secondary">Sin archivo</span>
                  </td>
                  <td class="text-right font-black">${{ formatAmount(res.total_amount) }}</td>
                  <td class="text-xs text-secondary">
                    {{ historyTab === 'pending' ? formatDateTime(res.created_at) : formatDateTime(res.validated_at || res.created_at) }}
                  </td>
                  <td class="text-right">
                    <button
                      v-if="historyTab === 'pending'"
                      @click="approve(res.id)"
                      class="btn btn-primary btn-xs"
                      type="button"
                    >
                      Validar
                    </button>
                    <span v-else class="badge badge-green">OK</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="p-xl text-center text-secondary italic">
            No hay reservas para este filtro.
          </div>
        </section>

        <aside class="side-panel">
          <section class="card p-md">
            <h3 class="m-0 mb-md uppercase tracking-wider text-sm text-secondary">Rifas</h3>
            <div class="raffles-list">
              <article v-for="raffle in raffles" :key="raffle.id" class="raffle-item">
                <div class="flex justify-between items-start gap-sm">
                  <div>
                    <h4 class="m-0">{{ raffle.name }}</h4>
                    <p class="text-xs text-secondary m-0 mt-xs">#{{ raffle.id }}</p>
                  </div>
                  <span class="badge" :class="getStatusClass(raffle.status)">{{ raffle.status }}</span>
                </div>

                <div class="raffle-metrics mt-sm">
                  <div>
                    <span class="k">Vendidos</span>
                    <strong>{{ raffle.sold_numbers_count || 0 }}</strong>
                  </div>
                  <div>
                    <span class="k">Reservados</span>
                    <strong>{{ raffle.reserved_numbers_count || 0 }}</strong>
                  </div>
                  <div>
                    <span class="k">Pendientes</span>
                    <strong>{{ raffle.pending_reservations_count || 0 }}</strong>
                  </div>
                </div>

                <div class="mt-sm text-sm text-secondary flex justify-between">
                  <span>Precio ticket</span>
                  <strong class="text-white">${{ formatAmount(raffle.ticket_price) }}</strong>
                </div>

                <div class="raffle-actions mt-sm">
                  <Link :href="route('admin.raffles.show', raffle.id)" class="btn btn-ghost btn-xs">Gestionar</Link>
                  <Link :href="route('admin.raffles.edit', raffle.id)" class="btn btn-outline btn-xs">Editar</Link>
                  <button
                    v-if="raffle.status === 'draft'"
                    @click="publish(raffle.id)"
                    class="btn btn-primary btn-xs"
                    type="button"
                  >
                    Publicar
                  </button>
                  <button @click="destroyRaffle(raffle)" class="btn btn-error btn-xs" type="button">Eliminar</button>
                </div>
              </article>
            </div>
          </section>
        </aside>
      </div>

      <div v-if="proofModal.open" class="modal-overlay" @click.self="closeProof">
        <div class="modal-content card">
          <div class="modal-header">
            <h3>Comprobante de {{ proofModal.buyerName }}</h3>
            <button @click="closeProof" class="close-btn" type="button">×</button>
          </div>
          <div class="proof-body">
            <img :src="proofModal.url" alt="Comprobante" class="proof-image" />
          </div>
          <div class="modal-footer flex justify-end gap-sm mt-md">
            <a :href="proofModal.url" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Abrir en pestaña</a>
            <button @click="closeProof" class="btn btn-primary btn-sm" type="button">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  raffles: Array,
  pendingReservations: Array,
  validatedReservations: Array,
});

const { ask } = useConfirm();
const historyTab = ref('pending');
const search = ref('');

const totalPendingAmount = computed(() => {
  return props.pendingReservations.reduce((sum, row) => sum + (Number(row.total_amount) || 0), 0);
});

const totalValidatedAmount = computed(() => {
  return props.validatedReservations.reduce((sum, row) => sum + (Number(row.total_amount) || 0), 0);
});

const activeReservations = computed(() => {
  const source = historyTab.value === 'pending' ? props.pendingReservations : props.validatedReservations;
  const term = search.value.toLowerCase();

  if (!term) return source;

  return source.filter((row) => {
    const numbersText = (row.numbers || []).join(', ');
    return [
      row.buyer_name,
      row.blader_name,
      row.email,
      row.phone,
      row.raffle?.name,
      numbersText,
    ].some((value) => String(value || '').toLowerCase().includes(term));
  });
});

const proofModal = reactive({
  open: false,
  buyerName: '',
  url: '',
});

const formatAmount = (val) => new Intl.NumberFormat('es-CL').format(Math.floor(Number(val) || 0));

const formatDateTime = (value) => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getStatusClass = (status) => {
  switch (status) {
    case 'published': return 'badge-green';
    case 'draft': return 'badge-red';
    case 'closed': return 'badge-amber';
    case 'drawn': return 'badge-blue';
    default: return 'badge-secondary';
  }
};

const openProof = (reservation) => {
  proofModal.open = true;
  proofModal.buyerName = reservation.buyer_name;
  proofModal.url = reservation.proof_url;
};

const closeProof = () => {
  proofModal.open = false;
  proofModal.buyerName = '';
  proofModal.url = '';
};

const destroyRaffle = async (raffle) => {
  const confirmed = await ask({
    title: 'Eliminar rifa',
    message: `¿Estás seguro de eliminar la rifa: ${raffle.name}?`,
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.raffles.destroy', raffle.id));
};

const publish = (id) => {
  router.post(route('admin.raffles.publish', id));
};

const approve = async (id) => {
  const confirmed = await ask({
    title: 'Validar pago',
    message: '¿Confirmas que el pago ha sido recibido?',
    confirmText: 'Validar',
    tone: 'primary',
  });

  if (!confirmed) return;
  router.post(route('admin.reservations.approve', id));
};
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-sm);
}

.stat-card {
  display: grid;
  gap: 4px;
}

.stat-card .k {
  font-size: 0.68rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.stat-card .v {
  font-family: var(--font-display);
  font-size: 1.45rem;
}

.raffles-layout {
  display: grid;
  grid-template-columns: minmax(0, 2.2fr) minmax(0, 1fr);
  gap: var(--space-lg);
  align-items: start;
}

.panel-head {
  padding: var(--space-md);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  gap: var(--space-sm);
  align-items: center;
  flex-wrap: wrap;
}

.panel-filters {
  padding: var(--space-md);
  border-bottom: 1px solid var(--border-color);
  background: rgba(255, 255, 255, 0.02);
}

.table-wrap {
  overflow-x: auto;
}

.numbers-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.number-chip {
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  padding: 2px 8px;
  font-size: 0.72rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.05);
}

.side-panel {
  display: grid;
  gap: var(--space-md);
}

.raffles-list {
  display: grid;
  gap: var(--space-sm);
}

.raffle-item {
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  padding: var(--space-sm);
  background: rgba(255, 255, 255, 0.02);
}

.raffle-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 6px;
}

.raffle-metrics > div {
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-sm);
  padding: 6px;
  text-align: center;
}

.raffle-metrics .k {
  display: block;
  color: var(--text-secondary);
  font-size: 0.62rem;
  text-transform: uppercase;
}

.raffle-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.gx-table {
  width: 100%;
  border-collapse: collapse;
}

.gx-table th {
  padding: var(--space-sm);
  text-align: left;
  font-size: 0.72rem;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border-color);
  white-space: nowrap;
}

.gx-table td {
  padding: var(--space-sm);
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.82);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--z-modal);
  padding: var(--space-md);
}

.modal-content {
  width: min(720px, 100%);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-md);
}

.close-btn {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 1.6rem;
  cursor: pointer;
}

.proof-body {
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.proof-image {
  width: 100%;
  max-height: 70vh;
  object-fit: contain;
  background: #0d0d0d;
}

@media (max-width: 1200px) {
  .raffles-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 860px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .raffle-actions .btn {
    flex: 1 1 auto;
  }
}
</style>
