<template>
  <Link :href="href" class="sidebar-link" :class="{ active }" @click="$emit('nav')">
    <span class="sidebar-link-icon">{{ icon }}</span>
    <span class="sidebar-link-label">{{ label }}</span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ icon: String, label: String, href: String, active: Boolean });
defineEmits(['nav']);
</script>

<style scoped>
.sidebar-link {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: 10px 12px;
  border-radius: var(--radius-md);
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
  transition: all var(--transition-fast);
  min-height: 44px;
  margin-bottom: 2px;
}

.sidebar-link:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
}

.sidebar-link.active {
  background: rgba(225, 6, 0, 0.1);
  color: var(--gx-red-light);
}

.sidebar-link.active .sidebar-link-icon {
  transform: scale(1.1);
}

.sidebar-link-icon {
  flex-shrink: 0;
  font-size: 1.2rem;
  width: 28px;
  text-align: center;
  transition: transform var(--transition-fast);
}

.sidebar-link-label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar.collapsed .sidebar-link-label {
  display: none;
}
</style>
