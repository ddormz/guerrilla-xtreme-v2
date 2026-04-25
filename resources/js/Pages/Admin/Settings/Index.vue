<template>
  <AppLayout>
    <Head title="Configuración" />

    <div class="page-content">
      <nav class="mb-lg">
        <Link href="/admin/ligas" class="btn btn-ghost btn-sm">← Volver al Admin</Link>
      </nav>

      <div class="page-header mb-xl">
        <h1 class="text-gradient">⚙️ Configuración del Sitio</h1>
      </div>

      <!-- Module Visibility Toggles -->
      <section class="card stagger">
        <h2 class="mb-md">Visibilidad de Módulos</h2>
        <p class="text-secondary text-sm mb-lg">Activa o desactiva módulos para los usuarios normales. Los administradores siempre tienen acceso completo.</p>

        <form @submit.prevent="saveModules">
          <div class="module-grid">
            <div v-for="mod in moduleList" :key="mod.key" class="module-card" :class="{ 'module-disabled': !form[mod.key] }">
              <div class="module-header">
                <span class="module-icon">{{ mod.icon }}</span>
                <div class="module-info">
                  <h3 class="module-name">{{ mod.label }}</h3>
                  <p class="module-desc text-sm text-secondary">{{ mod.description }}</p>
                </div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" v-model="form[mod.key]" />
                <span class="toggle-slider"></span>
                <span class="toggle-label">{{ form[mod.key] ? 'Activo' : 'Inactivo' }}</span>
              </label>
            </div>
          </div>

          <div class="mt-xl flex justify-end">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              Guardar Cambios
            </button>
          </div>
        </form>
      </section>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  modules: Object,
});

const form = useForm({
  rifas: props.modules?.rifas ?? true,
  torneos: props.modules?.torneos ?? true,
  liga: props.modules?.liga ?? true,
  ranking: props.modules?.ranking ?? true,
});

const moduleList = [
  { key: 'rifas', label: 'Rifas', icon: '🎟️', description: 'Sección de rifas y sorteos visibles para todos los usuarios.' },
  { key: 'torneos', label: 'Torneos', icon: '⚔️', description: 'Inscripción y visualización de torneos competitivos.' },
  { key: 'liga', label: 'Liga', icon: '🏆', description: 'Tabla de posiciones y estadísticas de la Liga Xtreme.' },
  { key: 'ranking', label: 'Ranking Global', icon: '🏅', description: 'Ranking basado en diferencia de puntos de Torneo+Ranking.' },
];

const saveModules = () => {
  form.post(route('admin.settings.modules.update'), {
    preserveScroll: true,
  });
};
</script>

<style scoped>
.module-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: var(--space-lg);
}

.module-card {
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--space-lg);
  background: rgba(255, 255, 255, 0.02);
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  transition: all var(--transition-normal);
}

.module-card:hover {
  border-color: var(--gx-red);
}

.module-disabled {
  opacity: 0.5;
  border-color: rgba(239, 68, 68, 0.3);
}

.module-header {
  display: flex;
  align-items: flex-start;
  gap: var(--space-md);
}

.module-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.module-name {
  margin: 0;
  font-size: 1rem;
}

.module-desc {
  margin: 4px 0 0;
}

/* Toggle Switch */
.toggle-switch {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  cursor: pointer;
  margin-top: auto;
}

.toggle-switch input {
  display: none;
}

.toggle-slider {
  position: relative;
  width: 48px;
  height: 26px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 26px;
  transition: all var(--transition-fast);
  flex-shrink: 0;
}

.toggle-slider::after {
  content: '';
  position: absolute;
  top: 3px;
  left: 3px;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  transition: transform var(--transition-fast);
}

.toggle-switch input:checked + .toggle-slider {
  background: var(--gx-red);
}

.toggle-switch input:checked + .toggle-slider::after {
  transform: translateX(22px);
}

.toggle-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
}
</style>
