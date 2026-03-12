<template>
  <AppLayout>
    <Head title="Mi Perfil" />

    <div class="page-content">
      <div class="page-header mb-lg">
        <h1 class="page-title text-gradient">Mi Perfil</h1>
        <p class="text-secondary">Administra tu información, seguridad y rendimiento en la liga.</p>
      </div>

      <section v-if="leagueStats" class="card mb-xl stats-card">
        <div class="stats-head">
          <div class="blader-identity">
            <img :src="leagueStats.avatar_url || page.props.user.avatar_path || '/img/placeholders/blader.png'" class="blader-avatar" alt="Blader" @error="handleImageError" />
            <div>
              <h3>{{ leagueStats.blader_name || page.props.user.blader_name || page.props.user.name }}</h3>
              <p class="text-secondary">Estadísticas de liga</p>
            </div>
          </div>
          <span class="rank-badge" v-if="leagueStats.rank">{{ leagueStats.rank }}°</span>
        </div>

        <div class="league-stats-grid">
          <div class="stat-box"><span class="label">Puntos</span><strong class="gold">{{ leagueStats.points }}</strong></div>
          <div class="stat-box"><span class="label">Ganadas</span><strong class="green">{{ leagueStats.wins }}</strong></div>
          <div class="stat-box"><span class="label">Perdidas</span><strong class="red">{{ leagueStats.losses }}</strong></div>
          <div class="stat-box"><span class="label">Xtremes</span><strong class="gold">{{ leagueStats.xtremes }}</strong></div>
          <div class="stat-box"><span class="label">Win Rate</span><strong>{{ leagueStats.win_rate }}%</strong></div>
          <div class="stat-box"><span class="label">Bursts</span><strong>{{ leagueStats.bursts }}</strong></div>
          <div class="stat-box"><span class="label">Overs</span><strong>{{ leagueStats.overs }}</strong></div>
          <div class="stat-box"><span class="label">Partidas</span><strong>{{ leagueStats.total_matches }}</strong></div>
        </div>

        <div class="lineage-grid">
          <div class="lineage-card">
            <span class="label">Quién es tu papá</span>
            <strong>{{ leagueStats.father ? leagueStats.father.name : 'Sin registro' }}</strong>
            <small v-if="leagueStats.father">{{ leagueStats.father.count }} derrotas</small>
          </div>
          <div class="lineage-card">
            <span class="label">Quién es tu hijo</span>
            <strong>{{ leagueStats.son ? leagueStats.son.name : 'Sin registro' }}</strong>
            <small v-if="leagueStats.son">{{ leagueStats.son.count }} victorias</small>
          </div>
        </div>

        <div v-if="globalLeaders && (globalLeaders.xtremes || globalLeaders.overs || globalLeaders.bursts || globalLeaders.wins)" class="achievements-section mt-xl stagger">
          <h4 class="section-subtitle mb-md uppercase tracking-widest text-xs text-secondary">Logros Globales</h4>
          <div class="achievements-grid">
            <div v-if="globalLeaders.xtremes" class="achievement-card gold anim-pulse">
              <span class="ach-icon">🔥</span>
              <div class="ach-info">
                <strong>REY XTREME</strong>
                <p>Líder global en finales Xtreme</p>
              </div>
            </div>
            <div v-if="globalLeaders.overs" class="achievement-card blue">
              <span class="ach-icon">🌪️</span>
              <div class="ach-info">
                <strong>MÁQUINA OVER</strong>
                <p>Más finales por salida del estadio</p>
              </div>
            </div>
            <div v-if="globalLeaders.bursts" class="achievement-card green">
              <span class="ach-icon">💥</span>
              <div class="ach-info">
                <strong>MAESTRO BURST</strong>
                <p>Líder en finales explosivos</p>
              </div>
            </div>
            <div v-if="globalLeaders.wins" class="achievement-card red">
              <span class="ach-icon">🏆</span>
              <div class="ach-info">
                <strong>INVICTO</strong>
                <p>Jugador con más victorias totales</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="profile-grid">
        <div class="card stagger">
          <div class="card-header">
            <h3>Información Personal</h3>
          </div>
          <div class="card-body">
            <form @submit.prevent="updateProfile">
              <div class="avatar-upload mb-lg text-center">
                <div class="avatar-preview mx-auto">
                  <img :src="avatarPreview || page.props.user.avatar_path || '/img/placeholders/blader.png'" alt="Avatar" class="avatar-img" />
                  <label for="avatar" class="avatar-edit-btn">✎</label>
                  <input type="file" id="avatar" accept="image/*" class="sr-only" @change="handleAvatarChange" />
                </div>
                <span v-if="profileForm.errors.avatar" class="form-error block mt-sm">{{ profileForm.errors.avatar }}</span>
              </div>

              <div class="form-row">
                <div class="form-group flex-1">
                  <label for="name" class="form-label">Nombre</label>
                  <input id="name" type="text" class="form-input" v-model="profileForm.name" required />
                  <span v-if="profileForm.errors.name" class="form-error">{{ profileForm.errors.name }}</span>
                </div>
                <div class="form-group flex-1">
                  <label for="blader_name" class="form-label">Blader Name</label>
                  <input id="blader_name" type="text" class="form-input" v-model="profileForm.blader_name" />
                  <span v-if="profileForm.errors.blader_name" class="form-error">{{ profileForm.errors.blader_name }}</span>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group flex-1">
                  <label for="email" class="form-label">Email</label>
                  <input id="email" type="email" class="form-input" v-model="profileForm.email" required />
                  <span v-if="profileForm.errors.email" class="form-error">{{ profileForm.errors.email }}</span>
                </div>
                <div class="form-group flex-1">
                  <label for="phone" class="form-label">WhatsApp</label>
                  <input id="phone" type="text" class="form-input" v-model="profileForm.phone" required />
                  <span v-if="profileForm.errors.phone" class="form-error">{{ profileForm.errors.phone }}</span>
                </div>
              </div>

              <div class="flex justify-end mt-sm">
                <button type="submit" class="btn btn-primary" :disabled="profileForm.processing">Guardar Cambios</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card stagger" style="animation-delay: 0.1s">
          <div class="card-header">
            <h3>Actualizar Contraseña</h3>
          </div>
          <div class="card-body">
            <form @submit.prevent="updatePassword">
              <div class="form-group">
                <label for="current_password" class="form-label">Contraseña Actual</label>
                <input id="current_password" type="password" class="form-input" v-model="passwordForm.current_password" required />
                <span v-if="passwordForm.errors.current_password" class="form-error">{{ passwordForm.errors.current_password }}</span>
              </div>

              <div class="form-group">
                <label for="password" class="form-label">Nueva Contraseña</label>
                <input id="password" type="password" class="form-input" v-model="passwordForm.password" required />
                <span v-if="passwordForm.errors.password" class="form-error">{{ passwordForm.errors.password }}</span>
              </div>

              <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                <input id="password_confirmation" type="password" class="form-input" v-model="passwordForm.password_confirmation" required />
              </div>

              <div class="flex justify-end mt-sm">
                <button type="submit" class="btn btn-warning" :disabled="passwordForm.processing">Cambiar Contraseña</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <ImageCropper
        :show="cropper.show"
        :image-src="cropper.imageSrc"
        :aspect-ratio="1"
        @close="cropper.show = false"
        @cropped="handleCropped"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ImageCropper from '@/Components/ImageCropper.vue';

defineProps({
  leagueStats: Object,
  rankingHistory: { type: Array, default: () => [] },
  globalLeaders: Object,
});

const page = usePage();
const avatarPreview = ref(null);
const cropper = ref({
  show: false,
  imageSrc: null,
});

const profileForm = useForm({
  name: page.props.user.name || '',
  blader_name: page.props.user.blader_name || '',
  email: page.props.user.email || '',
  phone: page.props.user.phone || '',
  avatar: null,
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const handleAvatarChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (event) => {
    cropper.value.imageSrc = event.target.result;
    cropper.value.show = true;
  };
  reader.readAsDataURL(file);
  e.target.value = '';
};

const handleCropped = (blob) => {
  const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
  profileForm.avatar = file;
  avatarPreview.value = URL.createObjectURL(blob);
};

const updateProfile = () => {
  profileForm
    .transform((data) => ({
      ...data,
      _method: 'PATCH',
    }))
    .post(route('profile.update'), {
      preserveScroll: true,
      forceFormData: true,
    });
};

const updatePassword = () => {
  passwordForm.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => passwordForm.reset(),
  });
};

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.profile-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);
}

.stats-card {
  display: grid;
  gap: var(--space-md);
}

.stats-head {
  display: flex;
  justify-content: space-between;
  gap: var(--space-sm);
  align-items: center;
}

.blader-identity {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.blader-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--gx-red);
}

.rank-badge {
  min-width: 46px;
  height: 46px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #ffd700, #b8860b);
  color: #1f1f1f;
  font-weight: 900;
}

.league-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: var(--space-sm);
}

.stat-box {
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  padding: var(--space-sm);
  background: rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
}

.stat-box .label {
  font-size: 0.72rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.stat-box strong {
  font-size: 1.4rem;
}

.gold { color: #ffd700; }
.green { color: #22c55e; }
.red { color: #ef4444; }

.lineage-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-sm);
}

.lineage-card {
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  padding: var(--space-sm);
  background: rgba(0, 0, 0, 0.2);
  display: grid;
  gap: 2px;
}

.history-wrap {
  overflow-x: auto;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
}

.history-table th,
.history-table td {
  border-bottom: 1px solid var(--border-color);
  padding: 8px;
  text-align: left;
}

.avatar-preview {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 3px solid var(--gx-red);
  background: var(--bg-primary);
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.avatar-edit-btn {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--gx-red);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

@media (min-width: 1024px) {
  .profile-grid {
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }
}

.form-row {
  display: flex;
  gap: var(--space-md);
  margin-bottom: var(--space-md);
}

.achievements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: var(--space-md);
}

.achievement-card {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-md);
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  position: relative;
  overflow: hidden;
}

.achievement-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
}

.achievement-card.gold::before { background: #ffd700; }
.achievement-card.blue::before { background: #3b82f6; }
.achievement-card.green::before { background: #22c55e; }
.achievement-card.red::before { background: #ef4444; }

.ach-icon {
  font-size: 1.5rem;
  filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.2));
}

.ach-info strong {
  display: block;
  font-size: 0.85rem;
  letter-spacing: 0.05em;
  color: var(--text-primary);
}

.ach-info p {
  font-size: 0.7rem;
  color: var(--text-secondary);
  line-height: 1.2;
}

.anim-pulse {
  animation: pulse-glow 2s infinite alternate;
}

@keyframes pulse-glow {
  from { box-shadow: 0 0 5px rgba(255, 215, 0, 0.1); }
  to { box-shadow: 0 0 15px rgba(255, 215, 0, 0.3); }
}

@media (max-width: 760px) {
  .league-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .lineage-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    flex-direction: column;
    gap: 0;
  }
}

@media (max-width: 480px) {
  .achievements-grid {
    grid-template-columns: 1fr;
  }
  
  .blader-identity {
    flex-direction: column;
    text-align: center;
  }
}
</style>
