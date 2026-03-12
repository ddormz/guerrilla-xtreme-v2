<template>
  <AppLayout>
    <Head title="Admin - Roster" />

    <div class="page-content">
      <div class="page-header flex justify-between items-center mb-lg flex-wrap gap-md">
        <div>
          <h1 class="page-title text-gradient">Gestión del Roster</h1>
          <p class="text-secondary">Administra los miembros públicos del equipo GX.</p>
        </div>
        <button @click="openCreateModal" class="btn btn-primary" type="button">
          <span class="btn-icon">➕</span> Nuevo Miembro
        </button>
      </div>

      <div v-if="members.length > 0" class="roster-grid stagger">
        <article v-for="member in members" :key="member.id" class="member-card card card-hover">
          <div class="card-status-dot" :class="member.is_active ? 'active' : 'inactive'"></div>

          <div class="member-header">
            <div class="member-photo-container">
              <img v-if="member.photo_path" :src="`/storage/${member.photo_path}`" alt="Foto Member" class="member-photo" @error="handleImageError">
              <div v-else class="member-photo-placeholder">👤</div>

              <div class="member-lock-chip" v-if="member.lock_chip_photo">
                <img :src="`/storage/${member.lock_chip_photo}`" alt="Chip" @error="handleImageError">
              </div>
            </div>

            <div class="member-info">
              <h3 class="member-name">{{ member.name }}</h3>
              <p class="member-nick text-gradient">{{ member.blader_name || 'Sin nick' }}</p>
              <div class="member-role">{{ member.role_title }}</div>
            </div>
          </div>

          <div class="member-footer">
            <div class="member-socials">
              <a v-if="member.instagram" :href="`https://instagram.com/${member.instagram}`" target="_blank" class="social-btn ig">IG</a>
              <a v-if="member.tiktok" :href="`https://tiktok.com/@${member.tiktok}`" target="_blank" class="social-btn tk">TK</a>
            </div>

            <div class="member-actions">
              <button @click="toggleStatus(member)" class="btn-icon-round" :class="{ 'btn-active': member.is_active }" title="Toggle Visibilidad" type="button">
                {{ member.is_active ? '👁️' : '🕶️' }}
              </button>
              <button @click="openEditModal(member)" class="btn-icon-round" title="Editar" type="button">✎</button>
              <button @click="confirmDelete(member)" class="btn-icon-round btn-danger" title="Eliminar" type="button">🗑️</button>
            </div>
          </div>
          <div class="member-order-badge">ORDEN: {{ member.display_order }}</div>
        </article>
      </div>
      <div v-else class="card p-2xl text-center text-secondary">
        No hay miembros en el roster.
      </div>

    </div>

    <div v-if="modalOpen" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content card max-w-2xl">
        <div class="modal-header">
          <h3>{{ editingMember ? 'Editar Miembro' : 'Añadir al Roster' }}</h3>
          <button @click="closeModal" class="btn-close" type="button">×</button>
        </div>
        <form @submit.prevent="submitForm">
          <div class="modal-body">

            <div class="form-group mb-md">
              <label class="form-label">Vincular a Usuario (Opcional)</label>
              <select v-model="form.user_id" class="form-input">
                <option value="">-- Sin usuario vinculado --</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.blader_name || user.name }}
                </option>
              </select>
              <p class="text-xs text-secondary mt-xs">Vincular un usuario permite filtrar correctamente el roster en el Home.</p>
              <span v-if="form.errors.user_id" class="form-error">{{ form.errors.user_id }}</span>
            </div>

            <div class="upload-grid mb-lg">
              <div class="upload-panel">
                <p class="text-xs font-bold mb-2 text-secondary">Foto Blader</p>
                <div class="preview-circle">
                  <img
                    v-if="displayPhotoPreview"
                    :src="displayPhotoPreview"
                    class="preview-image"
                    @error="handleImageError"
                  >
                  <span v-else>👤</span>
                </div>
                <label for="photo" class="avatar-edit-btn">🖼️</label>
                <input type="file" id="photo" accept="image/*" class="sr-only" @change="handlePhotoChange" />
                <span v-if="form.errors.photo" class="form-error block mt-sm">{{ form.errors.photo }}</span>
              </div>

              <div class="crop-preview-panel">
                <p class="text-xs font-bold mb-2 text-secondary">Preview de recorte</p>
                <div class="crop-preview-circle">
                  <img
                    v-if="displayPhotoPreview"
                    :src="displayPhotoPreview"
                    class="crop-preview-image"
                    @error="handleImageError"
                  >
                  <span v-else>Sin imagen</span>
                </div>
                <p class="text-xs text-secondary mt-xs">La foto se recorta automáticamente al centro y mantiene proporción.</p>
              </div>

              <div class="upload-panel">
                <p class="text-xs font-bold mb-2 text-secondary">Lock Chip</p>
                <div class="preview-square">
                  <img
                    v-if="lockChipPreview || (editingMember && editingMember.lock_chip_photo)"
                    :src="lockChipPreview || `/storage/${editingMember.lock_chip_photo}`"
                    class="preview-lock-chip"
                    @error="handleImageError"
                  >
                  <span v-else>⚙️</span>
                </div>
                <label for="lock_chip" class="avatar-edit-btn lock">🖼️</label>
                <input type="file" id="lock_chip" accept="image/*" class="sr-only" @change="handleLockChipChange" />
                <span v-if="form.errors.lock_chip_photo" class="form-error block mt-sm">{{ form.errors.lock_chip_photo }}</span>
              </div>
            </div>

            <div class="form-row flex gap-md mb-md">
              <div class="form-group flex-1">
                <label class="form-label">Nombre *</label>
                <input type="text" v-model="form.name" class="form-input" required />
                <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
              </div>
              <div v-if="!form.user_id" class="form-group flex-1">
                <label class="form-label">Blader Name</label>
                <input type="text" v-model="form.blader_name" class="form-input" />
              </div>
            </div>

            <div class="form-row flex gap-md mb-md">
              <div class="form-group flex-1">
                <label class="form-label">Rol / Rango *</label>
                <input type="text" v-model="form.role_title" class="form-input" required placeholder="Ej: Fundador, Miembro" />
                <span v-if="form.errors.role_title" class="form-error">{{ form.errors.role_title }}</span>
              </div>
              <div class="form-group" style="width:100px;">
                <label class="form-label">Orden</label>
                <input type="number" v-model="form.display_order" class="form-input text-center" min="0" required />
              </div>
            </div>

            <div class="form-row flex gap-md mb-md">
              <div class="form-group flex-1">
                <label class="form-label">Instagram</label>
                <input type="text" v-model="form.instagram" class="form-input" placeholder="usuario_ig" />
              </div>
              <div class="form-group flex-1">
                <label class="form-label">TikTok</label>
                <input type="text" v-model="form.tiktok" class="form-input" placeholder="usuario_tiktok" />
              </div>
            </div>

            <div class="form-group mt-md">
              <label class="flex items-center gap-sm cursor-pointer">
                <input type="checkbox" v-model="form.is_active" />
                <span>¿Mostrar públicamente en el Inicio?</span>
              </label>
            </div>

          </div>
          <div class="modal-footer flex justify-end gap-sm mt-md">
            <button type="button" @click="closeModal" class="btn btn-ghost">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ editingMember ? 'Actualizar' : 'Crear' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <ImageCropper
      v-if="cropperData.show"
      :show="cropperData.show"
      :image-src="cropperData.src"
      :aspect-ratio="cropperData.aspect"
      @close="cropperData.show = false"
      @cropped="handleCroppedImage"
    />
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import ImageCropper from '@/Components/ImageCropper.vue';

const props = defineProps({
  members: Array,
  users: Array,
});

const { ask } = useConfirm();

const modalOpen = ref(false);
const editingMember = ref(null);
const photoPreview = ref(null);
const lockChipPreview = ref(null);

const cropperData = ref({
  show: false,
  src: '',
  aspect: 1,
  target: 'photo' // 'photo' or 'lock_chip'
});

const form = useForm({
  user_id: '',
  name: '',
  blader_name: '',
  role_title: 'Miembro',
  instagram: '',
  tiktok: '',
  display_order: 0,
  is_active: true,
  photo: null,
  lock_chip_photo: null,
});

const displayPhotoPreview = computed(() => {
  if (photoPreview.value) return photoPreview.value;
  if (editingMember.value?.photo_path) return `/storage/${editingMember.value.photo_path}`;
  return null;
});

const openCreateModal = () => {
  editingMember.value = null;
  form.reset();
  form.user_id = '';
  form.clearErrors();
  photoPreview.value = null;
  lockChipPreview.value = null;
  form.display_order = props.members.length + 1;
  modalOpen.value = true;
};

const openEditModal = (member) => {
  editingMember.value = member;
  form.user_id = member.user_id || '';
  form.name = member.name;
  form.blader_name = member.blader_name || '';
  form.role_title = member.role_title;
  form.instagram = member.instagram || '';
  form.tiktok = member.tiktok || '';
  form.display_order = member.display_order;
  form.is_active = !!member.is_active;
  form.photo = null;
  form.lock_chip_photo = null;
  form.clearErrors();
  photoPreview.value = null;
  lockChipPreview.value = null;
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const handlePhotoChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (event) => {
    cropperData.value = {
      show: true,
      src: event.target.result,
      aspect: 1,
      target: 'photo'
    };
  };
  reader.readAsDataURL(file);
};

const handleLockChipChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (event) => {
    cropperData.value = {
      show: true,
      src: event.target.result,
      aspect: 1,
      target: 'lock_chip'
    };
  };
  reader.readAsDataURL(file);
};

const handleCroppedImage = (blob) => {
  const file = new File([blob], "cropped_image.jpg", { type: "image/jpeg" });
  
  if (cropperData.value.target === 'photo') {
    form.photo = file;
    photoPreview.value = URL.createObjectURL(blob);
  } else {
    form.lock_chip_photo = file;
    lockChipPreview.value = URL.createObjectURL(blob);
  }
};

const submitForm = () => {
  if (editingMember.value) {
    form.transform((data) => ({
      ...data,
      _method: 'PUT',
    })).post(route('admin.members.update', editingMember.value.id), {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('admin.members.store'), {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
};

const toggleStatus = (member) => {
  router.patch(route('admin.members.toggle', member.id), {}, { preserveScroll: true });
};

const confirmDelete = async (member) => {
  const confirmed = await ask({
    title: 'Eliminar miembro del roster',
    message: `¿Estás seguro de eliminar a ${member.name} del roster?\nEsta acción no se puede deshacer.`,
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.members.destroy', member.id), { preserveScroll: true });
};

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.roster-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--space-lg);
}

.member-card {
  position: relative;
  padding: var(--space-lg);
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.card-status-dot {
  position: absolute;
  top: 15px;
  right: 15px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  box-shadow: 0 0 10px currentColor;
}

.card-status-dot.active { color: #10b981; background: #10b981; }
.card-status-dot.inactive { color: #6b7280; background: #6b7280; }

.member-header {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
}

.member-photo-container {
  position: relative;
  width: 80px;
  height: 80px;
  flex-shrink: 0;
}

.member-photo {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  object-position: center;
  border: 3px solid var(--gx-red);
  box-shadow: 0 4px 15px rgba(225, 6, 0, 0.3);
}

.member-photo-placeholder {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: var(--bg-elevated);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  border: 3px dashed var(--border-color);
}

.member-lock-chip {
  position: absolute;
  bottom: -5px;
  right: -5px;
  width: 32px;
  height: 32px;
  background: black;
  border: 2px solid var(--accent-blue);
  border-radius: 8px;
  padding: 2px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.5);
}

.member-lock-chip img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.member-name {
  font-size: 1.25rem;
  font-weight: 800;
  margin: 0;
  line-height: 1.2;
}

.member-nick {
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  margin: 2px 0;
}

.member-role {
  font-size: 0.75rem;
  text-transform: uppercase;
  font-weight: 900;
  color: var(--text-muted);
  letter-spacing: 0.1em;
}

.member-footer {
  margin-top: auto;
  padding-top: var(--space-md);
  border-top: 1px solid var(--white-05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.member-socials {
  display: flex;
  gap: var(--space-xs);
}

.social-btn {
  text-decoration: none;
  font-size: 0.7rem;
  font-weight: 900;
  padding: 4px 8px;
  border-radius: 6px;
  color: white;
  transition: transform 0.2s ease;
}

.social-btn:hover { transform: translateY(-2px); }
.social-btn.ig { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
.social-btn.tk { background: #000; border: 1px solid rgba(255,255,255,0.2); }

.member-actions {
  display: flex;
  gap: var(--space-xs);
}

.btn-icon-round {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid var(--white-10);
  background: var(--bg-elevated);
  color: var(--text-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon-round:hover {
  background: var(--white-10);
  border-color: var(--white-20);
}

.btn-active {
  background: rgba(16, 185, 129, 0.1);
  border-color: #10b981;
  color: #10b981;
}

.btn-danger:hover {
  background: var(--gx-red);
  border-color: var(--gx-red);
  color: white;
}

.member-order-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  font-size: 10px;
  font-weight: 900;
  color: var(--text-muted);
  background: var(--bg-primary);
  padding: 2px 6px;
  border-radius: 4px;
  opacity: 0.5;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--z-modal);
  backdrop-filter: blur(8px);
  padding: var(--space-md);
}

.modal-content {
  width: 95%;
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  max-height: 90vh;
  overflow-y: auto;
  border: 1px solid var(--white-10);
}

.modal-header {
  padding: var(--space-lg);
  border-bottom: 1px solid var(--white-05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--text-secondary);
  cursor: pointer;
}

.modal-body { padding: var(--space-lg); }
.modal-footer { padding: 0 var(--space-lg) var(--space-lg); border-top: 1px solid var(--white-05); padding-top: var(--space-md); }

.upload-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-md);
}

.upload-panel,
.crop-preview-panel {
  position: relative;
  text-align: center;
}

.preview-circle,
.crop-preview-circle {
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
}

.preview-image,
.crop-preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}

.preview-square {
  width: 120px;
  height: 120px;
  border-radius: 12px;
  border: 3px solid var(--accent-blue);
  margin: 0 auto;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.03);
}

.preview-lock-chip {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.avatar-edit-btn {
  position: absolute;
  bottom: 0;
  right: 22px;
  cursor: pointer;
  background: var(--gx-red);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-edit-btn.lock {
  background: var(--accent-blue);
}

@media (max-width: 900px) {
  .upload-grid {
    grid-template-columns: 1fr;
  }
}
</style>
