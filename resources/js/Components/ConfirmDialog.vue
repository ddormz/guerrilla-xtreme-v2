<template>
  <Teleport to="body">
    <Transition name="confirm-fade">
      <div v-if="confirmState.isOpen" class="confirm-overlay" @click.self="cancel">
        <div class="confirm-card card" role="dialog" aria-modal="true" aria-label="Confirmacion">
          <h3 class="confirm-title">{{ confirmState.title }}</h3>
          <p class="confirm-message">{{ confirmState.message }}</p>
          <div class="confirm-actions">
            <button type="button" class="btn btn-ghost" @click="cancel">
              {{ confirmState.cancelText }}
            </button>
            <button
              type="button"
              class="btn"
              :class="confirmState.tone === 'primary' ? 'btn-primary' : 'btn-danger-soft'"
              @click="confirm"
            >
              {{ confirmState.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { useConfirm } from '@/Composables/useConfirm';

const { confirmState, confirm, cancel } = useConfirm();
</script>

<style scoped>
.confirm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(5px);
  display: grid;
  place-items: center;
  z-index: calc(var(--z-modal) + 5);
  padding: var(--space-md);
}

.confirm-card {
  width: min(460px, 100%);
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.confirm-title {
  margin-bottom: var(--space-sm);
  font-size: 1rem;
}

.confirm-message {
  color: var(--text-secondary);
  font-size: 0.92rem;
  white-space: pre-line;
  margin-bottom: var(--space-lg);
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-sm);
}

.btn-danger-soft {
  background: rgba(225, 6, 0, 0.15);
  color: var(--gx-red-light);
  border: 1px solid rgba(225, 6, 0, 0.35);
}

.btn-danger-soft:hover {
  background: rgba(225, 6, 0, 0.28);
}

.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity var(--transition-base);
}

.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}
</style>
