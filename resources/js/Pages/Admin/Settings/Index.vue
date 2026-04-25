<template>
  <AppLayout>
    <Head title="Configuración" />

    <div class="page-content">
      <nav class="mb-lg">
        <Link :href="route('admin.dashboard')" class="btn btn-ghost btn-sm">← Dashboard</Link>
      </nav>

      <div class="page-header mb-xl">
        <h1 class="text-gradient">Configuración de la Plataforma</h1>
      </div>

      <div class="card max-w-2xl">
        <h2 class="mb-md">Módulos Públicos</h2>
        <p class="text-secondary text-sm mb-lg">Habilita o deshabilita la visibilidad de los módulos para los usuarios normales.</p>

        <form @submit.prevent="submit">
          <div class="space-y-md">
            <label class="flex items-center gap-sm p-sm rounded hover:bg-white/5 cursor-pointer">
              <input type="checkbox" v-model="form.modules.rifas" class="form-checkbox" />
              <div>
                <div class="font-bold">Módulo de Rifas</div>
                <div class="text-xs text-secondary">Permite a los usuarios ver y comprar rifas.</div>
              </div>
            </label>

            <label class="flex items-center gap-sm p-sm rounded hover:bg-white/5 cursor-pointer">
              <input type="checkbox" v-model="form.modules.torneos" class="form-checkbox" />
              <div>
                <div class="font-bold">Módulo de Torneos</div>
                <div class="text-xs text-secondary">Permite a los usuarios ver y registrarse en torneos.</div>
              </div>
            </label>
          </div>

          <div class="mt-lg pt-md border-t border-white/10 flex justify-end">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              Guardar Configuración
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  settings: Object,
});

const form = useForm({
  modules: {
    rifas: props.settings?.modules?.rifas ?? true,
    torneos: props.settings?.modules?.torneos ?? true,
  }
});

const submit = () => {
  form.post(route('admin.settings.update'));
};
</script>
