<template>
  <div
    class="alert-box flex items-center gap-4 bg-white/10 mb-6 p-4 rounded-md text-k-text-primary"
    :class="`alert-box-${props.type}`"
  >
    <Icon v-if="props.type === 'info' || props.type === 'default'" :icon="faInfoCircle" />
    <Icon v-if="props.type === 'danger'" :icon="faExclamationCircle" />
    <Icon v-if="props.type === 'success'" :icon="faCheckCircle" />
    <Icon v-if="props.type === 'warning'" :icon="faExclamationTriangle" />

    <div class="flex-1">
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

<style scoped lang="postcss">
.alert-box {
  &-info {
    @apply bg-blue-500;
  }

  &-success {
    @apply bg-green-600;
  }

  &-warning {
    @apply bg-orange-500;
  }

  &-danger {
    @apply bg-red-500;
  }
}
</style>
