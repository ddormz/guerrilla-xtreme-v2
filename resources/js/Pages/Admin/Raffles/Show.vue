<template>
  <AppLayout>
    <Head :title="`Detalles Rifa: ${raffle.name}`" />

    <div class="page-content raffle-show-page">
      <div class="page-header mb-xl flex justify-between items-center flex-wrap gap-md">
        <div>
          <h1 class="page-title text-gradient">Gestión de números</h1>
          <p class="text-secondary">#{{ raffle.id }} - {{ raffle.name }}</p>
        </div>
        <div class="flex gap-sm">
          <Link :href="route('admin.raffles.index')" class="btn btn-secondary">Volver</Link>
          <Link :href="route('admin.raffles.edit', raffle.id)" class="btn btn-primary">Editar Configuración</Link>
        </div>
      </div>

      <section class="card admin-actions">
        <h2 class="section-title section-title-small">Resumen de la rifa: {{ raffle.name }}</h2>
        <div class="admin-dashboard-grid">
          <div class="admin-tile admin-tile-available">
            <h3>Disponibles</h3>
            <p class="metric-value">{{ totalAvailable }}</p>
            <p class="metric-label">Números libres</p>
          </div>
          <div class="admin-tile admin-tile-reserved">
            <h3>Reservados</h3>
            <p class="metric-value">{{ totalReserved }}</p>
            <p class="metric-label">Pendientes por pago / validación</p>
          </div>
          <div class="admin-tile admin-tile-sold">
            <h3>Vendidos</h3>
            <p class="metric-value">{{ totalPaid }}</p>
            <p class="metric-label">Asignados con pago validado</p>
          </div>
          <div class="admin-tile admin-tile-winner">
            <h3>Ganadores</h3>
            <p class="metric-value">{{ totalWinners }}</p>
            <p class="metric-label">Números marcados como ganadores</p>
          </div>
          <div class="admin-tile admin-tile-total">
            <h3>Total</h3>
            <p class="metric-value">{{ raffle.total_numbers }}</p>
            <p class="metric-label">Números definidos para esta rifa</p>
          </div>
        </div>
      </section>

      <section class="card admin-actions">
        <h2 class="section-title section-title-small">Asignar números a un comprador</h2>
        <form @submit.prevent="submitAssign" class="admin-form two-cols">
          <div class="admin-form-row">
            <label>Nombre del comprador</label>
            <input v-model="assignForm.buyer_name" type="text" required placeholder="Ej: Daniel Orellana" />
          </div>

          <div class="admin-form-row">
            <label>Nombre del blader (opcional)</label>
            <input v-model="assignForm.blader_name" type="text" placeholder="Ej: Blader Daniel" />
          </div>

          <div class="admin-form-row">
            <label>Teléfono (opcional)</label>
            <input v-model="assignForm.phone" type="text" placeholder="+56 9 ..." />
          </div>

          <div class="admin-form-row">
            <label>Correo (opcional)</label>
            <input v-model="assignForm.email" type="email" placeholder="correo@ejemplo.cl" />
          </div>

          <div class="admin-form-row full">
            <label>Números a asignar</label>
            <textarea v-model="assignNumbersStr" rows="2" placeholder="Ej: 1, 2, 5-8, 10"></textarea>
            <small>Usa comas y rangos. Ejemplo: <strong>1, 2, 5-8, 10</strong>.</small>
          </div>

          <div class="admin-form-row">
            <label>Comprobante de pago (opcional)</label>
            <input type="file" @change="e => assignForm.proof = e.target.files?.[0] || null" accept="image/*" />
            <small>JPG, PNG o WEBP, máx 10MB.</small>
          </div>

          <div class="admin-form-row">
            <label>Marcar como</label>
            <select v-model="assignForm.mark_as_paid">
              <option :value="true">VENDIDO</option>
              <option :value="false">RESERVADO</option>
            </select>
          </div>

          <div class="admin-form-row align-end">
            <button type="submit" class="btn btn-primary" :disabled="assignForm.processing">
              {{ assignForm.processing ? 'Asignando...' : 'Asignar números' }}
            </button>
          </div>
        </form>
      </section>

      <section class="card admin-actions">
        <h2 class="section-title section-title-small">Marcar número ganador</h2>
        <p class="info-text">Usa esta sección para marcar números ganadores. El número debe estar vendido o reservado.</p>

        <form @submit.prevent="submitWinner" class="admin-form winner-grid">
          <div class="admin-form-row">
            <label>Número ganador</label>
            <input v-model="winnerForm.winner_number" type="number" required placeholder="Ej: 15" class="winner-input" />
            <small>Ingresa un número de la rifa.</small>
          </div>

          <div class="admin-form-row">
            <label>Premio asignado</label>
            <select v-model="winnerForm.prize_position" required>
              <option value="">-- Selecciona el premio --</option>
              <option v-for="p in raffle.prizes" :key="p.id" :value="p.position">
                {{ p.position }}° - {{ p.title }}
              </option>
            </select>
            <small>Selecciona qué premio corresponde al ganador.</small>
          </div>

          <div class="admin-form-row full">
            <label>Foto de entrega del premio (opcional)</label>
            <input type="file" @change="e => winnerForm.winner_photo = e.target.files?.[0] || null" accept="image/*" />
            <small>JPG o PNG, máximo 10MB.</small>
          </div>

          <div class="admin-form-row align-end">
            <button type="submit" class="btn btn-primary btn-success" :disabled="winnerForm.processing">
              {{ winnerForm.processing ? 'Guardando...' : 'Marcar como Ganador' }}
            </button>
          </div>
        </form>
      </section>

      <section v-if="winners.length > 0" class="card admin-actions">
        <h2 class="section-title section-title-small">Fotos de entrega por ganador</h2>
        <p class="info-text">Sube o actualiza la foto de entrega del premio para cada número ganador.</p>

        <div class="winners-photo-grid">
          <article v-for="w in winners" :key="w.id" class="winner-photo-card">
            <div class="winner-photo-title">
              <span>🏆 #{{ String(w.number).padStart(2, '0') }}</span>
              <span v-if="w.prize_position" class="winner-prize">→ {{ getPrizeTitle(w.prize_position) }}</span>
            </div>

            <div class="winner-meta">
              {{ w.buyer_name || 'Sin nombre' }}
              <div v-if="w.blader_name">Blader: {{ w.blader_name }}</div>
            </div>

            <div v-if="w.winner_photo_url" class="winner-photo-preview">
              <img :src="w.winner_photo_url" alt="Foto ganador" />
            </div>
            <div v-else class="winner-photo-empty">Sin foto de entrega</div>

            <form @submit.prevent="uploadWinnerPhoto(w.number, $event)" class="winner-upload-form">
              <input type="file" name="winner_photo" accept="image/*" required />
              <button type="submit" class="btn btn-primary btn-sm w-full">
                {{ w.winner_photo_url ? 'Cambiar foto' : 'Subir foto' }}
              </button>
            </form>
          </article>
        </div>
      </section>

      <section class="card admin-actions">
        <div class="section-head-row">
          <h2 class="section-title section-title-small">Reservas y comprobantes</h2>
          <span class="badge badge-amber">Pendientes: {{ pendingReservationsCount }}</span>
        </div>

        <input
          v-model.trim="reservationSearch"
          type="text"
          class="form-input form-input-sm mb-md"
          placeholder="Buscar comprador, blader, correo o número..."
        />

        <div v-if="filteredReservations.length" class="reservations-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Comprador</th>
                <th>Contacto</th>
                <th>Números</th>
                <th>Estado</th>
                <th>Comprobante</th>
                <th class="text-right">Monto</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="reservation in filteredReservations" :key="reservation.id">
                <td>
                  <strong>{{ reservation.buyer_name }}</strong>
                  <div class="text-xs text-secondary">{{ reservation.blader_name || 'Sin blader' }}</div>
                </td>
                <td>
                  <div class="text-xs">{{ reservation.email || 'Sin correo' }}</div>
                  <div class="text-xs text-secondary">{{ reservation.phone || 'Sin teléfono' }}</div>
                </td>
                <td>
                  <div class="numbers-wrap">
                    <span v-for="number in reservation.numbers" :key="`${reservation.id}-${number}`" class="number-chip">{{ number }}</span>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="reservation.status === 'confirmed' ? 'badge-green' : 'badge-amber'">
                    {{ reservation.status === 'confirmed' ? 'VALIDADO' : 'PENDIENTE' }}
                  </span>
                </td>
                <td>
                  <button v-if="reservation.proof_url" type="button" class="btn btn-outline btn-xs" @click="openProofModal(reservation.proof_url)">
                    Ver
                  </button>
                  <span v-else class="text-xs text-secondary">Sin archivo</span>
                </td>
                <td class="text-right font-bold">${{ formatAmount(reservation.total_amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-sm text-secondary italic p-sm bg-white/5 rounded border border-white/10">No hay reservas para este filtro.</div>
      </section>

      <section class="card admin-actions">
        <h2 class="section-title section-title-small">Números de la rifa</h2>
        <div class="admin-numbers-toolbar">
          <p class="info-text">Haz clic en <strong>Liberar</strong> sobre un número vendido/reservado para devolverlo a disponible.</p>
          <button @click="clearAll" type="button" class="btn btn-secondary btn-clear-all">Liberar Todos</button>
        </div>

        <div class="numbers-grid-admin">
          <div
            v-for="n in numbersData"
            :key="n.id"
            class="number-admin-badge"
            :class="{
              'status-available': n.status === 'available',
              'status-reserved': n.status === 'reserved',
              'status-sold': n.status === 'sold',
              'status-winner': n.status === 'winner',
            }"
          >
            <div class="number-badge-circle">
              <span class="number-badge-num">{{ String(n.number).padStart(2, '0') }}</span>
            </div>

            <div class="number-badge-body">
              <div class="number-badge-label">
                <template v-if="n.status === 'available'">Libre</template>
                <template v-else-if="n.status === 'reserved'">Reservado</template>
                <template v-else-if="n.status === 'sold'">Vendido</template>
                <template v-else-if="n.status === 'winner'">Ganador</template>
              </div>

              <template v-if="n.status !== 'available'">
                <div v-if="n.buyer_name" class="number-badge-name">{{ n.buyer_name }}</div>
                <div v-if="n.blader_name" class="number-badge-name text-indigo-300">Blader: {{ n.blader_name }}</div>
                <div v-if="n.email" class="number-badge-phone">{{ n.email }}</div>
                <div v-if="n.phone" class="number-badge-phone">{{ n.phone }}</div>

                <div class="mt-xs">
                  <button
                    v-if="n.proof_url"
                    @click="openProofModal(n.proof_url)"
                    type="button"
                    class="proof-link"
                  >
                    📄 Comprobante
                  </button>

                  <form v-else @submit.prevent="uploadManualProof(n.number, $event)" class="manual-proof-form">
                    <label class="proof-upload-label">
                      + Subir comprobante
                      <input type="file" name="proof" accept="image/*" class="hidden" @change="$event.target.form.dispatchEvent(new Event('submit'))" />
                    </label>
                  </form>
                </div>
              </template>
            </div>

            <button v-if="n.status !== 'available'" @click="clearSingle(n.number)" class="link-clear-single" type="button">Liberar</button>
          </div>
        </div>
      </section>

      <div v-if="showProofModal" class="proof-modal-overlay" @click="showProofModal = false">
        <img :src="currentProof" class="proof-modal-image" @click.stop />
        <button class="proof-modal-close" type="button">&times;</button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  raffle: Object,
  mapped_numbers: Array,
  reservations: {
    type: Array,
    default: () => [],
  },
  prizeItems: {
    type: Array,
    default: () => [],
  },
});

const { ask } = useConfirm();
const { warning: toastWarning } = useToast();

const numbersData = computed(() => props.mapped_numbers || props.raffle?.mapped_numbers || []);
const winners = computed(() => numbersData.value.filter((n) => n.status === 'winner'));
const reservationSearch = ref('');

const totalAvailable = computed(() => numbersData.value.filter((n) => n.status === 'available').length);
const totalReserved = computed(() => numbersData.value.filter((n) => n.status === 'reserved').length);
const totalPaid = computed(() => numbersData.value.filter((n) => n.status === 'sold').length);
const totalWinners = computed(() => winners.value.length);
const pendingReservationsCount = computed(() => props.reservations.filter((row) => row.status === 'reserved').length);

const filteredReservations = computed(() => {
  const term = reservationSearch.value.toLowerCase();
  if (!term) return props.reservations;

  return props.reservations.filter((row) => {
    const numbersText = (row.numbers || []).join(', ');
    return [row.buyer_name, row.blader_name, row.email, row.phone, numbersText]
      .some((value) => String(value || '').toLowerCase().includes(term));
  });
});

const prizePreviewItems = computed(() => {
  if (props.prizeItems && props.prizeItems.length > 0) return props.prizeItems;

  return (props.raffle?.prizes || []).map((prize) => ({
    id: prize.id,
    position: prize.position,
    title: prize.title,
    description: prize.description,
    image_url: prize.image_path ? `/storage/${prize.image_path}` : null,
  }));
});

const formatAmount = (val) => new Intl.NumberFormat('es-CL').format(Math.floor(Number(val) || 0));

const getPrizeTitle = (pos) => {
  const p = prizePreviewItems.value.find((pr) => Number(pr.position) === Number(pos));
  return p ? `${p.position}° ${p.title}` : `${pos}° Premio`;
};

const assignForm = useForm({
  buyer_name: '',
  blader_name: '',
  phone: '',
  email: '',
  mark_as_paid: true,
  numbers: [],
  proof: null,
});

const assignNumbersStr = ref('');
const showProofModal = ref(false);
const currentProof = ref('');

const openProofModal = (url) => {
  currentProof.value = url;
  showProofModal.value = true;
};

const parseNumbersList = (text) => {
  const result = [];
  const parts = text.replace(/;/g, ',').replace(/\|/g, ',').split(/,|\s+/).filter(Boolean);

  parts.forEach((part) => {
    if (part.includes('-')) {
      let [start, end] = part.split('-').map((n) => Number.parseInt(n.trim(), 10));
      if (!Number.isNaN(start) && !Number.isNaN(end)) {
        if (start > end) [start, end] = [end, start];
        for (let i = start; i <= end; i += 1) result.push(i);
      }
    } else {
      const n = Number.parseInt(part, 10);
      if (!Number.isNaN(n)) result.push(n);
    }
  });

  return [...new Set(result)].sort((a, b) => a - b);
};

const submitAssign = () => {
  assignForm.numbers = parseNumbersList(assignNumbersStr.value);
  if (assignForm.numbers.length === 0) {
    toastWarning('Ingresa números válidos en el rango de esta rifa.');
    return;
  }

  assignForm.post(route('admin.raffles.assign', props.raffle.id), {
    onSuccess: () => {
      assignNumbersStr.value = '';
      assignForm.reset('buyer_name', 'blader_name', 'phone', 'email', 'numbers', 'proof');
      assignForm.mark_as_paid = true;
    },
  });
};

const winnerForm = useForm({
  winner_number: '',
  prize_position: '',
  winner_photo: null,
});

const submitWinner = () => {
  winnerForm.post(route('admin.raffles.numbers.winner', { raffle: props.raffle.id, number: winnerForm.winner_number }), {
    forceFormData: true,
    onSuccess: () => winnerForm.reset(),
  });
};

const clearSingle = async (num) => {
  const confirmed = await ask({
    title: 'Liberar Número',
    message: `¿Estás seguro de liberar el número #${num}?`,
    confirmText: 'Sí, liberar',
    tone: 'danger',
  });

  if (!confirmed) return;
  router.post(route('admin.raffles.numbers.clear', { raffle: props.raffle.id, number: num }), {}, { preserveScroll: true });
};

const clearAll = async () => {
  const confirmed = await ask({
    title: 'Liberar Todos',
    message: '¿Estás seguro de liberar TODOS los números de la rifa?',
    confirmText: 'Sí, liberar todo',
    tone: 'danger',
  });

  if (!confirmed) return;
  router.post(route('admin.raffles.numbers.clear-all', props.raffle.id), {}, { preserveScroll: true });
};

const uploadManualProof = (num, event) => {
  const file = event.target.proof?.files?.[0] || event.target.querySelector('input[type="file"]')?.files?.[0];
  if (!file) return;

  const form = new FormData();
  form.append('proof', file);

  router.post(route('admin.raffles.numbers.proof', { raffle: props.raffle.id, number: num }), form, {
    preserveScroll: true,
  });
};

const uploadWinnerPhoto = (num, event) => {
  const file = event.target.winner_photo?.files?.[0];
  if (!file) return;

  const form = new FormData();
  form.append('winner_photo', file);

  router.post(route('admin.raffles.numbers.winner-photo', { raffle: props.raffle.id, number: num }), form, {
    preserveScroll: true,
  });
};
</script>

<style scoped>
.admin-actions {
  margin-bottom: 16px;
}

.section-title {
  margin: 0 0 10px;
  font-size: 1.02rem;
  font-weight: 700;
}

.section-title-small {
  font-size: 0.95rem;
}

.info-text {
  margin: 4px 0 10px;
  color: var(--text-secondary);
  font-size: 0.85rem;
}

.admin-dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 12px;
  margin-top: 10px;
}

.admin-tile {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.03);
}

.admin-tile h3 {
  margin: 0;
  font-size: 0.86rem;
  font-weight: 700;
}

.metric-value {
  margin: 4px 0;
  font-family: var(--font-display);
  font-size: 1.8rem;
  font-weight: 800;
}

.metric-label {
  margin: 0;
  font-size: 0.72rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.admin-tile-available {
  border-color: rgba(59, 130, 246, 0.45);
}

.admin-tile-reserved {
  border-color: rgba(250, 204, 21, 0.45);
}

.admin-tile-sold {
  border-color: rgba(239, 68, 68, 0.45);
}

.admin-tile-winner {
  border-color: rgba(74, 222, 128, 0.45);
}

.admin-form {
  display: grid;
  gap: 12px;
}

.admin-form.two-cols {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.admin-form.winner-grid {
  grid-template-columns: 1fr 1fr;
  max-width: 680px;
}

.admin-form-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.admin-form-row.full {
  grid-column: 1 / -1;
}

.admin-form-row.align-end {
  justify-content: flex-end;
}

.admin-form-row label {
  font-size: 0.82rem;
  color: var(--text-secondary);
  font-weight: 700;
}

.admin-form-row input,
.admin-form-row textarea,
.admin-form-row select {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(0, 0, 0, 0.4);
  color: #fff;
}

.admin-form-row small {
  font-size: 0.74rem;
  color: var(--text-secondary);
}

.winner-input {
  font-size: 1.1rem;
  font-weight: 700;
}

.btn-success {
  background: linear-gradient(135deg, #4ade80, #22c55e);
  border-color: #4ade80;
}

.winners-photo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 14px;
}

.winner-photo-card {
  border-radius: 12px;
  border: 1px solid rgba(74, 222, 128, 0.45);
  background: rgba(74, 222, 128, 0.08);
  padding: 14px;
  display: grid;
  gap: 8px;
}

.winner-photo-title {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 800;
  color: #4ade80;
}

.winner-prize {
  font-size: 0.8rem;
  color: #fbbf24;
  font-family: var(--font-body);
}

.winner-meta {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.winner-photo-preview {
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.14);
}

.winner-photo-preview img {
  width: 100%;
  max-height: 200px;
  object-fit: cover;
}

.winner-photo-empty {
  padding: 18px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  color: var(--text-secondary);
  text-align: center;
  font-size: 0.82rem;
}

.winner-upload-form {
  display: grid;
  gap: 8px;
}

.section-head-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.reservations-table-wrap {
  overflow-x: auto;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table th,
.admin-table td {
  padding: 10px 8px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  text-align: left;
  vertical-align: top;
}

.admin-table th {
  font-size: 0.72rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.numbers-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.number-chip {
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.06);
  padding: 2px 8px;
  font-size: 0.72rem;
  font-weight: 700;
}

.admin-numbers-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.btn-clear-all {
  font-size: 0.78rem;
}

.numbers-grid-admin {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 12px;
}

.number-admin-badge {
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.18);
  padding: 10px 8px;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.number-admin-badge.status-available {
  border-color: rgba(59, 130, 246, 0.55);
}

.number-admin-badge.status-reserved {
  border-color: rgba(250, 204, 21, 0.55);
}

.number-admin-badge.status-sold {
  border-color: rgba(239, 68, 68, 0.55);
}

.number-admin-badge.status-winner {
  border-color: rgba(74, 222, 128, 0.55);
}

.number-badge-circle {
  width: 52px;
  height: 52px;
  margin: 0 auto;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-family: var(--font-display);
  background: rgba(0, 0, 0, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.number-badge-num {
  font-size: 1.15rem;
}

.number-badge-body {
  display: grid;
  gap: 2px;
}

.number-badge-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.number-badge-name {
  font-size: 0.78rem;
  color: var(--text-secondary);
}

.number-badge-phone {
  font-size: 0.72rem;
  color: var(--text-secondary);
}

.proof-link {
  background: transparent;
  border: 0;
  color: #4ade80;
  font-size: 0.8rem;
  cursor: pointer;
}

.manual-proof-form {
  display: grid;
}

.proof-upload-label {
  font-size: 0.72rem;
  cursor: pointer;
  color: var(--text-secondary);
}

.link-clear-single {
  margin-top: 4px;
  border: 1px solid rgba(239, 68, 68, 0.35);
  background: rgba(239, 68, 68, 0.12);
  color: #f87171;
  border-radius: 8px;
  padding: 4px 8px;
  font-size: 0.74rem;
  font-weight: 700;
  cursor: pointer;
}

.proof-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: calc(var(--z-modal) + 40);
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.proof-modal-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: 8px;
}

.proof-modal-close {
  position: absolute;
  top: 10px;
  right: 16px;
  border: 0;
  background: transparent;
  color: #fff;
  font-size: 2rem;
}

@media (max-width: 820px) {
  .admin-form.two-cols,
  .admin-form.winner-grid {
    grid-template-columns: 1fr;
  }
}
</style>
