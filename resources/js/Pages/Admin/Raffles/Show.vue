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

      <div class="grid-layout">
        <!-- Main Column (Left) -->
        <div class="flex flex-col gap-xl">
          
          <!-- Quick Assignment Form -->
          <section class="card section-card">
            <h2 class="section-title">Asignar números manualmente</h2>
            <form @submit.prevent="submitAssign" class="flex flex-col gap-md mt-md">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div class="form-group">
                  <label class="form-label">Nombre del comprador <span class="text-gx-red">*</span></label>
                  <input v-model="assignForm.buyer_name" type="text" class="form-input" required placeholder="Ej: Daniel Orellana">
                </div>
                <div class="form-group">
                  <label class="form-label">Nombre del blader</label>
                  <input v-model="assignForm.blader_name" type="text" class="form-input" placeholder="Ej: Blader Daniel">
                </div>
                <div class="form-group">
                  <label class="form-label">Teléfono</label>
                  <input v-model="assignForm.phone" type="text" class="form-input" placeholder="+56 9 ...">
                </div>
                <div class="form-group">
                  <label class="form-label">Correo</label>
                  <input v-model="assignForm.email" type="email" class="form-input" placeholder="correo@ejemplo.cl">
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">Números a asignar (comas o rangos) <span class="text-gx-red">*</span></label>
                <input v-model="assignNumbersStr" type="text" class="form-input" required placeholder="Ej: 1, 2, 5-8, 10">
                <small class="text-xs text-secondary mt-xs inline-block">Separa con comas. Ej: 1, 2, 5-8, 10 asigna 1, 2, 5, 6, 7, 8, 10.</small>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-md items-end">
                <div class="form-group">
                  <label class="form-label">Estado</label>
                  <select v-model="assignForm.mark_as_paid" class="form-select">
                    <option :value="false">Reservado (Pendiente)</option>
                    <option :value="true">Vendido (Pagado)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Comprobante (opcional)</label>
                  <input type="file" @change="e => assignForm.proof = e.target.files[0]" accept="image/*" class="form-input file-input" />
                </div>
              </div>
              <div class="flex justify-end mt-sm">
                <button type="submit" class="btn btn-primary" :disabled="assignForm.processing">
                  {{ assignForm.processing ? 'Asignando...' : 'Asignar números' }}
                </button>
              </div>
            </form>
          </section>

          <!-- Draw Winner Form -->
          <section class="card section-card">
            <h2 class="section-title">Marcar número ganador</h2>
            <p class="text-sm text-secondary mb-md">Marca los números vendidos/reservados como ganadores. Puedes elegir un premio y subir la foto de entrega.</p>
            <form @submit.prevent="submitWinner" class="grid grid-cols-1 md:grid-cols-2 gap-md items-end">
              <div class="form-group">
                <label class="form-label">Número ganador <span class="text-gx-red">*</span></label>
                <input v-model="winnerForm.winner_number" type="number" class="form-input font-display text-lg" required placeholder="Ej: 15">
              </div>
              <div class="form-group">
                <label class="form-label">Premio asignado <span class="text-gx-red">*</span></label>
                <select v-model="winnerForm.prize_position" class="form-select" required>
                  <option value="">-- Selecciona el premio --</option>
                  <option v-for="p in raffle.prizes" :key="p.id" :value="p.position">{{ p.position }}° - {{ p.title }}</option>
                </select>
              </div>
              <div class="form-group md:col-span-2">
                <label class="form-label">Foto de entrega (opcional ahora, se puede subir después)</label>
                <input type="file" @change="e => winnerForm.winner_photo = e.target.files[0]" accept="image/*" class="form-input file-input" />
              </div>
              <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="btn btn-primary bg-gx-success border-gx-success" :disabled="winnerForm.processing">
                  Marcar como Ganador
                </button>
              </div>
            </form>
          </section>

          <!-- Winners Delivery Photos -->
          <section v-if="winners.length > 0" class="card section-card">
            <h2 class="section-title mb-md">📸 Fotos de entrega por ganador</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
              <div v-for="w in winners" :key="w.id" class="p-md rounded-xl border border-gx-success bg-gx-success/10">
                <div class="flex items-center gap-sm mb-sm text-gx-success font-display text-xl font-bold">
                  🏆 #{{ w.number.toString().padStart(2, '0') }}
                  <span v-if="w.prize_position" class="text-sm text-amber-400 font-sans">
                    → {{ getPrizeTitle(w.prize_position) }}
                  </span>
                </div>
                <div class="text-sm text-secondary mb-md">
                  {{ w.buyer_name || 'Sin nombre' }}
                  <div v-if="w.blader_name" class="text-indigo-300">Blader: {{ w.blader_name }}</div>
                </div>

                <div v-if="w.winner_photo_url" class="mb-md">
                  <img :src="w.winner_photo_url" class="w-full max-h-[200px] object-cover rounded-lg border border-white/10" alt="Foto ganador" />
                </div>
                <div v-else class="mb-md p-lg bg-white/5 rounded-lg text-center text-secondary text-sm">
                  Sin foto de entrega
                </div>

                <form @submit.prevent="uploadWinnerPhoto(w.number, $event)" class="flex flex-col gap-sm">
                  <input type="file" name="winner_photo" accept="image/*" required class="form-input text-sm p-sm file-input">
                  <button type="submit" class="btn btn-primary btn-sm mx-auto w-full">
                    {{ w.winner_photo_url ? 'Cambiar foto' : 'Subir foto' }}
                  </button>
                </form>
              </div>
            </div>
          </section>

          <!-- The Raffle Numbers Grid -->
          <section class="card section-card">
            <div class="flex flex-wrap justify-between items-end gap-md mb-md">
              <div>
                <h2 class="section-title">Números de la rifa</h2>
                <p class="text-sm text-secondary">
                  Haz clic en <strong class="text-white">Liberar</strong> sobre un número vendido/reservado para devolverlo a disponible.
                </p>
              </div>
              <button @click="clearAll" type="button" class="btn btn-secondary border-gx-red text-gx-red hover:bg-gx-red hover:text-white">
                Liberar Todos
              </button>
            </div>

            <div class="numbers-grid-admin">
              <div 
                v-for="n in raffle.mapped_numbers" 
                :key="n.id" 
                class="number-admin-badge" 
                :class="{
                  'status-available': n.status === 'available',
                  'status-reserved': n.status === 'reserved',
                  'status-sold': n.status === 'sold',
                  'status-winner': n.status === 'winner'
                }"
              >
                <div class="number-badge-circle">
                  <span class="number-badge-num">{{ n.number.toString().padStart(2, '0') }}</span>
                </div>
                
                <div class="number-badge-body flex-1">
                  <div class="number-badge-label mb-xs uppercase">
                    <template v-if="n.status === 'available'">Libre</template>
                    <template v-else-if="n.status === 'reserved'">Reservado</template>
                    <template v-else-if="n.status === 'sold'">Vendido</template>
                    <template v-else-if="n.status === 'winner'">Ganador</template>
                  </div>
                  
                  <template v-if="n.status !== 'available'">
                    <div class="buyer-details bg-black/40 p-xs rounded border border-white/5 mb-sm text-left">
                      <div v-if="n.buyer_name" class="font-bold text-xs text-white truncate" :title="n.buyer_name">{{ n.buyer_name }}</div>
                      <div v-if="n.blader_name" class="text-[10px] text-accent-blue truncate">B: {{ n.blader_name }}</div>
                      <div v-if="n.phone" class="text-[10px] text-secondary truncate">{{ n.phone }}</div>
                    </div>

                    <!-- Proof UI -->
                    <div class="mt-auto">
                      <button v-if="n.proof_url" @click="openProofModal(n.proof_url)" type="button" class="btn btn-ghost btn-xs w-full text-accent-green hover:bg-accent-green/10 flex justify-center items-center gap-xs">
                        👁️ Comprobante
                      </button>
                      <form v-else @submit.prevent="uploadManualProof(n.number, $event)" class="w-full">
                        <label class="text-[10px] text-secondary hover:text-white cursor-pointer transition-colors block w-full border border-dashed border-white/20 p-1 rounded text-center">
                          + Pago
                          <input type="file" name="proof" class="hidden" @change="$event.target.form.dispatchEvent(new Event('submit'))" accept="image/*">
                        </label>
                      </form>
                    </div>
                  </template>
                  <template v-else>
                    <div class="text-[10px] text-secondary/40 italic py-md">Disponible</div>
                  </template>
                </div>

                <!-- Clear Single Button -->
                 <button v-if="n.status !== 'available'" @click="clearSingle(n.number)" class="btn btn-ghost text-gx-red hover:bg-gx-red/20 btn-xs mt-sm w-full font-bold border border-gx-red/20">
                   Liberar
                 </button>
              </div>
            </div>

            <div v-if="showProofModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 p-xl animate-in fade-in duration-300" @click="showProofModal = false">
              <img :src="currentProof" class="max-w-full max-h-full object-contain shadow-2xl animate-in zoom-in duration-300" @click.stop />
              <button class="absolute top-md right-md text-white/50 hover:text-white text-5xl transition-colors">&times;</button>
            </div>
          </section>
        </div>

        <!-- Sidebar (Right) -->
        <div class="flex flex-col gap-xl">
          <!-- Stats Panel -->
          <div class="card p-md">
             <h3 class="text-lg font-bold mb-md uppercase tracking-wider text-secondary border-b border-white/10 pb-sm">Monitor de Rifa</h3>
             <div class="grid grid-cols-2 gap-sm">
               <div class="bg-black/30 p-sm rounded-lg text-center border border-white/5">
                 <div class="text-xs text-secondary uppercase tracking-widest mb-xs">Libres</div>
                 <div class="font-display text-2xl">{{ totalAvailable }}</div>
               </div>
               <div class="bg-amber-500/10 p-sm rounded-lg text-center border border-amber-500/30 text-amber-500">
                 <div class="text-xs uppercase tracking-widest mb-xs">Reservas</div>
                 <div class="font-display text-2xl">{{ totalReserved }}</div>
               </div>
               <div class="bg-blue-500/10 p-sm rounded-lg text-center border border-blue-500/30 text-blue-400">
                 <div class="text-xs uppercase tracking-widest mb-xs">Vendidos</div>
                 <div class="font-display text-2xl">{{ totalPaid }}</div>
               </div>
               <div class="bg-green-500/10 p-sm rounded-lg text-center border border-green-500/30 text-green-400">
                 <div class="text-xs uppercase tracking-widest mb-xs">Ganadores</div>
                 <div class="font-display text-2xl">{{ totalWinners }}</div>
               </div>
             </div>
             
             <div class="mt-md bg-accent-green/10 p-md rounded-lg border border-accent-green/30 text-center text-accent-green">
               <div class="text-xs uppercase tracking-widest mb-xs">Recaudado Estimado</div>
               <div class="font-display text-3xl font-bold">${{ formatAmount((totalPaid + totalWinners) * raffle.ticket_price) }}</div>
             </div>
          </div>
          
          <div class="card p-md">
            <h3 class="text-lg font-bold mb-md uppercase tracking-wider text-secondary border-b border-white/10 pb-sm">Premios Definidos</h3>
            <div class="flex flex-col gap-sm">
              <div v-for="prize in raffle.prizes" :key="prize.id" class="flex gap-sm p-sm bg-white/5 rounded-md border border-white/10">
                <span class="font-display text-gx-red text-xl leading-none">{{ prize.position }}°</span>
                <span class="text-sm leading-tight">{{ prize.title }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  raffle: Object,
  mapped_numbers: Array,
});

const { ask } = useConfirm();
const { warning: toastWarning } = useToast();

const numbersData = computed(() => props.raffle?.mapped_numbers || []);
const winners = computed(() => numbersData.value.filter(n => n.status === 'winner'));

const totalAvailable = computed(() => numbersData.value.filter((n) => n.status === 'available').length);
const totalReserved = computed(() => numbersData.value.filter((n) => n.status === 'reserved').length);
const totalPaid = computed(() => numbersData.value.filter((n) => n.status === 'sold').length);
const totalWinners = computed(() => winners.value.length);

const formatAmount = (val) => new Intl.NumberFormat('es-CL').format(val);

const getPrizeTitle = (pos) => {
  const p = props.raffle.prizes?.find(pr => pr.position === pos);
  return p ? p.title : `${pos}° Premio`;
};

// Forms
const assignForm = useForm({
  buyer_name: '',
  blader_name: '',
  phone: '',
  email: '',
  mark_as_paid: false,
  numbers: [],
  proof: null
});

// Convert user string "1, 2, 5-8" to array
import { ref } from 'vue';
const assignNumbersStr = ref('');
const showProofModal = ref(false);
const currentProof = ref('');

const openProofModal = (url) => {
  currentProof.value = url;
  showProofModal.value = true;
};

const parseNumbersList = (text) => {
    let result = [];
    const parts = text.replace(/;/g, ',').replace(/\|/g, ',').split(/,|\s+/).filter(Boolean);
    
    parts.forEach(part => {
        if (part.includes('-')) {
            let [start, end] = part.split('-').map(n => parseInt(n.trim(), 10));
            if (!isNaN(start) && !isNaN(end)) {
                if (start > end) [start, end] = [end, start];
                for (let i = start; i <= end; i++) result.push(i);
            }
        } else {
            const n = parseInt(part, 10);
            if (!isNaN(n)) result.push(n);
        }
    });
    return [...new Set(result)].sort((a, b) => a - b);
};

const submitAssign = () => {
    assignForm.numbers = parseNumbersList(assignNumbersStr.value);
    if(assignForm.numbers.length === 0) {
        toastWarning("Ingresa números válidos en el rango.");
        return;
    }
    assignForm.post(route('admin.raffles.assign', props.raffle.id), {
        onSuccess: () => {
            assignNumbersStr.value = '';
            assignForm.reset();
        }
    });
};

const winnerForm = useForm({
    winner_number: '',
    prize_position: '',
    winner_photo: null
});

const submitWinner = () => {
    winnerForm.post(route('admin.raffles.numbers.winner', { raffle: props.raffle.id, number: winnerForm.winner_number }), {
        forceFormData: true,
        onSuccess: () => winnerForm.reset()
    });
};

const clearSingle = async (num) => {
    if(await ask({
        title: 'Liberar Número',
        message: `¿Estás seguro de que deseas liberar el número #${num}? Se borrarán los datos del comprador permanentemente.`,
        confirmText: 'Sí, liberar',
        tone: 'danger'
    })) {
        router.post(route('admin.raffles.numbers.clear', { raffle: props.raffle.id, number: num }), {}, { preserveScroll: true });
    }
};

const clearAll = async () => {
    if(await ask({
        title: 'ATENCIÓN: Liberar Todos',
        message: '¿Estás completamente seguro de liberar TODOS los números de la rifa? Esta acción destruirá las reservas y ventas.',
        confirmText: 'SÍ, BORRAR TODO',
        tone: 'danger'
    })) {
        router.post(route('admin.raffles.numbers.clear-all', props.raffle.id), {}, { preserveScroll: true });
    }
};

const uploadManualProof = (num, event) => {
    const file = event.target.proof?.files[0] || event.target.querySelector('input[type="file"]')?.files[0];
    if(!file) return;
    
    const form = new FormData();
    form.append('proof', file);
    router.post(route('admin.raffles.numbers.proof', { raffle: props.raffle.id, number: num }), form, {
        preserveScroll: true
    });
};

const uploadWinnerPhoto = (num, event) => {
    const file = event.target.winner_photo?.files[0];
    if(!file) return;
    
    const form = new FormData();
    form.append('winner_photo', file);
    router.post(route('admin.raffles.numbers.winner-photo', { raffle: props.raffle.id, number: num }), form, {
        preserveScroll: true
    });
};

</script>

<style scoped>
.grid-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: var(--space-lg);
  align-items: start;
}

@media (max-width: 1000px) {
  .grid-layout { grid-template-columns: 1fr; }
}

/* Replicando estilos de admin_numeros.php */
.numbers-grid-admin {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 12px;
}

.number-admin-badge {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    overflow: hidden;
    transition: all 0.2s ease;
}

.number-admin-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.status-available { border-color: rgba(255,255,255,0.05); }
.status-sold { border-color: var(--gx-blue); background: rgba(59, 130, 246, 0.05); }
.status-reserved { border-color: var(--gx-amber); background: rgba(245, 158, 11, 0.05); }
.status-winner { border-color: var(--gx-success); background: rgba(74, 222, 128, 0.1); }

.number-badge-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.3);
    margin-bottom: 12px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
    border: 1px solid rgba(255,255,255,0.05);
}

.status-sold .number-badge-circle { border-color: var(--gx-blue); color: var(--gx-blue); box-shadow: 0 0 15px rgba(59, 130, 246, 0.2); }
.status-reserved .number-badge-circle { border-color: var(--gx-amber); color: var(--gx-amber); }
.status-winner .number-badge-circle { border-color: var(--gx-success); color: var(--gx-success); box-shadow: 0 0 20px rgba(74, 222, 128, 0.3); }

.number-badge-num {
    font-size: 1.5rem;
    font-weight: 800;
    font-family: var(--font-display);
    line-height: 1;
}

.number-badge-body {
    width: 100%;
    text-align: center;
    z-index: 1;
    display: flex;
    flex-direction: column;
}

.number-badge-label {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: var(--text-secondary);
}

.status-sold .number-badge-label { color: var(--gx-blue); }
.status-reserved .number-badge-label { color: var(--gx-amber); }
.status-winner .number-badge-label { color: var(--gx-success); }
</style>


