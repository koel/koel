<template>
  <SideSheetButton class="block md:hidden" @click.prevent="toggleSidebar">
    <Icon v-if="sidebarExpanded" :icon="faTimes" fixed-width />
    <Icon v-else :icon="faBars" fixed-width />
  </SideSheetButton>
</template>

<script lang="ts" setup>
import { faBars, faTimes } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { eventBus } from '@/utils/eventBus'

import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const sidebarExpanded = ref(false)

eventBus.on('TOGGLE_SIDEBAR', () => (sidebarExpanded.value = !sidebarExpanded.value))

const toggleSidebar = () => eventBus.emit('TOGGLE_SIDEBAR')
</script>
