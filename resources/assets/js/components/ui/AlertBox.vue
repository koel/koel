<template>
  <div class="alert-box" :class="`alert-box-${props.type}`">
    <Icon v-if="props.type === 'info' || props.type === 'default'" :icon="faInfoCircle" />
    <Icon v-if="props.type === 'danger'" :icon="faExclamationCircle" />
    <Icon v-if="props.type === 'success'" :icon="faCheckCircle" />
    <Icon v-if="props.type === 'warning'" :icon="faExclamationTriangle" />

    <div class="text">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  faCheckCircle,
  faExclamationCircle,
  faExclamationTriangle,
  faInfoCircle
} from '@fortawesome/free-solid-svg-icons'

const props = withDefaults(defineProps<{ type?: 'default' | 'info' | 'danger' | 'success' | 'warning' }>(), {
  type: 'default'
})
</script>

<style scoped lang="scss">
.alert-box {
  padding: 1rem;
  border-radius: 5px;
  color: var(--color-text-primary);
  display: flex;
  align-items: center;
  gap: 1rem;
  background-color: rgba(255, 255, 255, .1);

  // though setting margins in components is not recommended, it's safe to assume that an alert box will be used
  // in a context where this margin is desired
  margin-bottom: 1.5rem;

  .text {
    flex: 1;
  }

  &-info {
    background-color: rgb(59 130 246);
  }

  &-success {
    background-color: rgb(16 185 129);
  }

  &-warning {
    background-color: rgb(249 115 22);
  }

  &-danger {
    background-color: rgb(185 28 28);
  }
}
</style>
