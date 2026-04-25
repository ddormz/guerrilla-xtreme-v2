<template>
  <AppLayout>
    <Head title="Gestionar Liga" />

    <div class="admin-league page-content">
      <nav class="mb-lg">
        <Link :href="route('admin.dashboard')" class="btn btn-ghost btn-sm">← Dashboard</Link>
      </nav>

      <div class="page-header mb-xl">
        <h1 class="text-gradient">Gestión de Liga y Torneos</h1>
        <div class="actions-group">
          <Link :href="route('admin.recovery.index')" class="btn btn-outline">Recuperaciones</Link>
          <button @click="openModal('season')" class="btn btn-ghost" type="button">+ Temporada</button>
          <button @click="openModal('event')" class="btn btn-primary" type="button">+ Evento</button>
        </div>
      </div>

      <div class="admin-grid-layout">
        <section class="stagger">
          <h2 class="mb-lg">Temporadas</h2>
          <div class="card p-0 overflow-hidden">
            <table class="gx-table">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th class="text-center">Estado</th>
                  <th class="text-right">Inicio</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in seasons" :key="s.id" class="table-row">
                  <td class="font-bold">{{ s.name }}</td>
                  <td class="text-center"><span class="badge" :class="s.status === 'en_curso' ? 'badge-green' : 'badge-secondary'">{{ s.status }}</span></td>
                  <td class="text-right text-muted">{{ s.start_date }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="stagger">
          <h2 class="mb-lg">Últimos Eventos</h2>
          <div class="card p-0 overflow-hidden">
            <table class="gx-table">
              <thead>
                <tr>
                  <th>Evento</th>
                  <th class="text-center">Tipo</th>
                  <th class="text-right">Fecha</th>
                  <th class="text-right">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="e in events" :key="e.id" class="table-row">
                  <td>
                    <strong>{{ e.name }}</strong>
                    <span v-if="e.season?.name" class="text-xs text-muted block">{{ e.season.name }}</span>
                  </td>
                  <td class="text-center">
                    <span class="badge" :class="eventBadgeClass(e.event_type)">{{ eventTypeLabel(e.event_type) }}</span>
                  </td>
                  <td class="text-right text-sm">{{ formatDateTime(e.event_date) }}</td>
                  <td class="text-right">
                    <div class="flex gap-sm justify-end items-center">
                      <Link :href="route('admin.events.show', e.id)" class="btn btn-primary btn-xs">Gestionar</Link>
                      <button @click="editEvent(e)" class="btn btn-outline btn-xs px-2" title="Editar" type="button">✎</button>
                      <button @click="deleteEvent(e.id)" class="btn btn-danger btn-xs px-2" title="Eliminar" type="button">🗑️</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="full-width stagger">
          <h2 class="mb-lg">Pagos Pendientes de Liga</h2>
          <div class="card p-0 overflow-hidden">
            <div class="p-md border-b border-white/10 flex items-center justify-between gap-sm flex-wrap">
              <p class="m-0 text-sm text-secondary">Bladers presentes con pago pendiente por fecha de liga.</p>
              <span class="badge badge-amber">
                Total pendiente: ${{ formatAmount(totalPendingPayments) }}
              </span>
            </div>

            <table v-if="pendingLeaguePayments.length" class="gx-table">
              <thead>
                <tr>
                  <th>Evento</th>
                  <th>Fecha</th>
                  <th>Blader</th>
                  <th class="text-right">Monto</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pending in pendingLeaguePayments" :key="`${pending.event_id}-${pending.player_id}`" class="table-row">
                  <td>
                    <Link :href="route('admin.events.show', pending.event_id)" class="font-bold hover:underline">
                      {{ pending.event_name }}
                    </Link>
                  </td>
                  <td class="text-sm text-secondary">{{ formatDateTime(pending.event_date) }}</td>
                  <td class="font-bold">{{ pending.blader_name }}</td>
                  <td class="text-right text-amber-400 font-black">${{ formatAmount(pending.pending_amount) }}</td>
                </tr>
              </tbody>
            </table>
            <div v-else class="p-lg text-center text-secondary italic">
              No hay pagos pendientes de liga.
            </div>
          </div>
        </section>

        <section class="full-width stagger mt-xl">
          <h2 class="mb-lg">Roster Oficial de la Liga</h2>
          <div class="card mb-lg">
            <div class="flex justify-between items-center mb-md flex-wrap gap-sm">
              <p class="text-secondary text-sm m-0">Jugadores oficiales con acceso a perfil y estadísticas.</p>
              <button @click="addAllGxMembers" class="btn btn-outline btn-sm" type="button">Añadir todos los Miembros GX</button>
            </div>
            <form @submit.prevent="submitPlayer" class="roster-inline-form">
              <div class="form-group flex-1 m-0">
                <select v-model="playerForm.user_id" class="form-input" required>
                  <option value="">Seleccionar Usuario...</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">{{ u.blader_name || u.name }}</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary" :disabled="playerForm.processing">Añadir seleccionado</button>
            </form>
          </div>

          <div class="roster-cards">
            <article v-for="p in players" :key="p.id" class="card player-card" :class="{ inactive: p.active === false }">
              <div class="player-head">
                <div class="user-avatar-small">
                  <img v-if="p.avatar_path || p.user?.avatar_path" :src="`/storage/${p.avatar_path || p.user?.avatar_path}`" class="avatar-img" @error="handleImageError">
                  <span v-else>{{ (p.blader_name || p.user?.name || '?').charAt(0) }}</span>
                </div>
                <div>
                  <h3 class="player-name">{{ p.blader_name || p.user?.name }}</h3>
                  <p class="text-secondary text-xs">{{ p.user?.name }}</p>
                </div>
                <span class="badge ml-auto" :class="p.active === false ? 'badge-red' : 'badge-green'">
                  {{ p.active === false ? 'Inactivo' : 'Activo' }}
                </span>
              </div>
              <div class="player-stats-grid">
                <div><span class="k">Ranking</span><strong>{{ leagueRank(p.id) }}</strong></div>
                <div><span class="k">Ganadas</span><strong class="green">{{ p.total_wins || 0 }}</strong></div>
                <div><span class="k">Perdidas</span><strong class="red">{{ p.total_losses || 0 }}</strong></div>
                <div><span class="k">Xtremes</span><strong class="gold">{{ p.total_xtremes || 0 }}</strong></div>
              </div>
              <div class="player-actions">
                <Link :href="route('league.players.show', p.id)" class="btn btn-outline btn-xs">Perfil</Link>
                <button @click="editPlayer(p)" class="btn btn-ghost btn-xs" type="button">Editar</button>
                <button @click="deletePlayer(p.id)" class="btn btn-danger btn-xs" type="button">Eliminar</button>
              </div>
            </article>
          </div>
        </section>
      </div>

      <div v-if="activeModal === 'season'" class="modal-overlay">
        <div class="modal-content card">
          <h3>Nueva Temporada</h3>
          <form @submit.prevent="submitSeason" class="mt-lg">
            <div class="form-group">
              <label class="form-label">Nombre</label>
              <input type="text" v-model="seasonForm.name" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">Fecha Inicio</label>
              <input type="date" v-model="seasonForm.start_date" class="form-input" required />
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-md">Guardar</button>
            <button @click="activeModal = null" type="button" class="btn btn-ghost btn-block mt-sm">Cancelar</button>
          </form>
        </div>
      </div>

      <div v-if="activeModal === 'event'" class="modal-overlay">
        <div class="modal-content card">
          <h3>{{ editingEvent ? 'Editar' : 'Nuevo' }} Evento</h3>
          <form @submit.prevent="submitEvent" class="mt-lg">
            <div v-if="eventForm.event_type === 'torneo'" class="form-group mb-md font-bold">
              <label class="form-label">Reglas del Evento <span class="badge badge-amber badge-xs ml-auto">IMPORTANTE</span></label>
              <textarea v-model="eventForm.rules" class="form-input" rows="3" placeholder="Ingresa las bases y restricciones del torneo..."></textarea>
            </div>

            <div class="form-group mb-md">
              <label class="form-label">Nombre del Evento</label>
              <input type="text" v-model="eventForm.name" class="form-input" required />
            </div>
            
            <div class="grid grid-cols-2 gap-md mb-md">
              <div class="form-group">
                <label class="form-label">Fecha y Hora</label>
                <input type="datetime-local" v-model="eventForm.event_date" class="form-input" required />
              </div>
              <div class="form-group">
                <label class="form-label">Tipo</label>
                <select v-model="eventForm.event_type" class="form-input" required>
                  <option value="liga">Liga</option>
                  <option value="torneo">Torneo (Eliminación)</option>
                  <option value="torneo_ranking">Torneo + Ranking</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-md mb-md">
              <div class="form-group">
                <label class="form-label">Precio Inscripción (CLP)</label>
                <input type="number" v-model="eventForm.registration_cost" class="form-input" required />
              </div>
              <div class="form-group">
                <label class="form-label">Lugar</label>
                <input type="text" v-model="eventForm.location" class="form-input" required />
              </div>
            </div>

            <div v-if="eventForm.event_type === 'torneo'" class="form-group mb-md">
              <label class="form-label flex justify-between items-center">
                Premios
                <button type="button" @click="addPrizeRow" class="text-accent-green text-[10px] font-black uppercase hover:underline">+ Añadir Premio</button>
              </label>
              <div class="space-y-xs">
                <div v-for="(prize, index) in eventForm.prizes_list" :key="index" class="flex gap-xs">
                  <input type="text" v-model="eventForm.prizes_list[index]" class="form-input form-input-sm" placeholder="Ej: 1er Lugar - Trofeo" />
                  <button type="button" @click="removePrizeRow(index)" class="btn btn-ghost btn-xs text-gx-red">×</button>
                </div>
              </div>
            </div>

            <div v-if="eventForm.event_type === 'torneo'" class="divider mb-md">Datos Bancarios (Para Torneos)</div>

            <div v-if="eventForm.event_type === 'torneo'" class="grid grid-cols-2 gap-md mb-md">
              <div class="form-group">
                <label class="form-label">Banco</label>
                <input type="text" v-model="eventForm.bank_name" class="form-input" placeholder="Banco Estado" />
              </div>
              <div class="form-group">
                <label class="form-label">Titular</label>
                <input type="text" v-model="eventForm.account_holder" class="form-input" placeholder="Nombre completo" />
              </div>
            </div>

            <div v-if="eventForm.event_type === 'torneo'" class="grid grid-cols-2 gap-md mb-md">
              <div class="form-group">
                <label class="form-label">Tipo de Cuenta</label>
                <input type="text" v-model="eventForm.account_type" class="form-input" placeholder="Corriente / Vista" />
              </div>
              <div class="form-group">
                <label class="form-label">N° Cuenta</label>
                <input type="text" v-model="eventForm.account_number" class="form-input" />
              </div>
            </div>

            <div v-if="eventForm.event_type === 'torneo'" class="form-group mb-md">
              <label class="form-label">Email de Pago</label>
              <input type="email" v-model="eventForm.account_email" class="form-input" />
            </div>

            <div v-if="eventForm.event_type === 'torneo'" class="form-group mb-md">
              <label class="form-label">Instrucciones de Pago</label>
              <textarea v-model="eventForm.payment_instructions" class="form-input" rows="2" placeholder="Ej: Enviar comprobante al Instagram..."></textarea>
            </div>

            <div class="form-group mb-lg">
              <label class="flex items-center gap-sm cursor-pointer">
                <input type="checkbox" v-model="eventForm.show_on_index" />
                <span>Mostrar en Home</span>
              </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
              {{ editingEvent ? 'Actualizar Evento' : 'Crear Evento' }}
            </button>
            <button @click="activeModal = null" type="button" class="btn btn-ghost btn-block mt-sm">Cancelar</button>
          </form>
        </div>
      </div>

      <div v-if="activeModal === 'player'" class="modal-overlay">
        <div class="modal-content card max-w-lg">
          <h3>Editar Blader: {{ editingPlayer?.blader_name }}</h3>
          <form @submit.prevent="submitPlayerEdit" class="mt-lg">
            <div class="flex flex-col items-center mb-lg">
              <div class="preview-circle mb-md">
                <img v-if="playerAvatarPreview" :src="playerAvatarPreview" class="preview-image" @error="handleImageError">
                <span v-else>👤</span>
              </div>
              <label class="btn btn-outline btn-sm">
                Cambiar Avatar
                <input type="file" @change="handlePlayerAvatarChange" accept="image/*" class="sr-only">
              </label>
            </div>

            <div class="form-group">
              <label class="form-label">Blader Name</label>
              <input type="text" v-model="playerEditForm.blader_name" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">Nombre Real</label>
              <input type="text" v-model="playerEditForm.real_name" class="form-input" />
            </div>
            <div class="form-group">
              <label class="flex items-center gap-sm cursor-pointer">
                <input type="checkbox" v-model="playerEditForm.active" />
                <span>Estado Activo</span>
              </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-md" :disabled="playerEditForm.processing">Guardar Cambios</button>
            <button @click="activeModal = null" type="button" class="btn btn-ghost btn-block mt-sm">Cancelar</button>
          </form>
        </div>
      </div>
    </div>

    <ImageCropper
      v-if="cropperData.show"
      :show="cropperData.show"
      :image-src="cropperData.src"
      :aspect-ratio="1"
      @close="cropperData.show = false"
      @cropped="handleCroppedAvatar"
    />
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import ImageCropper from '@/Components/ImageCropper.vue';

const props = defineProps({
  seasons: Array,
  events: Array,
  users: Array,
  players: Array,
  pendingLeaguePayments: {
    type: Array,
    default: () => [],
  },
});

const { ask } = useConfirm();

const activeModal = ref(null);
const editingEvent = ref(null);
const editingPlayer = ref(null);
const playerAvatarPreview = ref(null);

const cropperData = ref({
  show: false,
  src: '',
});

const seasonForm = useForm({ name: '', start_date: '' });
const eventForm = useForm({
  season_id: props.seasons[0]?.id || '',
  name: '',
  event_date: '',
  event_type: 'liga',
  description: '',
  rules: '',
  location: '',
  time: '',
  prizes: '',
  registration_cost: 0,
  bank_name: '',
  account_type: '',
  account_holder: '',
  account_number: '',
  account_email: '',
  payment_instructions: '',
  prizes_list: [''], // For dynamic UI
  show_on_index: false,
  _method: 'POST',
});
const playerForm = useForm({ user_id: '' });
const playerEditForm = useForm({
  blader_name: '',
  real_name: '',
  active: true,
  avatar: null,
  _method: 'PUT'
});

const sortedPlayers = computed(() => [...props.players].sort((a, b) => (b.total_points || 0) - (a.total_points || 0)));
const totalPendingPayments = computed(() => {
  return props.pendingLeaguePayments.reduce((sum, row) => sum + (Number(row.pending_amount) || 0), 0);
});

const leagueRank = (playerId) => {
  const index = sortedPlayers.value.findIndex((p) => p.id === playerId);
  return index >= 0 ? `${index + 1}°` : '-';
};

const formatAmount = (value) => new Intl.NumberFormat('es-CL').format(Math.floor(Number(value) || 0));

const formatDateTime = (value) => {
  if (!value) return 'Sin fecha';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;

  return date.toLocaleString('es-CL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const openModal = (type) => {
  editingEvent.value = null;
  if (type === 'event') {
    eventForm.reset();
    eventForm.season_id = props.seasons[0]?.id || '';
    eventForm.event_type = 'liga';
    eventForm.prizes_list = [''];
    eventForm._method = 'POST';
    resetTournamentOnlyFields();
  }
  activeModal.value = type;
};

const editEvent = (event) => {
  editingEvent.value = event;
  eventForm.season_id = event.season_id;
  eventForm.name = event.name;

  const date = new Date(event.event_date);
  const tzoffset = new Date().getTimezoneOffset() * 60000;
  eventForm.event_date = new Date(date - tzoffset).toISOString().slice(0, 16);

  eventForm.event_type = event.event_type;
  eventForm.description = event.description || '';
  eventForm.rules = event.rules || '';
  eventForm.location = event.location || '';
  eventForm.time = event.time || '';
  eventForm.prizes = event.prizes || '';
  eventForm.registration_cost = event.registration_cost || 0;
  eventForm.bank_name = event.bank_name || '';
  eventForm.account_type = event.account_type || '';
  eventForm.account_holder = event.account_holder || '';
  eventForm.account_number = event.account_number || '';
  eventForm.account_email = event.account_email || '';
  eventForm.payment_instructions = event.payment_instructions || '';
  eventForm.prizes_list = event.prizes ? event.prizes.split('\n') : [''];
  eventForm.show_on_index = !!event.show_on_index;
  eventForm._method = 'PUT';
  activeModal.value = 'event';
};

const deleteEvent = async (id) => {
  const confirmed = await ask({
    title: 'Eliminar evento',
    message: '¿Estás seguro de eliminar este evento?',
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.events.destroy', id), { preserveScroll: true });
};

const editPlayer = (player) => {
  editingPlayer.value = player;
  playerEditForm.blader_name = player.blader_name || '';
  playerEditForm.real_name = player.real_name || '';
  playerEditForm.active = player.active !== false;
  playerEditForm.avatar = null;
  playerAvatarPreview.value = player.avatar_path || player.user?.avatar_path ? `/storage/${player.avatar_path || player.user?.avatar_path}` : null;
  activeModal.value = 'player';
};

const handlePlayerAvatarChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (event) => {
    cropperData.value = {
      show: true,
      src: event.target.result,
    };
  };
  reader.readAsDataURL(file);
};

const handleCroppedAvatar = (blob) => {
  const file = new File([blob], "avatar.jpg", { type: "image/jpeg" });
  playerEditForm.avatar = file;
  playerAvatarPreview.value = URL.createObjectURL(blob);
};

const submitPlayerEdit = () => {
  playerEditForm.post(route('admin.players.update', editingPlayer.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      activeModal.value = null;
    },
  });
};

const addAllGxMembers = async () => {
  const confirmed = await ask({
    title: 'Añadir miembros GX',
    message: '¿Añadir automáticamente a todos los Miembros GX al roster oficial?',
    confirmText: 'Añadir',
    tone: 'primary',
  });

  if (!confirmed) return;
  router.post(route('admin.players.addAll'), {}, { preserveScroll: true });
};

const togglePlayerActive = (player) => {
  router.patch(route('admin.players.toggle-active', player.id), {}, { preserveScroll: true });
};

const deletePlayer = async (id) => {
  const confirmed = await ask({
    title: 'Eliminar jugador',
    message: '¿Eliminar este jugador del roster oficial?',
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.players.destroy', id), { preserveScroll: true });
};

const submitSeason = () => seasonForm.post(route('admin.seasons.store'), { onSuccess: () => (activeModal.value = null) });
const addPrizeRow = () => eventForm.prizes_list.push('');
const removePrizeRow = (index) => {
  if (eventForm.prizes_list.length > 1) {
    eventForm.prizes_list.splice(index, 1);
  } else {
    eventForm.prizes_list[0] = '';
  }
};

const resetTournamentOnlyFields = () => {
  eventForm.description = '';
  eventForm.rules = '';
  eventForm.prizes = '';
  eventForm.prizes_list = [''];
  eventForm.bank_name = '';
  eventForm.account_type = '';
  eventForm.account_holder = '';
  eventForm.account_number = '';
  eventForm.account_email = '';
  eventForm.payment_instructions = '';
};

watch(() => eventForm.event_type, (type) => {
  if (type === 'liga' || type === 'torneo_ranking') {
    resetTournamentOnlyFields();
  }
});

const submitEvent = () => {
  if (eventForm.event_type === 'torneo') {
    eventForm.prizes = eventForm.prizes_list.filter((p) => p.trim() !== '').join('\n');
  } else {
    resetTournamentOnlyFields();
  }

  if (editingEvent.value) {
    eventForm.post(route('admin.events.update', editingEvent.value.id), { onSuccess: () => (activeModal.value = null) });
  } else {
    eventForm.post(route('admin.events.store'), { onSuccess: () => (activeModal.value = null) });
  }
};
const eventBadgeClass = (type) => {
  if (type === 'torneo') return 'badge-amber';
  if (type === 'torneo_ranking') return 'badge-green';
  return 'badge-blue';
};

const eventTypeLabel = (type) => {
  if (type === 'torneo') return 'Torneo';
  if (type === 'torneo_ranking') return 'Torneo+Ranking';
  return 'Liga';
};

const submitPlayer = () => playerForm.post(route('admin.players.store'), { onSuccess: () => playerForm.reset() });
</script>

<style scoped>
.admin-grid-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);
}

@media (min-width: 992px) {
  .admin-grid-layout {
    grid-template-columns: 1fr 1fr;
  }
  .full-width {
    grid-column: span 2;
  }
}

.divider {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  font-size: 0.65rem;
  text-transform: uppercase;
  color: var(--gx-red);
  font-weight: 800;
  letter-spacing: 0.1em;
}

.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: rgba(225, 6, 0, 0.2);
}

.roster-inline-form {
  display: flex;
  gap: var(--space-md);
  align-items: flex-end;
}

.roster-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: var(--space-lg);
}

.player-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.player-card.inactive {
  opacity: 0.68;
  border-color: rgba(239, 68, 68, 0.35);
}

.player-head {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.preview-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 3px solid var(--gx-red);
  margin: 0 auto;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.03);
  position: relative;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-avatar-small {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  overflow: hidden;
  border: 2px solid var(--border-color);
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.player-name {
  font-size: 1rem;
  margin: 0;
}

.player-stats-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.player-stats-grid > div {
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-sm);
  padding: 6px 8px;
  background: rgba(0, 0, 0, 0.22);
}

.player-stats-grid .k {
  display: block;
  font-size: 0.65rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.green { color: #22c55e; }
.red { color: #ef4444; }
.gold { color: #ffd700; }

.player-actions {
  display: flex;
  justify-content: space-between;
  margin-top: auto;
  gap: var(--space-xs);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--z-modal);
}

.modal-content {
  width: 100%;
  max-width: 560px;
  padding: var(--space-xl);
  max-height: 90vh;
  overflow-y: auto;
}

.gx-table {
  width: 100%;
  border-collapse: collapse;
}

.gx-table th {
  padding: var(--space-md);
  text-align: left;
  font-size: 0.7rem;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border-color);
}

.gx-table td {
  padding: var(--space-md);
  border-bottom: 1px solid var(--border-color);
}

@media (max-width: 640px) {
  .roster-inline-form {
    flex-direction: column;
  }
  .roster-inline-form button {
    width: 100%;
  }
}
</style>
