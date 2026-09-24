<template>
  <div
    :class="`alert-box-${props.type}`"
    class="alert-box flex items-center gap-4 border border-k-fg-10 mb-6 p-4 rounded-md text-k-fg"
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

<script lang="ts" setup>
import {
  faCheckCircle,
  faExclamationCircle,
  faExclamationTriangle,
  faInfoCircle,
} from '@fortawesome/free-solid-svg-icons'

const props = withDefaults(defineProps<{ type?: 'default' | 'info' | 'danger' | 'success' | 'warning' }>(), {
  type: 'default',
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.alert-box {
  &-info {
    @apply border-k-primary;

    > svg {
      @apply text-k-primary;
    }
  }

  &-success {
    @apply border-k-success;

    > svg {
      @apply text-k-success;
    }
  }

  &-warning {
    @apply border-k-warning;

    > svg {
      @apply text-k-warning;
    }
  }

  &-danger {
    @apply border-k-danger;

    > svg {
      @apply text-k-danger;
    }
  }
}
</style>
