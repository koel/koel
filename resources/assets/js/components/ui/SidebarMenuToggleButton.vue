<template>
  <button @click.prevent.stop="toggleSidebar">
    <icon :icon="faBars"/>
  </button>
</template>

<script lang="ts" setup>
import { faBars } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'

const toggleSidebar = () => eventBus.emit('TOGGLE_SIDEBAR')
</script>
