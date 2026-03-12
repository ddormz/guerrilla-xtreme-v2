<template>
  <AppLayout>
    <Head :title="raffle.name" />

    <div class="raffle-show page-content py-xl">
      <div class="max-w-6xl mx-auto space-y-xl">
        <section class="card raffle-hero">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-md mb-md">
            <div>
              <h1 class="text-2xl font-bold text-gradient">{{ raffle.name }}</h1>
              <p class="text-secondary text-sm mt-xs">Estado: <strong class="text-white">{{ raffle.status_label || raffle.status }}</strong></p>
            </div>
            <span class="badge" :class="statusBadgeClass">{{ raffle.status_label || raffle.status }}</span>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
            <article class="info-panel">
              <h3 class="panel-title">Descripción</h3>
              <p class="text-sm text-secondary whitespace-pre-line">{{ raffle.description || 'Sin descripción.' }}</p>

              <h3 class="panel-title mt-md">Bases de la rifa</h3>
              <p class="text-sm text-secondary whitespace-pre-line">{{ raffle.rules || 'Sin bases registradas.' }}</p>
            </article>

            <article class="info-panel">
              <h3 class="panel-title">Premios</h3>
              <div class="prizes-grid">
                <div v-for="prize in raffle.prizes" :key="prize.position" class="prize-card">
                  <div class="prize-media">
                    <img v-if="prize.image_url" :src="prize.image_url" :alt="prize.title" class="prize-image" @error="handleImageError" />
                    <div v-else class="prize-placeholder">🎁</div>
                  </div>
                  <div>
                    <p class="font-bold text-sm text-white">{{ prize.position }}° {{ prize.title }}</p>
                    <p v-if="prize.description" class="text-xs text-secondary">{{ prize.description }}</p>
                  </div>
                </div>
                <p v-if="!raffle.prizes?.length" class="text-sm text-secondary">No hay premios definidos.</p>
              </div>
            </article>
          </div>
        </section>

        <section v-if="raffle.winners && raffle.winners.length" class="card">
          <h2 class="text-lg font-black mb-md">Números ganadores</h2>
          <div class="winners-grid">
            <article v-for="winner in raffle.winners" :key="`${winner.number}-${winner.prize_position}`" class="winner-card">
              <div class="winner-head">
                <strong>#{{ winner.number }}</strong>
                <span class="badge badge-amber">{{ winner.prize_position }}° premio</span>
              </div>
              <p class="text-sm font-bold text-white">{{ winner.prize_title || 'Premio' }}</p>
              <p class="text-xs text-secondary">Blader: {{ winner.blader_name || winner.buyer_name || 'Sin dato' }}</p>
              <img v-if="winner.winner_photo_url" :src="winner.winner_photo_url" class="winner-photo" alt="Foto de entrega" @error="handleImageError" />
              <div v-else class="winner-photo-empty">Sin foto de entrega</div>
            </article>
          </div>
        </section>

        <div class="max-w-4xl mx-auto" v-if="isReservationEnabled">
          <div class="steps-progress">
            <div class="progress-line" :style="{ width: ((currentStep - 1) / 3) * 100 + '%' }"></div>
            <div v-for="step in 4" :key="step" class="step-node" :class="{ active: currentStep >= step, current: currentStep === step }">
              <span class="step-icon">{{ step }}</span>
              <span class="step-label">{{ stepLabel(step) }}</span>
            </div>
          </div>
        </div>

        <div class="max-w-5xl mx-auto">
          <div v-if="currentStep === 1" class="step-container stagger">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-xl">
              <div class="lg:col-span-2">
                <div class="card p-xl">
                  <div class="flex justify-between items-center mb-lg flex-wrap gap-sm">
                    <h2 class="text-xl font-bold">Selecciona tus números</h2>
                    <div class="grid-legend flex gap-md flex-wrap">
                      <span class="legend-item"><span class="box available"></span> Disponible</span>
                      <span class="legend-item"><span class="box selected"></span> Seleccionado</span>
                      <span class="legend-item"><span class="box mine"></span> Mis números</span>
                      <span class="legend-item"><span class="box reserved"></span> Ocupado/Reservado</span>
                    </div>
                  </div>

                  <div v-if="!isReservationEnabled" class="message-box warn mb-lg border-amber-500/50 bg-amber-500/10 text-amber-400">
                    Esta rifa no acepta más reservas.
                  </div>

                  <div class="numbers-grid">
                    <button
                      v-for="n in raffle.numbers"
                      :key="n.number"
                      class="number-btn"
                      :class="numberClass(n)"
                      :disabled="n.status !== 'available' || !isReservationEnabled"
                      @click="toggleNumber(n.number)"
                      type="button"
                    >
                      {{ n.number }}
                    </button>
                  </div>
                </div>
              </div>

              <div class="sidebar-info">
                <div class="card p-lg text-center shadow-lg" :class="{ 'opacity-50 blur-[1px]': selectedNumbers.length === 0 }">
                  <h2 class="text-3xl font-bold mb-xs">${{ formatPrice(selectedNumbers.length * raffle.ticket_price) }}</h2>
                  <p class="text-secondary text-sm mb-lg">{{ selectedNumbers.length }} números seleccionados</p>

                  <button
                    @click="nextStep"
                    class="btn btn-primary btn-block btn-lg"
                    :disabled="selectedNumbers.length === 0 || !isReservationEnabled"
                    type="button"
                  >
                    Siguiente paso →
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="currentStep === 2 && isReservationEnabled" class="step-container max-w-2xl mx-auto stagger">
            <div class="card p-xl text-center">
              <div class="mb-xl">
                <div class="w-16 h-16 bg-accent-green/10 text-accent-green rounded-full flex items-center justify-center mx-auto mb-md">💳</div>
                <h2 class="text-2xl font-bold">Información de pago</h2>
                <p class="text-secondary mt-sm">
                  Realiza la transferencia por
                  <strong class="text-white">${{ formatPrice(selectedNumbers.length * raffle.ticket_price) }}</strong>
                </p>
              </div>

              <div class="payment-card mb-xl text-left">
                <div v-for="(val, label) in paymentInfo" :key="label" class="payment-row">
                  <span class="payment-label">{{ label }}</span>
                  <p class="payment-value">{{ val }}</p>
                </div>
              </div>

              <button class="btn btn-outline mb-lg" @click="copyAllPaymentInfo" type="button">COPIAR INFORMACIÓN DE PAGO</button>

              <div class="actions-row">
                <button @click="currentStep--" class="btn btn-secondary flex-1" type="button">← Volver</button>
                <button @click="nextStep" class="btn btn-primary flex-1" type="button">Ya transferí, continuar →</button>
              </div>
            </div>
          </div>

          <div v-if="currentStep === 3 && isReservationEnabled" class="step-container max-w-2xl mx-auto stagger">
            <div class="card p-xl">
              <h2 class="text-2xl font-bold mb-lg text-center">Tus datos</h2>

              <form @submit.prevent="submitReservation" class="space-y-md">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                  <div class="form-group">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" v-model="form.buyer_name" class="form-input" required />
                    <span v-if="form.errors.buyer_name" class="form-error">{{ form.errors.buyer_name }}</span>
                  </div>
                  <div class="form-group">
                    <label class="form-label">WhatsApp *</label>
                    <input type="tel" v-model="form.phone" @input="validatePhone" class="form-input" placeholder="Solo números" required />
                    <span v-if="form.errors.phone" class="form-error">{{ form.errors.phone }}</span>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                  <div class="form-group">
                    <label class="form-label">Nombre del Blader</label>
                    <input type="text" v-model="form.blader_name" class="form-input" />
                    <span v-if="form.errors.blader_name" class="form-error">{{ form.errors.blader_name }}</span>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" v-model="form.email" class="form-input" required />
                    <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
                  </div>
                </div>

                <div class="proof-upload p-xl text-center">
                  <input
                    ref="proofInput"
                    type="file"
                    @change="onProofSelected"
                    accept="image/*"
                    class="sr-only"
                    id="proof_file"
                    required
                  />

                  <div v-if="!form.proof" class="space-y-sm">
                    <div class="text-3xl">📄</div>
                    <h3 class="font-bold">Sube tu comprobante *</h3>
                    <button class="btn btn-secondary btn-sm mt-md" type="button" @click="triggerProofPicker">Seleccionar archivo</button>
                  </div>

                  <div v-else class="proof-selected">
                    <div class="flex items-center gap-sm text-left">
                      <span class="proof-icon">📸</span>
                      <div>
                        <p class="text-sm font-bold truncate max-w-[220px]">{{ form.proof.name }}</p>
                        <p class="text-xs text-secondary">{{ (form.proof.size / 1024 / 1024).toFixed(2) }} MB</p>
                      </div>
                    </div>
                    <button type="button" @click="removeProof" class="text-gx-red text-sm font-bold">Eliminar</button>
                  </div>
                  <span v-if="form.errors.proof" class="form-error block mt-sm">{{ form.errors.proof }}</span>
                </div>

                <div class="actions-row pt-lg">
                  <button type="button" @click="currentStep--" class="btn btn-secondary flex-1">← Volver</button>
                  <button type="submit" class="btn btn-primary flex-1 btn-lg" :disabled="form.processing">
                    {{ form.processing ? 'Registrando...' : 'Finalizar registro' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <div v-if="currentStep === 4 && isReservationEnabled" class="step-container max-w-2xl mx-auto stagger">
            <div class="card p-xl text-center overflow-hidden relative">
              <div class="mb-xl">
                <div class="w-20 h-20 bg-accent-green text-white rounded-full flex items-center justify-center mx-auto mb-lg">✓</div>
                <h2 class="text-3xl font-bold">Reserva recibida</h2>
                <p class="text-secondary mt-sm px-xl">Tu solicitud está en revisión. Te notificaremos cuando el pago sea validado.</p>
              </div>

              <div class="ticket-summary compact-summary">
                <div class="summary-header">
                  <div>
                    <h4 class="text-xs text-secondary uppercase font-bold tracking-widest">Rifa Guerrilla</h4>
                    <h3 class="text-lg font-display uppercase tracking-tight">{{ raffle.name }}</h3>
                  </div>
                  <img src="/img/logo.png" class="summary-logo" alt="GX" />
                </div>

                <div class="summary-grid">
                  <div>
                    <span class="text-[10px] text-secondary uppercase block mb-xs">Números</span>
                    <p class="summary-numbers">{{ formattedFinalNumbers }}</p>
                  </div>
                  <div class="text-right">
                    <span class="text-[10px] text-secondary uppercase block mb-xs">Total</span>
                    <p class="text-xl font-display font-bold text-gradient">${{ formatPrice(finalNumbers.length * raffle.ticket_price) }}</p>
                  </div>
                </div>
              </div>

              <div class="actions-row center mt-xl">
                <Link :href="route('raffles.index')" class="btn btn-secondary">Ir a rifas</Link>
                <button @click="resetFlow" class="btn btn-ghost" type="button">Comprar más</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from '@/Composables/useToast';

const { success: toastSuccess, error: toastError, warning: toastWarning } = useToast();

const props = defineProps({
  raffle: Object,
  paymentInfo: Object,
});

const currentStep = ref(1);
const selectedNumbers = ref([]);
const finalNumbers = ref([]);
const proofInput = ref(null);

const isReservationEnabled = computed(() => !!props.raffle?.can_reserve);
const selectedSet = computed(() => new Set(selectedNumbers.value));
const formattedFinalNumbers = computed(() => [...finalNumbers.value].sort((a, b) => a - b).join(', '));

const form = useForm({
  numbers: [],
  buyer_name: '',
  blader_name: '',
  email: '',
  phone: '',
  proof: null,
});

const stepLabel = (step) => ['Selección', 'Pago', 'Datos', 'Resumen'][step - 1];

const statusBadgeClass = computed(() => {
  const status = props.raffle?.status;
  if (status === 'published') return 'badge-green';
  if (status === 'closed') return 'badge-amber';
  if (status === 'drawn') return 'badge-blue';
  if (status === 'cancelled') return 'badge-red';
  return 'badge';
});

const validatePhone = (e) => {
  form.phone = e.target.value.replace(/\D/g, '');
};

const numberClass = (numberItem) => {
  const isSelected = selectedSet.value.has(numberItem.number);
  return {
    available: numberItem.status === 'available',
    selected: isSelected,
    mine: !!numberItem.is_mine,
    reserved: numberItem.status !== 'available',
  };
};

const toggleNumber = (num) => {
  if (!isReservationEnabled.value) return;

  const idx = selectedNumbers.value.indexOf(num);
  if (idx > -1) {
    selectedNumbers.value.splice(idx, 1);
  } else {
    selectedNumbers.value.push(num);
  }
};

const formatPrice = (price) => new Intl.NumberFormat('es-CL').format(price);

const nextStep = () => {
  if (!isReservationEnabled.value) {
    toastWarning('Esta rifa no acepta más reservas.');
    return;
  }

  if (currentStep.value < 4) {
    currentStep.value++;
  }
};

const copyAllPaymentInfo = async () => {
  const text = Object.entries(props.paymentInfo || {})
    .map(([key, val]) => `${key}: ${val}`)
    .join('\n');

  try {
    await navigator.clipboard.writeText(text);
    toastSuccess('Información de pago copiada');
  } catch {
    toastError('No se pudo copiar la información de pago.');
  }
};

const triggerProofPicker = () => {
  proofInput.value?.click();
};

const onProofSelected = (event) => {
  const file = event.target.files?.[0];
  if (file) {
    form.proof = file;
  }
};

const removeProof = () => {
  form.proof = null;
  if (proofInput.value) {
    proofInput.value.value = '';
  }
};

const submitReservation = () => {
  if (!isReservationEnabled.value) {
    toastWarning('Esta rifa no acepta más reservas.');
    return;
  }

  form.numbers = [...selectedNumbers.value];
  form.post(route('raffles.reserve', props.raffle.id), {
    preserveScroll: true,
    onSuccess: () => {
      finalNumbers.value = [...selectedNumbers.value].sort((a, b) => a - b);
      currentStep.value = 4;
    },
  });
};

const resetFlow = () => {
  currentStep.value = 1;
  selectedNumbers.value = [];
  finalNumbers.value = [];
  form.reset();
  if (proofInput.value) {
    proofInput.value.value = '';
  }
};

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.raffle-hero .panel-title {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.info-panel {
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  background: var(--bg-secondary);
}

.prizes-grid {
  display: grid;
  gap: var(--space-sm);
}

.prize-card {
  display: grid;
  grid-template-columns: 56px 1fr;
  gap: var(--space-sm);
  align-items: center;
  padding: var(--space-sm);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-card);
}

.prize-media {
  width: 56px;
  height: 56px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.prize-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.prize-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-secondary);
}

.winners-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: var(--space-md);
}

.winner-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  display: grid;
  gap: 8px;
}

.winner-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.winner-photo {
  width: 100%;
  height: 140px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--border-color);
}

.winner-photo-empty {
  height: 140px;
  border: 1px dashed var(--border-color);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  font-size: 0.8rem;
}

.steps-progress {
  position: relative;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-sm);
}

.progress-line {
  position: absolute;
  top: 19px;
  left: 20px;
  height: 3px;
  background: var(--gx-red);
  z-index: 1;
  transition: width 0.4s ease;
}

.step-node {
  position: relative;
  z-index: 5;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.step-icon {
  width: 40px;
  height: 40px;
  background: var(--bg-card);
  border: 2px solid var(--border-color);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
}

.step-node.active .step-icon {
  border-color: var(--gx-red);
}

.step-label {
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 0.05em;
}

.number-btn {
  aspect-ratio: 1;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  color: white;
  font-size: 0.85rem;
  font-weight: 700;
  transition: all 0.2s ease;
}

.number-btn.available {
  background: #14532d;
  border-color: #10b981;
}

.number-btn.selected {
  background: #2563eb;
  border-color: #60a5fa;
}

.number-btn.mine {
  background: #312e81;
  border-color: #818cf8;
  box-shadow: inset 0 0 0 2px #c4b5fd;
}

.number-btn.reserved {
  background: #7f1d1d;
  border-color: #ef4444;
  opacity: 0.58;
  cursor: not-allowed;
}

.numbers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
  gap: 8px;
}

.box {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 4px;
  margin-right: 4px;
}

.box.available { background: #10b981; }
.box.selected { background: #2563eb; }
.box.mine { background: #6366f1; }
.box.reserved { background: #ef4444; }

.payment-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--space-md);
}

.payment-row {
  padding: var(--space-sm) 0;
  border-bottom: 1px solid var(--border-color);
}

.payment-row:last-child {
  border-bottom: 0;
}

.payment-label {
  display: inline-block;
  font-size: 0.72rem;
  text-transform: uppercase;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #60a5fa;
}

.payment-value {
  margin-top: 4px;
  font-family: var(--font-body);
  color: #f8fafc;
  word-break: break-word;
}

.proof-upload {
  border: 2px dashed var(--border-color-light);
  border-radius: var(--radius-lg);
  background: var(--bg-secondary);
}

.proof-icon {
  background: #14532d;
  border: 1px solid #10b981;
  border-radius: 8px;
  padding: 4px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.proof-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
  padding: var(--space-sm);
}

.actions-row {
  display: flex;
  gap: var(--space-md);
  flex-wrap: wrap;
}

.actions-row.center {
  justify-content: center;
}

.compact-summary {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--space-md);
}

.summary-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
  border-bottom: 1px solid var(--border-color);
  padding-bottom: var(--space-sm);
  margin-bottom: var(--space-sm);
}

.summary-logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
  opacity: 0.65;
}

.summary-grid {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: var(--space-md);
  align-items: start;
}

.summary-numbers {
  font-weight: 700;
  color: var(--accent-green);
  letter-spacing: 0.01em;
}

@media (max-width: 640px) {
  .actions-row {
    flex-direction: column;
  }

  .actions-row .btn {
    width: 100%;
  }

  .prize-card {
    grid-template-columns: 44px 1fr;
  }

  .prize-media {
    width: 44px;
    height: 44px;
  }
}
</style>
