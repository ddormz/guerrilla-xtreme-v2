<template>
  <AppLayout>
    <Head title="Admin - Finanzas" />

    <div class="page-content">
      <div class="page-header mb-xl">
        <h1 class="page-title text-gradient">Finanzas Globales</h1>
        <div class="flex items-center justify-between gap-md flex-wrap">
          <p class="text-secondary m-0">Vista general de billeteras, categorías y movimientos del sistema.</p>
          <button @click="openMovementModal" class="btn btn-primary" v-if="currentTab === 'movements'">+ Registrar Movimiento</button>
        </div>
      </div>

      <!-- Quick Toggles -->
      <div class="flex gap-sm mb-lg border-b border-border pb-sm">
        <button 
          @click="currentTab = 'movements'" 
          class="btn btn-sm" 
          :class="currentTab === 'movements' ? 'btn-primary' : 'btn-ghost'"
        >Movimientos</button>
        <button 
          @click="currentTab = 'wallets'" 
          class="btn btn-sm" 
          :class="currentTab === 'wallets' ? 'btn-primary' : 'btn-ghost'"
        >Billeteras</button>
        <button 
          @click="currentTab = 'categories'" 
          class="btn btn-sm" 
          :class="currentTab === 'categories' ? 'btn-primary' : 'btn-ghost'"
        >Categorías</button>
      </div>

      <!-- Tab: Movements -->
      <div v-if="currentTab === 'movements'" class="stagger">
        <div class="card mb-lg">
          <div class="card-header flex justify-between items-center bg-transparent gap-md flex-wrap">
            <h3 class="m-0">Historial de Transacciones</h3>
            <!-- Filters -->
            <div class="flex gap-sm">
              <select v-model="filtersForm.wallet" class="form-input form-sm" @change="applyFilters">
                <option value="">Todas Billeteras</option>
                <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
              <select v-model="filtersForm.type" class="form-input form-sm" @change="applyFilters">
                <option value="">Tipos</option>
                <option value="ingreso">Ingreso (+)</option>
                <option value="gasto">Gasto (-)</option>
              </select>
              <select v-model="filtersForm.category" class="form-input form-sm" @change="applyFilters">
                <option value="">Todas Categorías</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <button @click="clearFilters" class="btn btn-ghost btn-sm" v-if="hasActiveFilters">✖</button>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-container">
              <table class="data-table w-full">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Monto</th>
                    <th>Responsable</th>
                    <th>Descripción</th>
                    <th class="text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="mov in movements.data" :key="mov.id">
                    <td>{{ mov.date }}</td>
                    <td>
                      <span class="badge" :class="mov.type === 'ingreso' ? 'bg-success/10 text-success' : 'bg-error/10 text-error'">
                        {{ mov.type === 'ingreso' ? 'Ingreso' : 'Gasto' }}
                      </span>
                    </td>
                    <td class="font-medium">{{ mov.category }}</td>
                    <td class="font-bold" :class="mov.type === 'ingreso' ? 'text-success' : 'text-error'">
                      {{ mov.type === 'ingreso' ? '+' : '-' }} ${{ formatAmount(mov.amount) }}
                    </td>
                    <td>
                      <div class="flex items-center gap-xs">
                        <span class="avatar-tiny">{{ mov.creator?.charAt(0) || 'S' }}</span>
                        {{ mov.creator }}
                      </div>
                    </td>
                    <td class="text-sm text-secondary">{{ mov.description || '-' }}</td>
                    <td class="text-right">
                      <div class="flex gap-xs justify-end">
                        <button @click="editMovement(mov)" class="btn btn-ghost btn-sm" title="Editar Movimiento">✎</button>
                        <button @click="confirmDelete(mov)" class="btn btn-error btn-sm" title="Deshacer Movimiento">Rollback</button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!movements.data.length">
                    <td colspan="7" class="text-center py-xl text-secondary">No se encontraron movimientos.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <PaginationLinks :links="movements.links" />
          </div>
        </div>
        
      </div>

      <!-- Movement Modal -->
      <div v-if="movementModalOpen" class="modal-overlay" @click.self="movementModalOpen = false">
        <div class="modal-content card stagger">
           <div class="card-header flex justify-between items-center">
             <h3 class="m-0">Registrar Nuevo Movimiento</h3>
             <button @click="movementModalOpen = false" class="btn btn-ghost p-xs">✕</button>
           </div>
           <div class="card-body">
             <form @submit.prevent="storeMovement" class="space-y-md">
               <div v-if="movementForm.errors.all" class="alert alert-error text-xs p-2 mb-2">
                 {{ movementForm.errors.all }}
               </div>
               
               <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                 <div class="form-group">
                   <label class="form-label">Caja / Fondo *</label>
                   <select v-model="movementForm.wallet_id" class="form-input" :class="{'border-error': movementForm.errors.wallet_id}" required>
                     <option value="" disabled>-- Seleccionar Billetera --</option>
                     <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }} (${{ formatAmount(w.balance) }})</option>
                   </select>
                   <span v-if="movementForm.errors.wallet_id" class="form-error">{{ movementForm.errors.wallet_id }}</span>
                 </div>
                 <div class="form-group">
                   <label class="form-label">Tipo de Movimiento *</label>
                   <select v-model="movementForm.type" class="form-input" :class="{'border-error': movementForm.errors.type}" required>
                     <option value="ingreso">Ingreso (+)</option>
                     <option value="gasto">Gasto (-)</option>
                   </select>
                   <span v-if="movementForm.errors.type" class="form-error">{{ movementForm.errors.type }}</span>
                 </div>
               </div>

               <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                 <div class="form-group">
                   <label class="form-label">Categoría *</label>
                   <select v-model="movementForm.category_id" class="form-input" :class="{'border-error': movementForm.errors.category_id}" required>
                     <option value="" disabled>-- Seleccionar Categoría --</option>
                     <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                   </select>
                   <span v-if="movementForm.errors.category_id" class="form-error">{{ movementForm.errors.category_id }}</span>
                 </div>
                 <div class="form-group">
                   <label class="form-label">Monto ($) *</label>
                   <input type="number" v-model="movementForm.amount" step="0.01" min="0.01" class="form-input" :class="{'border-error': movementForm.errors.amount}" placeholder="0.00" required />
                   <span v-if="movementForm.errors.amount" class="form-error">{{ movementForm.errors.amount }}</span>
                 </div>
               </div>

               <div class="form-group">
                 <label class="form-label">Descripción / Nota</label>
                 <textarea v-model="movementForm.description" class="form-input" rows="2" placeholder="Detalle opcional del movimiento..."></textarea>
                 <span v-if="movementForm.errors.description" class="form-error">{{ movementForm.errors.description }}</span>
               </div>

               <div class="modal-footer flex justify-end gap-sm mt-md">
                 <button type="button" @click="movementModalOpen = false" class="btn btn-ghost">Cancelar</button>
                 <button type="submit" class="btn btn-primary" :disabled="movementForm.processing">
                   {{ movementForm.processing ? 'Guardando...' : 'Registrar Movimiento' }}
                 </button>
               </div>
             </form>
           </div>
        </div>
      </div>

      <!-- Edit Movement Modal -->
      <div v-if="editModalOpen" class="modal-overlay" @click.self="editModalOpen = false">
        <div class="modal-content card stagger">
           <div class="card-header flex justify-between items-center">
             <h3 class="m-0">Editar Movimiento</h3>
             <button @click="editModalOpen = false" class="btn btn-ghost p-xs">✕</button>
           </div>
           <div class="card-body">
             <form @submit.prevent="updateMovement" class="space-y-md">
               <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                 <div class="form-group">
                   <label class="form-label">Categoría *</label>
                   <select v-model="editForm.category_id" class="form-input" required>
                     <option value="" disabled>-- Seleccionar --</option>
                     <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                   </select>
                 </div>
                 <div class="form-group">
                   <label class="form-label">Tipo *</label>
                   <select v-model="editForm.type" class="form-input" required>
                     <option value="ingreso">Ingreso (+)</option>
                     <option value="gasto">Gasto (-)</option>
                   </select>
                 </div>
               </div>
               <div class="form-group">
                 <label class="form-label">Monto ($) *</label>
                 <input type="number" v-model="editForm.amount" step="0.01" min="0.01" class="form-input" required />
               </div>
               <div class="form-group">
                 <label class="form-label">Descripción</label>
                 <textarea v-model="editForm.description" class="form-input" rows="2"></textarea>
               </div>
               <div class="modal-footer flex justify-end gap-sm mt-md">
                 <button type="button" @click="editModalOpen = false" class="btn btn-ghost">Cancelar</button>
                 <button type="submit" class="btn btn-primary" :disabled="editForm.processing">
                   {{ editForm.processing ? 'Guardando...' : 'Actualizar Movimiento' }}
                 </button>
               </div>
             </form>
           </div>
        </div>
      </div>

      <!-- Tab: Wallets -->
      <div v-if="currentTab === 'wallets'" class="stagger">
        <div class="grid-2 gap-lg">
          <!-- Create Wallet -->
          <div class="card">
            <div class="card-header">
              <h3>Crear Billetera / Fondo</h3>
            </div>
            <div class="card-body">
              <form @submit.prevent="submitWallet">
                <div class="form-group">
                  <label class="form-label">Nombre del Fondo</label>
                  <input type="text" v-model="walletForm.name" class="form-input" required placeholder="Ej: Caja Chica Santiago" />
                  <span v-if="walletForm.errors.name" class="form-error">{{ walletForm.errors.name }}</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Vincular a Blader (Opcional)</label>
                  <select v-model="walletForm.user_id" class="form-input">
                    <option value="">-- Sin usuario (Fondo Común) --</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.blader_name || u.name }}</option>
                  </select>
                  <span v-if="walletForm.errors.user_id" class="form-error">{{ walletForm.errors.user_id }}</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Balance Inicial ($)</label>
                  <input type="number" v-model="walletForm.balance" class="form-input" required />
                  <span v-if="walletForm.errors.balance" class="form-error">{{ walletForm.errors.balance }}</span>
                </div>
                <div class="flex gap-sm">
                  <button type="submit" class="btn btn-primary" :disabled="walletForm.processing">{{ editingWallet ? 'Actualizar' : 'Agregar' }}</button>
                  <button v-if="editingWallet" type="button" @click="cancelEditWallet" class="btn btn-ghost">Cancelar</button>
                </div>
              </form>
            </div>
          </div>
          <!-- Wallets list -->
          <div class="card">
            <div class="card-header">
              <h3>Fondos Disponibles</h3>
            </div>
            <div class="card-body p-0">
              <table class="data-table w-full">
                <thead><tr><th>Nombre</th><th class="text-right">Balance</th></tr></thead>
                <tbody>
                  <tr v-for="w in wallets" :key="w.id">
                    <td>
                      <div class="font-bold">{{ w.name }}</div>
                      <div v-if="w.user_id" class="text-xs text-secondary italic">Blader: {{ users.find(u => u.id === w.user_id)?.blader_name || 'Desconocido' }}</div>
                    </td>
                    <td class="text-right font-display text-lg">${{ formatAmount(w.balance) }}</td>
                    <td class="text-right">
                      <div class="flex gap-xs justify-end">
                        <button @click="editWallet(w)" class="btn btn-ghost btn-sm">✎</button>
                        <button @click="deleteWallet(w)" class="btn btn-error btn-sm">🗑</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Categories -->
      <div v-if="currentTab === 'categories'" class="stagger">
        <div class="grid-2 gap-lg">
          <!-- Create Category -->
          <div class="card">
            <div class="card-header">
              <h3>Crear Categoría</h3>
            </div>
            <div class="card-body">
              <form @submit.prevent="submitCategory">
                <div class="form-group">
                  <label class="form-label">Nombre de Categoría</label>
                  <input type="text" v-model="categoryForm.name" class="form-input" required placeholder="Ej: Viajes Internacionales" />
                  <span v-if="categoryForm.errors.name" class="form-error">{{ categoryForm.errors.name }}</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Descripción</label>
                  <input type="text" v-model="categoryForm.description" class="form-input" placeholder="Opcional" />
                </div>
                <div class="flex gap-sm">
                  <button type="submit" class="btn btn-primary" :disabled="categoryForm.processing">{{ editingCategory ? 'Actualizar' : 'Añadir' }}</button>
                  <button v-if="editingCategory" type="button" @click="cancelEditCategory" class="btn btn-ghost">Cancelar</button>
                </div>
              </form>
            </div>
          </div>
          <!-- Categories list -->
          <div class="card">
            <div class="card-header">
              <h3>Categorías Maestras</h3>
            </div>
            <div class="card-body p-0">
              <table class="data-table w-full">
                <thead><tr><th>Categoría</th><th>Descripción</th></tr></thead>
                <tbody>
                  <tr v-for="c in categories" :key="c.id">
                    <td class="font-bold border-l-4 border-primary">{{ c.name }}</td>
                    <td class="text-secondary text-sm">{{ c.description || '-' }}</td>
                    <td class="text-right">
                      <div class="flex gap-xs justify-end">
                        <button @click="editCategory(c)" class="btn btn-ghost btn-sm">✎</button>
                        <button @click="deleteCategory(c)" class="btn btn-error btn-sm">🗑</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';

const props = defineProps({
  wallets: Array,
  categories: Array,
  movements: Object,
  filters: Object,
  users: Array,
});

const currentTab = ref('movements');
const movementModalOpen = ref(false);

const openMovementModal = () => {
  movementModalOpen.value = true;
};

const filtersForm = ref({
  wallet: props.filters.wallet || '',
  type: props.filters.type || '',
  category: props.filters.category || '',
});

const hasActiveFilters = computed(() => {
  return filtersForm.value.wallet || filtersForm.value.type || filtersForm.value.category;
});

const walletForm = useForm({
  name: '',
  user_id: '',
  balance: 0,
});
const editingWallet = ref(null);

const categoryForm = useForm({
  name: '',
  description: '',
});
const editingCategory = ref(null);

const movementForm = useForm({
  wallet_id: props.wallets?.length > 0 ? props.wallets[0].id : '',
  category_id: props.categories?.length > 0 ? props.categories[0].id : '',
  type: 'ingreso',
  amount: '',
  description: '',
});

const { success: toastSuccess, error: toastError } = useToast();
const { ask } = useConfirm();

const formatAmount = (val) => new Intl.NumberFormat('es-CL').format(val);

const applyFilters = () => {
  router.get(route('admin.finance.index'), filtersForm.value, { preserveState: true, replace: true });
};

const clearFilters = () => {
  filtersForm.value.wallet = '';
  filtersForm.value.type = '';
  filtersForm.value.category = '';
  applyFilters();
};

const submitWallet = () => {
  if (editingWallet.value) {
    walletForm.put(route('admin.finance.wallet.update', editingWallet.value.id), {
      preserveScroll: true,
      onSuccess: () => cancelEditWallet(),
    });
  } else {
    walletForm.post(route('admin.finance.wallet.store'), {
      preserveScroll: true,
      onSuccess: () => walletForm.reset(),
    });
  }
};

const editWallet = (w) => {
  editingWallet.value = w;
  walletForm.name = w.name;
  walletForm.user_id = w.user_id || '';
  walletForm.balance = w.balance;
};

const cancelEditWallet = () => {
  editingWallet.value = null;
  walletForm.reset();
};

const deleteWallet = async (w) => {
  const confirmed = await ask({
    title: 'Eliminar fondo',
    message: `¿Eliminar fondo "${w.name}"?\nEsta acción fallará si tiene movimientos registrados.`,
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.finance.wallet.destroy', w.id), { preserveScroll: true });
};

const submitCategory = () => {
  if (editingCategory.value) {
    categoryForm.put(route('admin.finance.category.update', editingCategory.value.id), {
      preserveScroll: true,
      onSuccess: () => cancelEditCategory(),
    });
  } else {
    categoryForm.post(route('admin.finance.category.store'), {
      preserveScroll: true,
      onSuccess: () => categoryForm.reset(),
    });
  }
};

const editCategory = (c) => {
  editingCategory.value = c;
  categoryForm.name = c.name;
  categoryForm.description = c.description || '';
};

const cancelEditCategory = () => {
  editingCategory.value = null;
  categoryForm.reset();
};

const deleteCategory = async (c) => {
  const confirmed = await ask({
    title: 'Eliminar categoría',
    message: `¿Eliminar categoría "${c.name}"?`,
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;
  router.delete(route('admin.finance.category.destroy', c.id), { preserveScroll: true });
};

const storeMovement = () => {
  movementForm.post(route('admin.finance.movement.store'), {
    preserveScroll: true,
    onSuccess: () => {
      toastSuccess('Movimiento registrado exitosamente.');
      movementForm.reset('amount', 'description');
      movementModalOpen.value = false;
    },
    onError: (errors) => {
      toastError(Object.values(errors)[0] || 'Error al registrar el movimiento.');
    }
  });
};

const confirmDelete = async (mov) => {
  const confirmed = await ask({
    title: 'Rollback de movimiento',
    message: `Al hacer rollback se eliminará el movimiento y se restaurará el saldo original.\n\nMonto: ${formatAmount(mov.amount)}\n\n¿Deseas continuar?`,
    confirmText: 'Sí, deshacer',
  });

  if (!confirmed) return;
  router.delete(route('admin.finance.movement.destroy', mov.id), { preserveScroll: true });
};

const editModalOpen = ref(false);
const editingMovement = ref(null);

const editForm = useForm({
  category_id: '',
  type: 'ingreso',
  amount: '',
  description: '',
});

const editMovement = (mov) => {
  editingMovement.value = mov;
  editForm.category_id = mov.category_id || '';
  editForm.type = mov.type;
  editForm.amount = mov.amount;
  editForm.description = mov.description || '';
  editModalOpen.value = true;
};

const updateMovement = () => {
  editForm.put(route('admin.finance.movement.update', editingMovement.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      toastSuccess('Movimiento actualizado correctamente.');
      editModalOpen.value = false;
      editingMovement.value = null;
    },
    onError: (errors) => {
      toastError(Object.values(errors)[0] || 'Error al actualizar.');
    }
  });
};
</script>

<style scoped>
.grid-2 {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-lg);
}

@media (min-width: 768px) {
  .grid-2 {
    grid-template-columns: repeat(2, 1fr);
  }
}

.data-table { border-collapse: collapse; }
.data-table th, .data-table td {
  padding: var(--space-md);
  border-bottom: 1px solid var(--border-color);
  text-align: left;
}
.data-table th {
  background: rgba(0,0,0,0.02);
  color: var(--text-secondary);
  font-weight: 600;
  font-size: 0.9rem;
}

.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: bold;
  text-transform: uppercase;
  background: var(--bg-elevated);
}

.bg-success\/10 { background: rgba(16, 185, 129, 0.1); }
.text-success { color: #10b981; }

.bg-error\/10 { background: rgba(225, 6, 0, 0.1); }
.text-error { color: var(--gx-red); }

.avatar-tiny {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--border-color);
  font-size: 0.7rem;
  font-weight: bold;
}

.form-sm {
  padding: 4px 8px;
  font-size: 0.85rem;
  height: 32px;
}

.pagination-link {
  padding: 6px 12px;
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

.btn-error {
  background: rgba(225, 6, 0, 0.1);
  color: var(--gx-red);
  border: 1px solid rgba(225, 6, 0, 0.2);
}
.btn-error:hover {
  background: var(--gx-red);
  color: white;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  padding: var(--space-md);
}

.modal-content {
  width: 100%;
  max-width: 500px;
}
</style>


