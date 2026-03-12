<template>
  <component :is="isButton ? 'button' : Link" :href="href || undefined" class="bottom-nav-item" :class="{ active }" @click="$emit('click')">
    <span class="bottom-nav-icon">{{ icon }}</span>
    <span class="bottom-nav-label">{{ label }}</span>
  </component>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ icon: String, label: String, href: String, active: Boolean, isButton: Boolean });
defineEmits(['click']);
</script>

<style scoped>
.bottom-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  padding: 6px 12px;
  color: var(--text-muted);
  text-decoration: none;
  border: none;
  background: none;
  cursor: pointer;
  min-width: 52px;
  min-height: 44px;
  transition: color var(--transition-fast);
}

.bottom-nav-item.active {
  color: var(--gx-red-light);
}

.bottom-nav-icon {
  font-size: 1.3rem;
  line-height: 1;
}

.bottom-nav-label {
  font-size: 0.6rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
</style>
