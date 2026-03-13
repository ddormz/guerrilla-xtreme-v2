<template>
  <AppLayout>
    <Head title="Restablecer Contraseña" />

    <div class="auth-page page-content flex items-center justify-center">
      <div class="auth-card card">
        <div class="text-center mb-xl">
          <h1 class="text-gradient">Nueva Contraseña</h1>
          <p class="text-secondary">
            Define una nueva contraseña segura para tu cuenta.
          </p>
        </div>

        <form @submit.prevent="submit" class="auth-form">
          <input type="hidden" v-model="form.token" />

          <div class="form-group">
            <label for="email" class="form-label">Correo electrónico</label>
            <input
              id="email"
              type="email"
              v-model="form.email"
              class="form-input"
              required
              autocomplete="email"
            />
            <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Nueva contraseña</label>
            <input
              id="password"
              type="password"
              v-model="form.password"
              class="form-input"
              required
              autocomplete="new-password"
            />
            <span v-if="form.errors.password" class="form-error">{{ form.errors.password }}</span>
          </div>

          <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
            <input
              id="password_confirmation"
              type="password"
              v-model="form.password_confirmation"
              class="form-input"
              required
              autocomplete="new-password"
            />
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="form.processing">
            {{ form.processing ? 'Actualizando...' : 'Actualizar contraseña' }}
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  token: String,
  email: String,
});

const form = useForm({
  token: props.token || '',
  email: props.email || '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => {
      form.reset('password', 'password_confirmation');
    },
  });
};
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - var(--header-height) - var(--bottom-nav-height));
}

.auth-card {
  width: 100%;
  max-width: 460px;
}
</style>

