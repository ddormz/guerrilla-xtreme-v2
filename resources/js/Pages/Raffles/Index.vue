<template>
  <AppLayout>
    <Head title="Rifas" />

    <div class="raffles-index page-content">
      <div class="page-header mb-xl animate-fade-in-up">
        <h1 class="text-gradient">Rifas Disponibles</h1>
        <p class="text-secondary">Participa y apoya a la comunidad para ganar increíbles premios.</p>
      </div>

      <div class="raffles-warning card mb-lg">
        <p>
          Esta sección se encuentra en reconstrucción, pronto se publicarán las fotos de los ganadores anteriores,
          ante cualquier duda, pueden revisar nuestro instagram o hablarnos al dm.
        </p>
      </div>

      <div v-if="raffles.length > 0" class="raffles-grid stagger">
        <div v-for="raffle in raffles" :key="raffle.id" class="raffle-card card card-hover">
          <div class="raffle-status-badge">
            <span class="badge" :class="getStatusClass(raffle.status)">{{ raffle.status_label }}</span>
          </div>
          
          <div class="raffle-info">
            <h2 class="raffle-name mb-xs">{{ raffle.name }}</h2>
            <p class="raffle-description text-secondary mb-md">{{ truncate(raffle.description, 100) }}</p>
            
            <div class="raffle-meta mb-lg">
              <div class="meta-item">
                <span class="meta-label">Precio:</span>
                <span class="meta-value text-gradient">${{ formatPrice(raffle.ticket_price) }}</span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Sorteo:</span>
                <span class="meta-value">{{ raffle.draw_at || 'Por definir' }}</span>
              </div>
            </div>
          </div>

          <div class="raffle-actions">
            <Link :href="route('raffles.show', raffle.slug)" class="btn btn-primary btn-block">
              {{ raffle.status === 'published' ? 'Ver Números' : 'Ver Detalles' }}
            </Link>
          </div>
        </div>
      </div>

      <div v-else class="empty-state card text-center p-2xl">
        <div class="empty-icon text-4xl mb-md">🎟️</div>
        <h3>No hay rifas activas</h3>
        <p class="text-secondary">Vuelve pronto para participar en nuestros próximos sorteos.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  raffles: Array
});

const getStatusClass = (status) => {
  switch (status) {
    case 'published': return 'badge-green';
    case 'closed': return 'badge-amber';
    case 'drawn': return 'badge-blue';
    default: return 'badge-red';
  }
};

const truncate = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

const formatPrice = (price) => {
  return new Intl.NumberFormat('es-CL').format(price);
};
</script>

<style scoped>
.raffles-warning {
  border-left: 4px solid #f59e0b;
  background: rgba(245, 158, 11, 0.08);
  color: #fde68a;
}

.raffles-warning p {
  margin: 0;
  font-size: 0.95rem;
  line-height: 1.5;
}

.raffles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--space-xl);
}

.raffle-card {
  display: flex;
  flex-direction: column;
  position: relative;
}

.raffle-status-badge {
  position: absolute;
  top: var(--space-md);
  right: var(--space-md);
}

.raffle-name {
  font-size: 1.5rem;
  padding-right: 80px;
}

.raffle-meta {
  display: flex;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.03);
  padding: var(--space-md);
  border-radius: var(--radius-md);
}

.meta-item {
  display: flex;
  flex-direction: column;
}

.meta-label {
  font-size: 0.7rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.meta-value {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.1rem;
}

.raffle-actions {
  margin-top: auto;
}
</style>
