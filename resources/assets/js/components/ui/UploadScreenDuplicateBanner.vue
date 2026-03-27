<template>
  <div class="duplicate-banner">
    <div class="message-content">
      <FontAwesomeIcon :icon="faTriangleExclamation" class="status-icon" />
      <span><strong>4 duplicate files</strong> detected</span>
    </div>

    <button class="expand-btn" @click="$emit('toggle')">
      View songs
    </button>
  </div>
</template>

<script setup>
import { faTriangleExclamation } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineEmits(['toggle'])
</script>

<style scoped>
.duplicate-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;

  width: 100%;
  max-width: 800px;
  margin: 1rem auto;
  padding: 0.75rem 1.25rem;

  background-color: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.3);
  border-radius: 12px;
  color: #f59e0b;
  font-size: 0.95rem;
  transition: all 0.2s ease;
}

.message-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.1rem;
}
.status-icon {
  font-size: 1.1rem;
}

.expand-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.8rem;

  background: rgba(245, 158, 11, 0.2);
  border: none;
  border-radius: 6px;
  color: inherit;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
}

.expand-btn:hover {
  background: rgba(245, 158, 11, 0.3);
}
</style>
