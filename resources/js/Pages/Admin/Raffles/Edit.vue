<template>
  <AppLayout>
    <Head :title="`Editar Rifa: ${raffle.name}`" />

    <div class="page-content raffle-edit-page">
      <div class="page-header mb-xl flex justify-between items-center flex-wrap gap-md">
        <div>
          <h1 class="page-title text-gradient">Editar Rifa</h1>
          <p class="text-secondary">#{{ raffle.id }} - {{ raffle.name }}</p>
        </div>
        <div class="flex gap-sm">
          <Link :href="route('admin.raffles.index')" class="btn btn-secondary">Volver</Link>
          <button @click="deleteRaffle" class="btn btn-outline text-red-400 border-red-500/40" type="button">Eliminar</button>
        </div>
      </div>

      <div class="stats-grid mb-xl stagger">
        <div class="card stat-card">
          <span class="k">Recaudado</span>
          <strong class="v text-accent-green">${{ formatAmount(totalRevenue) }}</strong>
        </div>
        <div class="card stat-card">
          <span class="k">Pagados</span>
          <strong class="v text-accent-blue">{{ totalPaid }}</strong>
        </div>
        <div class="card stat-card">
          <span class="k">Reservados</span>
          <strong class="v text-accent-amber">{{ totalReserved }}</strong>
        </div>
        <div class="card stat-card">
          <span class="k">Disponibles</span>
          <strong class="v">{{ totalAvailable }}</strong>
        </div>
      </div>

      <div class="raffle-layout">
        <section class="card section-card">
          <h2 class="section-title">Información general</h2>
          <form @submit.prevent="updateRaffle" class="space-y-md">
            <div class="form-group">
              <label class="form-label">Nombre</label>
              <input type="text" v-model="editForm.name" class="form-input" required />
            </div>

            <div class="form-group">
              <label class="form-label">Descripción</label>
              <textarea v-model="editForm.description" class="form-input" rows="3" required></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Reglas</label>
              <textarea v-model="editForm.rules" class="form-input" rows="3"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
              <div class="form-group">
                <label class="form-label">Inicio de ventas</label>
                <input type="datetime-local" v-model="editForm.sales_start_at" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Fin de ventas</label>
                <input type="datetime-local" v-model="editForm.sales_end_at" class="form-input" />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
              <div class="form-group">
                <label class="form-label">Precio ticket</label>
                <input type="number" v-model="editForm.ticket_price" class="form-input" min="1" required />
              </div>
              <div class="form-group">
                <label class="form-label">Fecha sorteo</label>
                <input type="datetime-local" v-model="editForm.draw_at" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Estado</label>
                <select v-model="editForm.status" class="form-input">
                  <option value="draft">Borrador</option>
                  <option value="published">Publicada</option>
                  <option value="closed">Cerrada</option>
                  <option value="drawn">Sorteada</option>
                  <option value="cancelled">Cancelada</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
              <div class="form-group">
                <label class="form-label">Número ganador</label>
                <input type="number" v-model="editForm.winner_number" class="form-input" min="1" />
              </div>
              <div class="form-group">
                <label class="form-label">Foto ganador</label>
                <input type="file" @input="editForm.winner_photo = $event.target.files[0]" class="form-input" accept="image/*" />
              </div>
            </div>

            <h3 class="sub-title">Información de pago</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
              <div class="form-group">
                <label class="form-label">Banco</label>
                <input type="text" v-model="editForm.bank_name" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Titular</label>
                <input type="text" v-model="editForm.account_holder" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Número de cuenta</label>
                <input type="text" v-model="editForm.account_number" class="form-input" />
              </div>
              <div class="form-group">
                <label class="form-label">Tipo de cuenta</label>
                <input type="text" v-model="editForm.account_type" class="form-input" placeholder="Cuenta Vista" />
              </div>
              <div class="form-group md:col-span-2">
                <label class="form-label">Correo de pago</label>
                <input type="email" v-model="editForm.account_email" class="form-input" />
              </div>
              <div class="form-group md:col-span-2">
                <label class="form-label">Instrucciones</label>
                <textarea v-model="editForm.payment_instructions" class="form-input" rows="2"></textarea>
              </div>
            </div>

            <div class="flex justify-end">
              <button type="submit" class="btn btn-primary" :disabled="editForm.processing">Guardar cambios</button>
            </div>
          </form>
        </section>

        <div class="side-col stagger">
          <section class="card section-card">
            <h2 class="section-title">Premios</h2>

            <div class="prize-list mb-md">
              <article v-for="prize in raffle.prizes" :key="prize.id" class="prize-row">
                <div class="badge badge-red">#{{ prize.position }}</div>
                <div class="flex-1 overflow-hidden">
                  <p class="font-bold truncate">{{ prize.title }}</p>
                  <p v-if="prize.description" class="text-xs text-secondary truncate">{{ prize.description }}</p>
                </div>
                <button @click="deletePrize(prize.id)" class="btn btn-ghost btn-xs text-red-400" type="button">🗑️</button>
              </article>
              <div v-if="raffle.prizes.length === 0" class="text-secondary text-sm">No hay premios registrados.</div>
            </div>

            <form @submit.prevent="submitPrize" class="space-y-sm">
              <div class="form-group">
                <label class="form-label">Título premio</label>
                <input type="text" v-model="prizeForm.title" class="form-input" required />
              </div>
              <div class="grid grid-cols-2 gap-sm">
                <div class="form-group">
                  <label class="form-label">Pos</label>
                  <input type="number" v-model="prizeForm.position" class="form-input" min="1" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Img</label>
                  <input type="file" @input="prizeForm.image = $event.target.files[0]" class="form-input" accept="image/*" />
                </div>
              </div>
              <button type="submit" class="btn btn-secondary btn-sm w-full" :disabled="prizeForm.processing">Añadir premio</button>
            </form>
          </section>

          <section class="card section-card">
            <h2 class="section-title">Asignación manual</h2>
            <div class="numbers-scroll mb-md">
              <button
                v-for="n in raffle.numbers"
                :key="n.id"
                @click="toggleManualNumber(n.number)"
                class="num-chip"
                :class="[n.status, { selected: manualForm.numbers.includes(n.number) }]"
                :disabled="n.status !== 'available' && !manualForm.numbers.includes(n.number)"
                type="button"
              >
                {{ n.number }}
              </button>
            </div>

            <form @submit.prevent="submitManualAssign" class="space-y-sm">
              <input type="text" v-model="manualForm.buyer_name" class="form-input" placeholder="Nombre completo" required />
              <input type="text" v-model="manualForm.blader_name" class="form-input" placeholder="Nombre del blader (opcional)" />
              <input type="email" v-model="manualForm.email" class="form-input" placeholder="Correo (opcional)" />
              <input type="text" v-model="manualForm.phone" class="form-input" placeholder="WhatsApp" required />
              <input type="file" @change="manualForm.proof = $event.target.files[0]" class="form-input" accept="image/*" />
              <div class="flex items-center gap-xs text-sm">
                <input type="checkbox" v-model="manualForm.mark_as_paid" />
                <span>Marcar pagado</span>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-full" :disabled="manualForm.numbers.length === 0 || manualForm.processing">
                Asignar ({{ manualForm.numbers.length }})
              </button>
            </form>
          </section>
        </div>

        <section v-if="raffle.status !== 'draft'" class="card section-card" :class="raffle.status === 'drawn' ? 'drawn' : 'draw-box'">
          <h2 class="section-title">Sorteo</h2>
          <form v-if="raffle.status !== 'drawn'" @submit.prevent="submitDraw" class="space-y-sm">
            <div class="form-group">
              <label class="form-label">Número ganador</label>
              <input type="number" v-model="drawForm.winner_number" class="form-input" min="1" required />
            </div>
            <div class="form-group">
              <label class="form-label">Foto del sorteo</label>
              <input type="file" @input="drawForm.winner_photo = $event.target.files[0]" class="form-input" accept="image/*" />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="drawForm.processing">Finalizar rifa</button>
          </form>

          <div v-else class="drawn-box">
            <p class="text-secondary text-xs uppercase">Número ganador oficial</p>
            <p class="winner-number">#{{ raffle.winner_number }}</p>
            <img v-if="raffle.winner_photo" :src="`/storage/${raffle.winner_photo}`" class="winner-photo" alt="Ganador" />
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  raffle: Object
});

const { ask } = useConfirm();

const totalAvailable = computed(() => props.raffle.numbers.filter((n) => n.status === 'available').length);
const totalReserved = computed(() => props.raffle.numbers.filter((n) => n.status === 'reserved').length);
const totalPaid = computed(() => props.raffle.numbers.filter((n) => n.status === 'sold' || n.status === 'winner').length);
const totalRevenue = computed(() => totalPaid.value * props.raffle.ticket_price);

const formatAmount = (val) => new Intl.NumberFormat('es-CL').format(val);

const editForm = useForm({
  name: props.raffle.name,
  description: props.raffle.description,
  rules: props.raffle.rules || '',
  ticket_price: props.raffle.ticket_price,
  sales_start_at: props.raffle.sales_start_at ? props.raffle.sales_start_at.substring(0, 16) : '',
  sales_end_at: props.raffle.sales_end_at ? props.raffle.sales_end_at.substring(0, 16) : '',
  draw_at: props.raffle.draw_at ? props.raffle.draw_at.substring(0, 16) : '',
  status: props.raffle.status,
  winner_number: props.raffle.winner_number || '',
  winner_photo: null,
  bank_name: props.raffle.bank_name || '',
  account_holder: props.raffle.account_holder || '',
  account_number: props.raffle.account_number || '',
  account_type: props.raffle.account_type || '',
  account_email: props.raffle.account_email || '',
  payment_instructions: props.raffle.payment_instructions || '',
});

const prizeForm = useForm({
  title: '',
  description: '',
  position: props.raffle.prizes.length + 1,
  image: null,
});

const manualForm = useForm({
  numbers: [],
  buyer_name: '',
  phone: '',
  email: '',
  mark_as_paid: true,
  proof: null,
});

const drawForm = useForm({
  winner_number: '',
  winner_photo: null,
});

const toggleManualNumber = (num) => {
  const idx = manualForm.numbers.indexOf(num);
  if (idx > -1) manualForm.numbers.splice(idx, 1);
  else manualForm.numbers.push(num);
};

const updateRaffle = () => {
  editForm.transform((data) => ({
    ...data,
    _method: 'PUT',
  })).post(route('admin.raffles.update', props.raffle.id), {
    preserveScroll: true
  });
};

const deleteRaffle = async () => {
  const confirmed = await ask({
    title: 'Eliminar rifa',
    message: '¿Seguro que deseas eliminar esta rifa? Todos los números y premios se perderán.',
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.raffles.destroy', props.raffle.id));
};

const submitPrize = () => {
  prizeForm.post(route('admin.raffles.prizes.store', props.raffle.id), {
    preserveScroll: true,
    onSuccess: () => {
      prizeForm.reset('title', 'description', 'image');
      prizeForm.position = props.raffle.prizes.length + 1;
    }
  });
};

const deletePrize = async (id) => {
  const confirmed = await ask({
    title: 'Eliminar premio',
    message: '¿Eliminar este premio?',
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.raffles.prizes.destroy', id), { preserveScroll: true });
};

const submitManualAssign = () => {
  manualForm.post(route('admin.raffles.assign', props.raffle.id), {
    preserveScroll: true,
    onSuccess: () => {
      manualForm.reset();
    }
  });
};

const submitDraw = async () => {
  const confirmed = await ask({
    title: 'Confirmar sorteo',
    message: `¿Confirmar que el número ${drawForm.winner_number} es el ganador?`,
    confirmText: 'Confirmar',
    tone: 'primary',
  });

  if (!confirmed) return;

  drawForm.post(route('admin.raffles.draw', props.raffle.id), {
    preserveScroll: true
  });
};

const approveRes = async (id) => {
  const confirmed = await ask({
    title: 'Aprobar reserva',
    message: '¿Confirmar que el pago ha sido recibido y validar la reserva?',
    confirmText: 'Aprobar pago',
    tone: 'primary',
  });

  if (!confirmed) return;
  router.post(route('admin.raffles.approve', id), {}, { preserveScroll: true });
};
</script>

<style scoped>
.raffle-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: var(--space-lg);
  align-items: start;
}

.side-col {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.section-card {
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.section-title {
  margin-bottom: var(--space-md);
  font-size: 1rem;
}

.sub-title {
  margin-top: var(--space-md);
  margin-bottom: var(--space-xs);
  font-size: 0.92rem;
  color: var(--accent-blue);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-sm);
}

.stat-card {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-card .k {
  font-size: 0.7rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.stat-card .v {
  font-family: var(--font-display);
  font-size: 1.5rem;
}

.prize-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.prize-row {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: var(--radius-sm);
  padding: var(--space-sm);
}

.numbers-scroll {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  max-height: 180px;
  overflow-y: auto;
  padding: var(--space-xs);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  background: rgba(0, 0, 0, 0.2);
}

.num-chip {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: var(--bg-card);
  cursor: pointer;
  font-weight: 700;
}

.num-chip.selected {
  background: rgba(59, 130, 246, 0.35);
  border-color: rgba(59, 130, 246, 0.95);
}

.num-chip.sold,
.num-chip.reserved,
.num-chip.winner {
  opacity: 0.4;
  cursor: not-allowed;
}

.num-chip.winner {
  opacity: 0.85;
  border-color: rgba(16, 185, 129, 0.7);
  background: rgba(16, 185, 129, 0.25);
}

.draw-box {
  border-color: rgba(245, 158, 11, 0.35);
  background: rgba(245, 158, 11, 0.06);
}

.drawn {
  border-color: rgba(16, 185, 129, 0.3);
  background: rgba(16, 185, 129, 0.05);
}

.raffle-layout .full-width {
  grid-column: 1 / -1;
}

.badge-mini {
  background: rgba(255, 255, 255, 0.1);
  color: var(--text-primary);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.table-wrap {
  overflow-x: auto;
}

.gx-table {
  width: 100%;
  border-collapse: collapse;
}

.gx-table th, .gx-table td {
  padding: var(--space-md) var(--space-sm);
  text-align: left;
  border-bottom: 1px solid var(--border-color);
}

.gx-table th {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--text-secondary);
  background: rgba(0, 0, 0, 0.1);
}

.drawn-box {
  text-align: center;
}

.winner-number {
  font-family: var(--font-display);
  font-size: 3rem;
  font-weight: 800;
  line-height: 1;
  margin: var(--space-sm) 0;
}

.winner-photo {
  max-height: 220px;
  width: 100%;
  object-fit: cover;
  border-radius: var(--radius-md);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

@media (max-width: 1100px) {
  .raffle-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 700px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>

