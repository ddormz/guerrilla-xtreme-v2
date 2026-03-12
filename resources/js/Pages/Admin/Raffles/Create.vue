<template>
  <AppLayout>
    <Head title="Crear Rifa" />

    <div class="admin-raffle-create page-content">
      <nav class="mb-lg">
        <Link :href="route('admin.raffles.index')" class="btn btn-ghost btn-sm">← Volver a Rifas</Link>
      </nav>

      <div class="page-header mb-xl">
        <h1 class="text-gradient">Nueva Rifa</h1>
        <p class="text-secondary mt-xs">Completa toda la configuración para dejar la rifa lista para publicar.</p>
      </div>

      <form @submit.prevent="createRaffle" class="space-y-lg">
        <section class="card section-card">
          <h2 class="section-title">Información general</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            <div class="form-group md:col-span-2">
              <label class="form-label">Nombre de la Rifa *</label>
              <input type="text" v-model="form.name" class="form-input" placeholder="Ej: Rifa Proyectil GX" required />
              <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
            </div>

            <div class="form-group md:col-span-2">
              <label class="form-label">Descripción *</label>
              <textarea v-model="form.description" class="form-input" rows="3" placeholder="Detalles generales de la rifa..." required></textarea>
              <span v-if="form.errors.description" class="form-error">{{ form.errors.description }}</span>
            </div>

            <div class="form-group md:col-span-2">
              <label class="form-label">Bases / Reglas</label>
              <textarea v-model="form.rules" class="form-input" rows="3" placeholder="Reglas del sorteo y condiciones..."></textarea>
              <span v-if="form.errors.rules" class="form-error">{{ form.errors.rules }}</span>
            </div>
          </div>
        </section>

        <section class="card section-card">
          <h2 class="section-title">Configuración de números y fechas</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-md">
            <div class="form-group">
              <label class="form-label">Precio por Ticket ($) *</label>
              <input type="number" v-model="form.ticket_price" class="form-input" min="1" step="1" required />
              <span v-if="form.errors.ticket_price" class="form-error">{{ form.errors.ticket_price }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Total de números *</label>
              <input type="number" v-model="form.total_numbers" class="form-input" min="1" required />
              <span v-if="form.errors.total_numbers" class="form-error">{{ form.errors.total_numbers }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Inicio de ventas</label>
              <input type="datetime-local" v-model="form.sales_start_at" class="form-input" />
              <span v-if="form.errors.sales_start_at" class="form-error">{{ form.errors.sales_start_at }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Fin de ventas</label>
              <input type="datetime-local" v-model="form.sales_end_at" class="form-input" />
              <span v-if="form.errors.sales_end_at" class="form-error">{{ form.errors.sales_end_at }}</span>
            </div>

            <div class="form-group lg:col-span-2">
              <label class="form-label">Fecha del sorteo</label>
              <input type="datetime-local" v-model="form.draw_at" class="form-input" />
              <span v-if="form.errors.draw_at" class="form-error">{{ form.errors.draw_at }}</span>
            </div>
          </div>
        </section>

        <section class="card section-card">
          <h2 class="section-title">Información de pago</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            <div class="form-group">
              <label class="form-label">Banco</label>
              <input type="text" v-model="form.bank_name" class="form-input" placeholder="Ej: BancoEstado" />
              <span v-if="form.errors.bank_name" class="form-error">{{ form.errors.bank_name }}</span>
            </div>
            <div class="form-group">
              <label class="form-label">Titular</label>
              <input type="text" v-model="form.account_holder" class="form-input" placeholder="Nombre del titular" />
              <span v-if="form.errors.account_holder" class="form-error">{{ form.errors.account_holder }}</span>
            </div>
            <div class="form-group">
              <label class="form-label">Número de cuenta</label>
              <input type="text" v-model="form.account_number" class="form-input" placeholder="Cuenta vista / rut" />
              <span v-if="form.errors.account_number" class="form-error">{{ form.errors.account_number }}</span>
            </div>
            <div class="form-group">
              <label class="form-label">Tipo de cuenta</label>
              <input type="text" v-model="form.account_type" class="form-input" placeholder="Ej: Cuenta Vista" />
              <span v-if="form.errors.account_type" class="form-error">{{ form.errors.account_type }}</span>
            </div>
            <div class="form-group">
              <label class="form-label">Correo de pago</label>
              <input type="email" v-model="form.account_email" class="form-input" placeholder="correo@ejemplo.com" />
              <span v-if="form.errors.account_email" class="form-error">{{ form.errors.account_email }}</span>
            </div>
            <div class="form-group md:col-span-2">
              <label class="form-label">Instrucciones adicionales</label>
              <textarea v-model="form.payment_instructions" class="form-input" rows="2" placeholder="Ej: enviar comprobante por WhatsApp."></textarea>
              <span v-if="form.errors.payment_instructions" class="form-error">{{ form.errors.payment_instructions }}</span>
            </div>
          </div>
        </section>

        <section class="card section-card">
          <h2 class="section-title">Premios (con imagen)</h2>
          <div class="prizes-section">
            <div class="flex justify-between items-center mb-md">
              <p class="text-secondary text-sm">Configura los premios en orden de posición.</p>
              <button type="button" @click="addPrize" class="btn btn-ghost btn-xs">+ Añadir Premio</button>
            </div>

            <div v-for="(prize, index) in form.prizes" :key="index" class="prize-item">
              <button type="button" @click="removePrize(index)" class="remove-prize">✕</button>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-sm">
                <div class="form-group">
                  <label class="form-label">Posición *</label>
                  <input type="number" v-model="prize.position" class="form-input" min="1" required />
                </div>
                <div class="form-group md:col-span-3">
                  <label class="form-label">Título *</label>
                  <input type="text" v-model="prize.title" class="form-input" placeholder="Ej: Beyblade X" required />
                </div>
                <div class="form-group md:col-span-2">
                  <label class="form-label">Descripción</label>
                  <input type="text" v-model="prize.description" class="form-input" placeholder="Descripción breve del premio" />
                </div>
                <div class="form-group md:col-span-2">
                  <label class="form-label">Imagen del premio</label>
                  <input type="file" class="form-input" accept="image/*" @change="onPrizeImageChange(index, $event)" />
                </div>
              </div>
            </div>
          </div>
        </section>

        <div class="flex justify-end gap-md pt-md border-t border-white/10">
          <Link :href="route('admin.raffles.index')" class="btn btn-ghost">Cancelar</Link>
          <button type="submit" class="btn btn-primary" :disabled="form.processing">Crear Rifa</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
  name: '',
  description: '',
  rules: '',
  ticket_price: '',
  total_numbers: 100,
  sales_start_at: '',
  sales_end_at: '',
  draw_at: '',
  bank_name: '',
  account_holder: '',
  account_number: '',
  account_type: '',
  account_email: '',
  payment_instructions: '',
  prizes: [
    { position: 1, title: '', description: '', image: null },
  ],
});

const addPrize = () => {
  form.prizes.push({ position: form.prizes.length + 1, title: '', description: '', image: null });
};

const removePrize = (index) => {
  form.prizes.splice(index, 1);
};

const onPrizeImageChange = (index, event) => {
  const file = event.target.files?.[0] || null;
  form.prizes[index].image = file;
};

const createRaffle = () => {
  form.post(route('admin.raffles.store'), {
    forceFormData: true,
  });
};
</script>

<style scoped>
.section-card {
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.section-title {
  margin-bottom: var(--space-md);
  font-size: 1rem;
  color: var(--accent-blue);
}

.prize-item {
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  margin-bottom: var(--space-sm);
  background: var(--bg-secondary);
}

.remove-prize {
  position: absolute;
  top: 6px;
  right: 8px;
  background: transparent;
  color: var(--gx-red-light);
  border: none;
  cursor: pointer;
  font-size: 0.9rem;
}
</style>
