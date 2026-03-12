<template>
  <nav v-if="links && links.length > 3" class="pagination-nav">
    <div class="pagination-links">
      <Link
        v-for="(link, i) in links"
        :key="i"
        :href="link.url || ''"
        class="pagination-link"
        :class="{ active: link.active, disabled: !link.url }"
        v-html="formatLabel(link.label)"
        :preserve-scroll="true"
      />
    </div>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  links: Array,
});

const formatLabel = (label) => {
  return label
    .replace('&laquo; Previous', '← Anterior')
    .replace('Next &raquo;', 'Siguiente →')
    .replace('pagination.previous', '← Anterior')
    .replace('pagination.next', 'Siguiente →');
};
</script>

<style scoped>
.pagination-nav {
  display: flex;
  justify-content: center;
  padding: var(--space-lg) 0;
}

.pagination-links {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
  justify-content: center;
}

.pagination-link {
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text-secondary);
  transition: all 0.2s;
  text-decoration: none;
}

.pagination-link:hover:not(.disabled) {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.4);
  color: white;
}

.pagination-link.active {
  background: var(--gx-blue, #3b82f6);
  border-color: var(--gx-blue, #3b82f6);
  color: white;
}

.pagination-link.disabled {
  opacity: 0.4;
  pointer-events: none;
}
</style>
