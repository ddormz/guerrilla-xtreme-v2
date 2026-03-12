<template>
  <AppLayout>
    <Head title="Iniciar Sesión" />

    <div class="auth-page page-content flex items-center justify-center">
      <div class="auth-card card stagger">
        <div class="text-center mb-xl">
          <Link :href="route('home')" class="auth-logo mb-md inline-block">
            <span class="logo-icon text-3xl">⚡</span>
          </Link>
          <h1 class="text-gradient">Bienvenido de Vuelta</h1>
          <p class="text-secondary">Ingresa tus credenciales para continuar</p>
        </div>

        <form @submit.prevent="submit" class="auth-form">
          <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input
              id="email"
              type="email"
              v-model="form.email"
              class="form-input"
              placeholder="correo@ejemplo.com"
              required
              autofocus
              autocomplete="username"
            />
            <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Contraseña</label>
            <input
              id="password"
              type="password"
              v-model="form.password"
              class="form-input"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            />
            <span v-if="form.errors.password" class="form-error">{{ form.errors.password }}</span>
          </div>

          <div class="form-helper mb-lg">
            <label class="checkbox-container">
              <input type="checkbox" v-model="form.remember" />
              <span class="checkmark"></span>
              Recordarme
            </label>
            <Link href="/forgot-password" class="forgot-link">¿Olvidaste tu contraseña?</Link>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="form.processing">
            {{ form.processing ? 'Iniciando...' : 'Iniciar Sesión' }}
          </button>
        </form>

        <div class="auth-footer mt-xl text-center">
          <p class="text-secondary">¿No tienes una cuenta?</p>
          <Link :href="route('register')" class="btn btn-ghost btn-sm mt-sm">Regístrate Aquí</Link>
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
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - var(--header-height) - var(--bottom-nav-height));
  display: flex;
  align-items: center;
  justify-content: center;
}

.auth-card {
  width: 100%;
  max-width: 440px;
  background: var(--bg-card);
  padding: var(--space-xl);
}

.auth-form {
  margin-top: var(--space-lg);
}

.form-helper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.85rem;
}

/* Custom Checkbox */
.checkbox-container {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  color: var(--text-secondary);
  user-select: none;
}

.checkbox-container input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.checkmark {
  height: 18px;
  width: 18px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 4px;
}

.checkbox-container:hover input ~ .checkmark {
  border-color: var(--gx-red);
}

.checkbox-container input:checked ~ .checkmark {
  background: var(--gx-red);
  border-color: var(--gx-red);
}

.checkpoint-marker {
  display: none;
}

.forgot-link {
  font-weight: 500;
  color: var(--text-secondary);
}

.forgot-link:hover {
  color: var(--gx-red-light);
}

@media (max-width: 480px) {
  .auth-card {
    background: transparent;
    border: none;
    padding: var(--space-md);
  }
}
</style>
