<template>
  <AppLayout>
    <Head :title="`Registro - ${event.name}`" />

    <div class="page-content py-xl prereg-page">
      <div class="max-w-4xl mx-auto">
        <div class="text-center mb-xl">
          <h1 class="page-title text-gradient">{{ event.name }}</h1>
          <p class="text-secondary">{{ event.season?.name }} | {{ new Date(event.event_date).toLocaleDateString('es-CL') }}</p>
        </div>

        <div class="steps-progress mb-xl">
          <div class="progress-line" :style="{ width: ((currentStep - 1) / 4) * 100 + '%' }"></div>
          <div v-for="step in 5" :key="step" class="step-node" :class="{ active: currentStep >= step, current: currentStep === step, 'step-hidden': isRex && step === 4 }">
            <span class="step-icon">{{ step }}</span>
            <span class="step-label">{{ step === 4 && isRex ? '---' : stepTitles[step - 1] }}</span>
          </div>
        </div>

        <div class="card prereg-card">
          <form @submit.prevent="submit" class="space-y-lg">
            <section v-if="currentStep === 1" class="stagger">
              <div class="flex justify-between items-center mb-md">
                <h3 class="section-title m-0">Información del Evento</h3>
                <span v-if="isRegistered" class="badge badge-green flex items-center gap-xs">
                  ✅ YA ESTÁS REGISTRADO
                </span>
              </div>
              
              <div v-if="isRegistered" class="message-box info mb-md border-green-500/50 bg-green-500/10 text-green-400">
                Ya hemos recibido tu inscripción para este torneo. No es necesario registrarse de nuevo.
              </div>
              <div class="event-box">
                <div class="event-grid">
                  <div>
                    <span class="k">Nombre</span>
                    <p>{{ event.name }}</p>
                  </div>
                  <div>
                    <span class="k">Fecha</span>
                    <p>{{ new Date(event.event_date).toLocaleDateString('es-CL') }}</p>
                  </div>
                  <div v-if="event.location">
                    <span class="k">Ubicación</span>
                    <p>{{ event.location }}</p>
                  </div>
                  <div v-if="event.time">
                    <span class="k">Hora</span>
                    <p>{{ event.time }}</p>
                  </div>
                  <div v-if="event.prizes" class="event-block col-span-2">
                    <span class="k text-amber-500">Premios</span>
                    <p class="font-bold">{{ event.prizes }}</p>
                  </div>
                  <div class="event-block col-span-2">
                    <span class="k text-green-500">Costo Inscripción</span>
                    <p class="text-xl font-black">{{ new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(event.registration_cost) }}</p>
                  </div>
                </div>
                <div v-if="event.description" class="event-block">
                  <span class="k">Descripción</span>
                  <p>{{ event.description }}</p>
                </div>
                <div v-if="event.rules" class="event-block">
                  <span class="k">Reglas</span>
                  <p>{{ event.rules }}</p>
                </div>
              </div>
            </section>

            <!-- STEP 2: REX CHECK -->
            <section v-if="currentStep === 2" class="stagger">
              <h3 class="section-title">¿Tienes cuenta en R.E.X?</h3>
              <p class="text-secondary mb-md italic text-sm">
                REX es nuestra plataforma oficial de gestión de batallas y bladers. 
                <a href="https://royal-evolution-x.cl/dashboard" target="_blank" class="text-gx-red font-bold hover:underline ml-1">Ir a R.E.X →</a>
              </p>
              
              <div class="rex-options mb-lg">
                <button type="button" class="rex-option" :class="{ active: form.is_rex_registered === true }" @click="form.is_rex_registered = true">
                  <div class="text-xl mb-xs">🛡️</div>
                  Sí, ya tengo cuenta R.E.X
                </button>
                <button type="button" class="rex-option" :class="{ active: form.is_rex_registered === false }" @click="form.is_rex_registered = false">
                  <div class="text-xl mb-xs">🌱</div>
                  No, soy nuevo / No tengo
                </button>
              </div>

              <div v-if="form.is_rex_registered === true" class="message-box info border-blue-500/30">
                <p><strong>IMPORTANTE:</strong> Si marcas que tienes cuenta y no la tienes, <strong>no podremos validar tu inscripción</strong> y deberás comunicarte al Instagram de <a href="https://instagram.com/guerrilla_xtrem" target="_blank" class="underline">@guerrilla_xtrem</a>.</p>
              </div>

              <div v-if="form.is_rex_registered === false" class="message-box warn border-amber-500/30">
                <p>No te preocupes. Se creará automáticamente un perfil básico para ti en nuestra liga para gestionar este torneo.</p>
              </div>
              
              <span v-if="form.errors.is_rex_registered" class="form-error">{{ form.errors.is_rex_registered }}</span>
            </section>

            <!-- STEP 3: PERSONAL DATA (DYNAMIC) -->
            <section v-if="currentStep === 3" class="stagger">
              <h3 class="section-title">Datos del Blader</h3>
              
              <div v-if="form.is_rex_registered" class="form-group">
                <label class="form-label">Nombre de Blader en R.E.X *</label>
                <div class="relative">
                  <input type="text" v-model="form.blader_name" class="form-input pl-10" placeholder="Ej: GingaHagane" required />
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-lg">👤</span>
                </div>
                <p class="text-xs text-secondary mt-xs">Asegúrate de que sea exactamente igual a tu nombre en R.E.X</p>
                <span v-if="form.errors.blader_name" class="form-error">{{ form.errors.blader_name }}</span>
              </div>

              <div v-else class="space-y-md">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                  <div class="form-group">
                    <label class="form-label">Nombre Real *</label>
                    <input type="text" v-model="form.first_name" class="form-input" required />
                    <span v-if="form.errors.first_name" class="form-error">{{ form.errors.first_name }}</span>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Apellido Real *</label>
                    <input type="text" v-model="form.last_name" class="form-input" required />
                    <span v-if="form.errors.last_name" class="form-error">{{ form.errors.last_name }}</span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Nombre de Blader deseado *</label>
                  <input type="text" v-model="form.blader_name" class="form-input" required />
                  <span v-if="form.errors.blader_name" class="form-error">{{ form.errors.blader_name }}</span>
                </div>
              </div>
            </section>

            <!-- STEP 4: CONTACT (ONLY IF NO REX) -->
            <section v-if="currentStep === 4 && !form.is_rex_registered" class="stagger">
              <h3 class="section-title">Datos de Contacto</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div class="form-group">
                  <label class="form-label">Fecha de nacimiento *</label>
                  <input type="date" v-model="form.birth_date" class="form-input" required />
                  <span v-if="form.errors.birth_date" class="form-error">{{ form.errors.birth_date }}</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Correo electrónico *</label>
                  <input type="email" v-model="form.email" class="form-input" required />
                  <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Teléfono / WhatsApp *</label>
                <input type="text" v-model="form.whatsapp" @input="validatePhone" class="form-input" placeholder="Solo números" required />
                <p class="text-xs text-secondary mt-xs">Lo usaremos para coordinar el torneo.</p>
                <span v-if="form.errors.whatsapp" class="form-error">{{ form.errors.whatsapp }}</span>
              </div>
            </section>


            <section v-if="currentStep === 5" class="stagger">
              <div class="mb-xl text-center">
                <div class="w-16 h-16 bg-accent-green/10 text-accent-green rounded-full flex items-center justify-center mx-auto mb-md">💳</div>
                <h3 class="section-title m-0">¿Deseas pagar ahora?</h3>
                <p class="text-secondary mt-sm">Puedes asegurar tu cupo de inmediato o pagar días antes del evento.</p>
              </div>

              <div class="rex-options mb-xl">
                <button type="button" class="rex-option" :class="{ active: form.payment_option === 'now' }" @click="form.payment_option = 'now'">
                  <div class="text-xl mb-xs">⚡</div>
                  Pagar ahora
                </button>
                <button type="button" class="rex-option" :class="{ active: form.payment_option === 'later' }" @click="form.payment_option = 'later'">
                  <div class="text-xl mb-xs">📅</div>
                  Pagar días antes
                </button>
              </div>

              <div v-if="form.payment_option === 'now'" class="stagger">
                <div class="message-box info border-accent-green/30 bg-accent-green/5 text-accent-green mb-lg">
                  <p>✅ Al momento de subir el comprobante, validaremos tu pago y tu inscripción estará <strong>asegurada</strong>.</p>
                </div>

                <div class="payment-card mb-xl">
                  <div class="payment-row">
                    <span class="payment-label">Banco</span>
                    <p class="payment-value">{{ event.bank_name || 'Banco Estado' }}</p>
                  </div>
                  <div class="payment-row">
                    <span class="payment-label">Titular</span>
                    <p class="payment-value">{{ event.account_holder || 'Daniel Orellana' }}</p>
                  </div>
                  <div class="payment-row">
                    <span class="payment-label">Cuenta</span>
                    <p class="payment-value">{{ event.account_number || '20539169' }}</p>
                  </div>
                  <div class="payment-row">
                    <span class="payment-label">Email</span>
                    <p class="payment-value">{{ event.account_email || 'ventas@gxbeyblade.com' }}</p>
                  </div>
                  <div v-if="event.payment_instructions" class="payment-row col-span-2">
                    <span class="payment-label">Instrucciones</span>
                    <p class="payment-value italic">{{ event.payment_instructions }}</p>
                  </div>
                </div>

                <div class="proof-upload p-xl text-center border-2 border-dashed border-white/10 rounded-xl bg-white/5">
                  <input ref="proofInput" type="file" @change="onProofSelected" accept="image/*" class="sr-only" id="proof_file" />
                  
                  <div v-if="!form.proof" class="space-y-sm">
                    <div class="text-3xl">📄</div>
                    <h3 class="font-bold">Sube tu comprobante *</h3>
                    <p class="text-xs text-secondary">Imagen JPG, PNG o PDF (Máx 5MB)</p>
                    <button class="btn btn-secondary btn-sm mt-md" type="button" @click="triggerProofPicker">Seleccionar archivo</button>
                  </div>

                  <div v-else class="proof-selected">
                    <div class="flex items-center justify-center gap-sm">
                      <span class="text-2xl">📸</span>
                      <div class="text-left">
                        <p class="text-sm font-bold truncate max-w-[200px]">{{ form.proof.name }}</p>
                        <p class="text-xs text-secondary">{{ (form.proof.size / 1024 / 1024).toFixed(2) }} MB</p>
                      </div>
                    </div>
                    <button type="button" @click="removeProof" class="text-gx-red text-sm font-bold mt-sm block mx-auto">Eliminar comprobante</button>
                  </div>
                  <span v-if="form.errors.proof" class="form-error block mt-sm">{{ form.errors.proof }}</span>
                </div>
              </div>

              <div v-if="form.payment_option === 'later'" class="message-box warn border-gx-red/30 bg-gx-red/5 text-gx-red-light mb-xl">
                <p>⚠️ <strong>Atención:</strong> Si 24 horas antes del evento no se ha recibido el pago, tu inscripción será <strong>cancelada</strong> sin previo aviso.</p>
              </div>
            </section>

            <div class="actions-row">
              <button type="button" class="btn btn-secondary" @click="prevStep" :disabled="currentStep === 1">← Anterior</button>
              <button v-if="currentStep < 5" type="button" class="btn btn-primary" @click="nextStep" :disabled="currentStep === 1 && isRegistered">Siguiente →</button>
              <button v-else type="submit" class="btn btn-primary" :disabled="form.processing">
                {{ form.processing ? 'Enviando...' : (form.payment_option === 'later' ? 'Completar pre-registro' : 'Confirmar registro y pago') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import AppLayout from '@/Layouts/AppLayout.vue';

const { props: pageProps } = usePage();
const { warning: toastWarning, success: toastSuccess, error: toastError } = useToast();

const props = defineProps({
  event: Object,
  isRegistered: Boolean,
});

const stepTitles = ['Evento', 'R.E.X?', 'Blader', 'Contacto', 'Pago'];
const currentStep = ref(1);
const isRex = computed(() => form.is_rex_registered === true);
const proofInput = ref(null);

const form = useForm({
  first_name: '',
  last_name: '',
  blader_name: '',
  birth_date: '',
  whatsapp: '',
  email: '',
  is_rex_registered: null,
  payment_option: 'now',
  proof: null,
});

const validatePhone = (e) => {
  form.whatsapp = e.target.value.replace(/\D/g, '');
};

onMounted(() => {
  const user = pageProps?.auth?.user;
  if (user) {
    const names = user.name ? user.name.split(' ') : ['', ''];
    form.first_name = names[0] || '';
    form.last_name = names.slice(1).join(' ') || '';
    form.blader_name = user.blader_name || '';
    form.email = user.email || '';
  }
});

const validateStep = () => {
  if (currentStep.value === 2 && form.is_rex_registered === null) {
    toastWarning('Selecciona si tienes cuenta en R.E.X para continuar.');
    return false;
  }

  if (currentStep.value === 3) {
    if (form.is_rex_registered && !form.blader_name) {
      toastWarning('Ingresa tu nombre de Blader en R.E.X.');
      return false;
    }
    if (!form.is_rex_registered && (!form.first_name || !form.last_name || !form.blader_name)) {
      toastWarning('Completa todos tus datos de blader.');
      return false;
    }
  }

  if (currentStep.value === 4 && !form.is_rex_registered && (!form.birth_date || !form.email || !form.whatsapp)) {
    toastWarning('Completa los datos de contacto para continuar.');
    return false;
  }

  return true;
};

const triggerProofPicker = () => proofInput.value?.click();
const onProofSelected = (e) => {
  const file = e.target.files[0];
  if (file) form.proof = file;
};
const removeProof = () => {
  form.proof = null;
  if (proofInput.value) proofInput.value.value = '';
};

const nextStep = () => {
  if (currentStep.value === 1 && props.isRegistered) {
    toastWarning('Ya te encuentras registrado en este torneo.');
    return;
  }
  if (!validateStep()) return;
  
  if (currentStep.value === 3 && form.is_rex_registered) {
    currentStep.value = 5; // Jump to payment
  } else if (currentStep.value < 5) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value === 5 && form.is_rex_registered) {
    currentStep.value = 3;
  } else if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const submit = () => {
  if (form.processing) return;

  if (form.is_rex_registered === null) {
    toastWarning('Selecciona si estás registrado en REX.');
    return false;
  }

  if (form.payment_option === 'now' && !form.proof) {
    toastWarning('Debes subir el comprobante de pago para finalizar.');
    return;
  }

  form
    .transform((data) => ({
      ...data,
      is_rex_registered: data.is_rex_registered ? 1 : 0,
    }))
    .post(route('tournaments.register.store', props.event.id), {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        toastSuccess('Pre-registro confirmado correctamente.');
        currentStep.value = 1;
        form.reset();
      },
      onError: (errors) => {
        const firstError = Object.values(errors || {})[0];
        toastError(firstError || 'No se pudo completar el pre-registro.');
      },
    });
};
</script>

<style scoped>
.prereg-page {
  background: radial-gradient(circle at top, rgba(225, 6, 0, 0.07), transparent 45%);
}

.steps-progress {
  position: relative;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: var(--space-xs);
}

.progress-line {
  position: absolute;
  top: 19px;
  left: 20px;
  height: 3px;
  background: var(--gx-red);
  transition: width 0.35s ease;
  z-index: 1;
}

.step-node {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

.step-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  border: 2px solid var(--border-color);
  background: var(--bg-card);
  font-weight: 800;
}

.step-node.active .step-icon {
  border-color: var(--gx-red);
}

.step-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  color: var(--text-secondary);
  letter-spacing: 0.05em;
}

.prereg-card {
  padding: var(--space-xl);
}

.section-title {
  margin-bottom: var(--space-md);
}

.event-box {
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  display: grid;
  gap: var(--space-md);
  background: rgba(0, 0, 0, 0.2);
}

.event-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-sm);
}

.event-block {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: var(--space-sm);
}

.k {
  display: block;
  font-size: 0.68rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.rex-options {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-sm);
}

.rex-option {
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  text-align: center;
  font-weight: 700;
  background: rgba(0, 0, 0, 0.2);
  cursor: pointer;
}

.rex-option.active {
  border-color: var(--gx-red);
  background: rgba(225, 6, 0, 0.1);
}

.step-hidden {
  opacity: 0.3;
  pointer-events: none;
}

.message-box {
  border-radius: var(--radius-md);
  padding: var(--space-md);
  font-size: 0.9rem;
}

.message-box.info {
  background: rgba(59, 130, 246, 0.15);
  border: 1px solid rgba(59, 130, 246, 0.4);
}

.message-box.warn {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.4);
}

.rex-options button {
  color: white !important;
}

.actions-row button {
  color: white !important;
}

.actions-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-sm);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: var(--space-md);
}

.payment-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-sm);
}

.payment-row {
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding-bottom: 6px;
}

.payment-row:last-child {
  border-bottom: none;
}

.payment-label {
  display: block;
  font-size: 0.65rem;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 0.05em;
}

.payment-value {
  font-weight: 600;
  font-size: 0.9rem;
}

@media (max-width: 700px) {
  .event-grid,
  .rex-options,
  .payment-card {
    grid-template-columns: 1fr;
  }

  .actions-row {
    flex-direction: column;
  }

  .actions-row .btn {
    width: 100%;
  }
}
</style>


