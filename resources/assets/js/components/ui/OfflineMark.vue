<template>
  <span v-koel-tooltip class="opacity-50!">
    <Icon :icon="faCloudArrowDown" />
    <span class="sr-only">Available offline</span>
  </span>
</template>

<script lang="ts" setup>
import { faCloudArrowDown } from '@fortawesome/free-solid-svg-icons'
</script>
