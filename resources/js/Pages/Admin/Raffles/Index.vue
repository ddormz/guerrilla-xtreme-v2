<template>
  <AppLayout>
    <Head title="Gestionar Rifas" />

    <div class="admin-raffles page-content">
      <nav class="mb-lg">
        <Link :href="route('admin.dashboard')" class="btn btn-ghost btn-sm">← Dashboard</Link>
      </nav>

      <div class="page-header mb-xl">
        <h1 class="text-gradient">Gestión de Rifas</h1>
        <Link :href="route('admin.raffles.create')" class="btn btn-primary">+ Nueva Rifa</Link>
      </div>

      <section class="mb-2xl">
        <div class="flex items-center justify-between gap-md mb-md flex-wrap">
          <h2 class="m-0">Historial de Reservas</h2>
          <div class="flex gap-sm">
            <button class="btn btn-sm" :class="historyTab === 'pending' ? 'btn-primary' : 'btn-ghost'" @click="historyTab = 'pending'" type="button">
              Por validar ({{ pendingReservations.length }})
            </button>
            <button class="btn btn-sm" :class="historyTab === 'validated' ? 'btn-primary' : 'btn-ghost'" @click="historyTab = 'validated'" type="button">
              Validadas ({{ validatedReservations.length }})
            </button>
          </div>
        </div>

        <div v-if="activeReservations.length > 0" class="reservation-grid stagger">
          <article v-for="res in activeReservations" :key="`${historyTab}-${res.id}`" class="reservation-item card">
            <div class="res-head">
              <div>
                <strong>{{ res.buyer_name }}</strong>
                <p class="text-xs text-muted">{{ res.blader_name || 'Sin blader' }} · {{ res.raffle?.name }}</p>
              </div>
              <span class="badge" :class="historyTab === 'pending' ? 'badge-amber' : 'badge-green'">
                {{ historyTab === 'pending' ? 'PENDIENTE' : 'VALIDADA' }}
              </span>
            </div>

            <div class="res-meta text-secondary text-sm">
              <span>{{ res.email || 'Sin correo' }}</span>
              <span v-if="res.phone">• {{ res.phone }}</span>
            </div>

            <div class="res-numbers">
              <span class="text-xs text-secondary uppercase">Números</span>
              <p class="number-list">{{ formatNumbers(res.numbers) }}</p>
            </div>

            <div class="res-foot">
              <div class="res-amount font-bold text-accent-green">${{ formatAmount(res.total_amount) }}</div>
              <small class="text-xs text-secondary">{{ historyTab === 'pending' ? formatDateTime(res.created_at) : formatDateTime(res.validated_at || res.created_at) }}</small>
            </div>

            <div class="res-actions">
              <button
                v-if="res.proof_url"
                @click="openProof(res)"
                class="btn btn-outline btn-sm"
                type="button"
              >
                Ver comprobante
              </button>
              <button
                v-else
                class="btn btn-ghost btn-sm"
                type="button"
                disabled
              >
                Sin comprobante
              </button>

              <button
                v-if="historyTab === 'pending'"
                @click="approve(res.id)"
                class="btn btn-primary btn-sm"
                type="button"
              >
                Validar Pago
              </button>
            </div>
          </article>
        </div>
        <div v-else class="card p-xl text-center text-muted">
          {{ historyTab === 'pending' ? 'No hay reservas pendientes de validación.' : 'No hay reservas validadas para mostrar.' }}
        </div>
      </section>

      <section>
        <h2 class="mb-lg">Sorteos</h2>
        <div class="card p-0 overflow-hidden stagger">
          <table class="gx-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Precio</th>
                <th class="text-center">Números</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="raffle in raffles" :key="raffle.id" class="table-row">
                <td class="font-bold">{{ raffle.name }}</td>
                <td class="text-center">
                  <span class="badge" :class="getStatusClass(raffle.status)">{{ raffle.status }}</span>
                </td>
                <td class="text-center">${{ formatAmount(raffle.ticket_price) }}</td>
                <td class="text-center">{{ raffle.total_numbers }}</td>
                <td class="text-right">
                  <div class="flex gap-xs justify-end flex-wrap">
                    <Link :href="route('admin.raffles.show', raffle.id)" class="btn btn-ghost btn-xs">Ver</Link>
                    <Link :href="route('admin.raffles.edit', raffle.id)" class="btn btn-outline btn-xs">Editar</Link>
                    <button @click="destroyRaffle(raffle)" class="btn btn-error btn-xs" title="Eliminar" type="button">🗑️</button>
                    <button v-if="raffle.status === 'draft'" @click="publish(raffle.id)" class="btn btn-ghost btn-xs text-accent-green" type="button">Publicar</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

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

const activeReservations = computed(() => {
  return historyTab.value === 'pending' ? props.pendingReservations : props.validatedReservations;
});

const proofModal = reactive({
  open: false,
  buyerName: '',
  url: '',
});

const formatAmount = (val) => new Intl.NumberFormat('es-CL').format(val);

const formatDateTime = (value) => {
  if (!value) return '-';
  return new Date(value).toLocaleString('es-CL');
};

const formatNumbers = (numbers) => {
  if (!numbers || numbers.length === 0) return 'Sin números';
  return numbers.join(', ');
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
.reservation-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--space-md);
}

.reservation-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.res-head {
  display: flex;
  justify-content: space-between;
  gap: var(--space-sm);
  align-items: flex-start;
}

.number-list {
  font-family: var(--font-display);
  font-weight: 700;
  color: var(--text-primary);
}

.res-foot {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: var(--space-sm);
}

.res-actions {
  display: flex;
  gap: var(--space-sm);
  flex-wrap: wrap;
}

.gx-table { width: 100%; border-collapse: collapse; }
.gx-table th { padding: var(--space-md); text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border-color); }
.gx-table td { padding: var(--space-md); border-bottom: 1px solid var(--border-color); }

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

@media (max-width: 640px) {
  .res-actions .btn {
    flex: 1;
  }
}
</style>


