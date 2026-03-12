<template>
  <div class="toast-container">
    <TransitionGroup name="toast-fade">
      <div v-for="toast in toasts" :key="toast.id" 
           class="toast-item" :class="`toast-${toast.type}`"
           @click="remove(toast.id)">
        <div class="toast-icon">
          <span v-if="toast.type === 'success'">✅</span>
          <span v-else-if="toast.type === 'error'">❌</span>
          <span v-else-if="toast.type === 'warning'">⚠️</span>
          <span v-else>ℹ️</span>
        </div>
        <div class="toast-content">{{ toast.message }}</div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '@/Composables/useToast';
const { toasts, remove } = useToast();
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.toast-item {
  pointer-events: auto;
  min-width: 280px;
  max-width: 400px;
  background: var(--bg-elevated, #1a1a1a);
  border: 1px solid var(--glass-border, rgba(255,255,255,0.1));
  backdrop-filter: blur(12px);
  padding: 12px 16px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  cursor: pointer;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.toast-item:hover {
  transform: translateY(-2px);
}

.toast-success { border-left: 4px solid #10b981; }
.toast-error { border-left: 4px solid #ef4444; }
.toast-warning { border-left: 4px solid #f59e0b; }
.toast-info { border-left: 4px solid #3b82f6; }

.toast-icon { font-size: 1.2rem; }
.toast-content { font-size: 0.95rem; font-weight: 500; color: white; }

/* Animations */
.toast-fade-enter-from { opacity: 0; transform: translateX(30px); }
.toast-fade-enter-to { opacity: 1; transform: translateX(0); }
.toast-fade-leave-from { opacity: 1; transform: scale(1); }
.toast-fade-leave-to { opacity: 0; transform: scale(0.9); }
</style>
