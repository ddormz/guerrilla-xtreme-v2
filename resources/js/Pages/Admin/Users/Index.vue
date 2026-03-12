<template>
  <AppLayout>
    <Head title="Admin - Usuarios" />

    <div class="page-content">
      <div class="page-header flex justify-between items-center mb-lg">
        <div>
          <h1 class="page-title text-gradient">Gestión de Usuarios</h1>
          <p class="text-secondary">Administra los roles y contraseñas de la comunidad.</p>
        </div>
      </div>

      <div class="card stagger">
        <div class="card-header flex justify-between items-center flex-wrap gap-md">
          <div class="search-box">
            <input 
              v-model="search" 
              type="text" 
              class="form-input" 
              placeholder="Buscar por nombre, email..." 
              @keyup.enter="applyFilters"
            />
            <button @click="applyFilters" class="btn btn-outline btn-sm shadow-sm" style="margin-left:8px">Buscar</button>
          </div>
          
          <div class="filter-box">
            <select v-model="roleFilter" class="form-input" @change="applyFilters">
              <option value="">Todos los Roles</option>
              <option value="miembro">Miembro</option>
              <option value="miembro_gx">Miembro GX</option>
              <option value="arbitro_gx">Árbitro GX</option>
              <option value="admin">Administrador</option>
            </select>
            <button v-if="search || roleFilter" @click="clearFilters" class="btn btn-ghost btn-sm">Limpiar</button>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-container">
            <table class="data-table w-full">
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th>Contacto</th>
                  <th>Registro</th>
                  <th>Rol</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users.data" :key="user.id">
                  <td>
                    <div class="flex items-center gap-sm">
                      <div class="user-avatar-sm" style="background:var(--bg-primary); color:var(--text-primary)">
                        <img v-if="user.avatar_path" :src="`/storage/${user.avatar_path}`" alt="Avatar" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" @error="handleImageError">
                        <span v-else>{{ user.blader_name?.charAt(0) || user.name?.charAt(0) }}</span>
                      </div>
                      <div>
                        <div class="font-bold">{{ user.name }}</div>
                        <div class="text-sm text-secondary">{{ user.blader_name || 'Sin nick' }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div>{{ user.email }}</div>
                    <div class="text-sm text-secondary">{{ user.phone }}</div>
                  </td>
                  <td>{{ user.created_at }}</td>
                  <td>
                    <span :class="['role-badge', user.role]">
                      {{ formatRole(user.role) }}
                    </span>
                  </td>
                  <td>
                    <div class="flex gap-sm">
                      <button @click="openEditModal(user)" class="btn btn-outline btn-sm" title="Editar Usuario">✎ Datos</button>
                      <button @click="openRoleModal(user)" class="btn btn-outline btn-sm" title="Cambiar Rol">🛡️ Rol</button>
                      <button @click="resetPassword(user)" class="btn btn-warning btn-sm" title="Resetear Contraseña">🔑</button>
                      <button @click="deleteUser(user)" class="btn btn-danger btn-sm" title="Eliminar Usuario">🗑️</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="users.data.length === 0">
                  <td colspan="5" class="text-center py-xl text-secondary">No se encontraron usuarios</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="pagination flex justify-center gap-sm p-4" v-if="users.links.length > 3">
            <template v-for="(link, p) in users.links" :key="p">
              <div v-if="link.url === null" class="pagination-link disabled" v-html="link.label" />
              <Link v-else class="pagination-link" :class="{ 'active': link.active }" :href="link.url" v-html="link.label" />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Modal -->
    <div v-if="roleModalOpen" class="modal-overlay" @click.self="roleModalOpen = false">
      <div class="modal-content card">
        <div class="modal-header">
          <h3>Cambiar Rol</h3>
          <button @click="roleModalOpen = false" class="btn-close">×</button>
        </div>
        <form @submit.prevent="submitRoleChange">
          <div class="modal-body form-group">
            <p class="mb-sm">Selecciona el nuevo rol para <strong>{{ selectedUser.name }}</strong></p>
            <select v-model="roleForm.role" class="form-input" required>
              <option value="miembro">Miembro (Normal)</option>
              <option value="miembro_gx">Miembro GX Official</option>
              <option value="arbitro_gx">Árbitro GX</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
          <div class="modal-footer flex justify-end gap-sm mt-md">
            <button type="button" @click="roleModalOpen = false" class="btn btn-ghost">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="roleForm.processing">Guardar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editModalOpen" class="modal-overlay" @click.self="editModalOpen = false">
      <div class="modal-content card">
        <div class="modal-header">
          <h3>Editar Usuario</h3>
          <button @click="editModalOpen = false" class="btn-close">×</button>
        </div>
        <form @submit.prevent="submitEdit">
          <div class="modal-body form-group">
            <div class="mb-md">
              <label class="form-label">Nombre Real</label>
              <input type="text" v-model="editForm.name" class="form-input" required />
            </div>
            <div class="mb-md">
              <label class="form-label">Nick Blader</label>
              <input type="text" v-model="editForm.blader_name" class="form-input" />
            </div>
            <div class="mb-md">
              <label class="form-label">Email</label>
              <input type="email" v-model="editForm.email" class="form-input" required />
            </div>
            <div class="mb-md">
              <label class="form-label">Avatar</label>
              <input type="file" @input="editForm.avatar = $event.target.files[0]" class="form-input" accept="image/*" />
            </div>
            <div class="mb-md">
              <label class="form-label">Rol del Sistema</label>
              <select v-model="editForm.role" class="form-input" required>
                <option value="miembro">Miembro (Normal)</option>
                <option value="miembro_gx">Miembro GX Official</option>
                <option value="arbitro_gx">Árbitro GX</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
          </div>
          <div class="modal-footer flex justify-end gap-sm mt-md">
            <button type="button" @click="editModalOpen = false" class="btn btn-ghost">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="editForm.processing">Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  users: Object,
  filters: Object,
});

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');

const selectedUser = ref(null);
const roleModalOpen = ref(false);
const editModalOpen = ref(false);

const roleForm = useForm({ role: '' });
const editForm = useForm({
  name: '',
  blader_name: '',
  email: '',
  role: '',
  avatar: null,
  _method: 'PUT'
});

const { ask } = useConfirm();

const formatRole = (role) => {
  const map = {
    'admin': 'Administrador',
    'arbitro_gx': 'Árbitro GX',
    'miembro_gx': 'Miembro GX',
    'miembro': 'Miembro'
  };
  return map[role] || role;
};

const applyFilters = () => {
  router.get(route('admin.users.index'), {
    search: search.value,
    role: roleFilter.value
  }, { preserveState: true, replace: true });
};

const clearFilters = () => {
  search.value = '';
  roleFilter.value = '';
  applyFilters();
};

const openRoleModal = (user) => {
  selectedUser.value = user;
  roleForm.role = user.role;
  roleModalOpen.value = true;
};

const submitRoleChange = () => {
  roleForm.patch(route('admin.users.role', selectedUser.value.id), {
    onSuccess: () => {
      roleModalOpen.value = false;
    }
  });
};

const resetPassword = async (user) => {
  const confirmed = await ask({
    title: 'Resetear contraseña',
    message: `¿Enviar una nueva contraseña temporal al correo de ${user.name}?`,
    confirmText: 'Enviar',
    tone: 'primary',
  });

  if (!confirmed) return;

  router.patch(route('admin.users.password', user.id), {}, {
    preserveState: true,
    preserveScroll: true
  });
};

const openEditModal = (user) => {
  selectedUser.value = user;
  editForm.name = user.name;
  editForm.blader_name = user.blader_name;
  editForm.email = user.email;
  editForm.role = user.role;
  editForm.avatar = null;
  editModalOpen.value = true;
};

const submitEdit = () => {
  editForm.post(route('admin.users.update', selectedUser.value.id), {
    onSuccess: () => {
      editModalOpen.value = false;
    }
  });
};

const deleteUser = async (user) => {
  const confirmed = await ask({
    title: 'Eliminar usuario',
    message: `¿Estás seguro de que deseas eliminar a ${user.name}? Esta acción no se puede deshacer.`,
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.users.destroy', user.id));
};

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.user-avatar-sm {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  overflow: hidden;
  flex-shrink: 0;
}

.data-table {
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: var(--space-md);
  text-align: left;
  border-bottom: 1px solid var(--border-color);
}

.data-table th {
  background: rgba(0,0,0,0.02);
  font-weight: 600;
  color: var(--text-secondary);
}

.role-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: bold;
}

.role-badge.admin { background: rgba(225, 6, 0, 0.15); color: #ff3333; border: 1px solid rgba(225, 6, 0, 0.2); }
.role-badge.arbitro_gx { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }
.role-badge.miembro_gx { background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2); }
.role-badge.miembro { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }

.search-box {
  display: flex;
  max-width: 400px;
  flex: 1;
}

.pagination-link {
  padding: 8px 12px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 4px;
  color: var(--text-primary);
}

.pagination-link.active {
  background: var(--gx-red);
  color: white;
  border-color: var(--gx-red);
}

.pagination-link.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal Simple Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  backdrop-filter: blur(4px);
}

.modal-content {
  width: 100%;
  max-width: 450px;
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  animation: fadeIn 0.2s ease-out;
}

.modal-header {
  padding: var(--space-md) var(--space-lg);
  border-bottom: 1px solid var(--border-color);
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

.modal-body {
  padding: var(--space-lg);
}

.modal-footer {
  padding: 0 var(--space-lg) var(--space-lg);
}
</style>

