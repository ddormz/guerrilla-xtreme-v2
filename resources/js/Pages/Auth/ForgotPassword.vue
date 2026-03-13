<template>
  <AppLayout>
    <Head title="Recuperar Contraseña" />

    <div class="auth-page page-content flex items-center justify-center">
      <div class="auth-card card">
        <div class="text-center mb-xl">
          <h1 class="text-gradient">Recuperar Contraseña</h1>
          <p class="text-secondary">
            Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
          </p>
        </div>

        <form @submit.prevent="submit" class="auth-form">
          <div class="form-group">
            <label for="email" class="form-label">Correo electrónico</label>
            <input
              id="email"
              type="email"
              v-model="form.email"
              class="form-input"
              required
              autofocus
              autocomplete="email"
            />
            <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="form.processing">
            {{ form.processing ? 'Enviando...' : 'Enviar enlace de recuperación' }}
          </button>
        </form>

        <div class="text-center mt-lg">
          <Link :href="route('login')" class="btn btn-ghost btn-sm">Volver al login</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('password.email'));
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

