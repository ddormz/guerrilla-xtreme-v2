<template>
  <AppLayout>
    <Head title="Finanzas" />

    <div class="finance-page page-content">
      <div class="page-header mb-xl animate-fade-in-up">
        <h1 class="text-gradient">Tesorería GX</h1>
        <p class="text-secondary">Transparencia y control de los fondos de la comunidad.</p>
      </div>

      <!-- Wallets Grid -->
      <div class="wallets-grid stagger mb-2xl">
        <div v-for="wallet in wallets" :key="wallet.id" class="wallet-card card">
          <div class="wallet-icon">💰</div>
          <div class="wallet-info">
            <span class="wallet-label">{{ wallet.name }}</span>
            <h2 class="wallet-balance">${{ formatAmount(wallet.balance) }}</h2>
          </div>
        </div>
      </div>

      <div class="finance-layout">
        <!-- Movements List -->
        <div class="movements-section">
          <div class="section-header mb-lg">
            <h2>Movimientos Recientes</h2>
          </div>
          
          <div class="card p-0 overflow-hidden stagger">
            <div class="table-container">
              <table class="gx-table">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th class="text-right">Monto</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in movements" :key="m.id" class="table-row">
                    <td class="text-secondary text-sm">{{ m.date }}</td>
                    <td>
                      <span class="badge" :class="m.type === 'ingreso' ? 'badge-green' : 'badge-red'">
                        {{ m.type }}
                      </span>
                    </td>
                    <td class="font-medium">{{ m.category }}</td>
                    <td class="text-secondary text-sm">{{ m.description }}</td>
                    <td class="text-right font-bold" :class="m.type === 'ingreso' ? 'text-accent-green' : 'text-gx-red-light'">
                      {{ m.type === 'ingreso' ? '+' : '-' }}${{ formatAmount(m.amount) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div v-if="movements.length === 0" class="p-2xl text-center text-muted">
              No hay movimientos registrados.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  wallets: Array,
  categories: Array,
  movements: Array
});

const formatAmount = (val) => {
  return new Intl.NumberFormat('es-CL', { minimumFractionDigits: 0 }).format(val);
};
</script>

<style scoped>
.wallets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: var(--space-lg);
}

.wallet-card {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
  border-left: 4px solid var(--accent-green);
}

.wallet-icon {
  font-size: 2rem;
  background: rgba(255, 255, 255, 0.05);
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
}

.wallet-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.wallet-balance {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 700;
  margin-top: -4px;
}

.finance-layout {
  display: flex;
  flex-direction: column;
  gap: var(--space-xl);
}

.gx-table {
  width: 100%;
  border-collapse: collapse;
}

.gx-table th {
  padding: var(--space-md);
  text-align: left;
  font-family: var(--font-display);
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border-color);
}

.gx-table td {
  padding: var(--space-md);
  border-bottom: 1px solid var(--border-color);
}

.text-accent-green { color: var(--accent-green); }
</style>
