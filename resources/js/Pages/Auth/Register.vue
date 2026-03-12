<template>
  <AppLayout>
    <Head title="Registro de Blader" />

    <div class="auth-page page-content flex items-center justify-center">
      <div class="auth-card card stagger">
        <div class="text-center mb-xl">
          <Link :href="route('home')" class="auth-logo mb-md inline-block">
            <span class="logo-icon text-3xl">⚡</span>
          </Link>
          <h1 class="text-gradient">Únete a la Guerrilla</h1>
          <p class="text-secondary">Crea tu cuenta de blader para participar</p>
        </div>

        <form @submit.prevent="submit" class="auth-form">
          <div class="form-row">
            <div class="form-group flex-1">
              <label for="name" class="form-label">Nombre Real</label>
              <input
                id="name"
                type="text"
                v-model="form.name"
                class="form-input"
                placeholder="Juan Pérez"
                required
                autocomplete="name"
              />
              <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
            </div>

            <div class="form-group flex-1">
              <label for="blader_name" class="form-label">Blader Name (Nick)</label>
              <input
                id="blader_name"
                type="text"
                v-model="form.blader_name"
                class="form-input"
                placeholder="X-TremeBlader"
                autocomplete="nickname"
              />
              <span v-if="form.errors.blader_name" class="form-error">{{ form.errors.blader_name }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input
              id="email"
              type="email"
              v-model="form.email"
              class="form-input"
              placeholder="blader@ejemplo.com"
              required
              autocomplete="email"
            />
            <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
          </div>

          <div class="form-group">
            <label for="phone" class="form-label">WhatsApp (para coordinación)</label>
            <input
              id="phone"
              type="tel"
              v-model="form.phone"
              class="form-input"
              placeholder="+569 1234 5678"
              required
              autocomplete="tel"
            />
            <span v-if="form.errors.phone" class="form-error">{{ form.errors.phone }}</span>
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Contraseña</label>
            <input
              id="password"
              type="password"
              v-model="form.password"
              class="form-input"
              placeholder="Min. 8 caracteres"
              required
              autocomplete="new-password"
            />
            <span v-if="form.errors.password" class="form-error">{{ form.errors.password }}</span>
          </div>

          <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
            <input
              id="password_confirmation"
              type="password"
              v-model="form.password_confirmation"
              class="form-input"
              placeholder="Repite tu contraseña"
              required
              autocomplete="new-password"
            />
          </div>

          <div class="mb-lg mt-md text-sm text-secondary">
            Al registrarte, confirmas que has leído y aceptas nuestra política de privacidad.
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="form.processing">
            {{ form.processing ? 'Registrando...' : 'Crear Cuenta' }}
          </button>
        </form>

        <div class="auth-footer mt-xl text-center">
          <p class="text-secondary">¿Ya tienes una cuenta?</p>
          <Link :href="route('login')" class="btn btn-ghost btn-sm mt-sm">Inicia Sesión Aquí</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
  name: '',
  blader_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - var(--header-height) - var(--bottom-nav-height));
  padding-top: var(--space-xl);
  padding-bottom: var(--space-xl);
}

.auth-card {
  width: 100%;
  max-width: 500px;
  background: var(--bg-card);
  padding: var(--space-xl);
}

.auth-form {
  margin-top: var(--space-lg);
}

.form-row {
  display: flex;
  gap: var(--space-md);
  margin-bottom: var(--space-md);
}

@media (max-width: 600px) {
  .form-row {
    flex-direction: column;
    gap: 0;
  }
}

@media (max-width: 480px) {
  .auth-card {
    background: transparent;
    border: none;
    padding: var(--space-md);
  }
}
</style>
