<template>
  <ExtraDrawerButton class="block md:hidden" @click.prevent="toggleSidebar">
    <Icon :icon="faTimes" v-if="sidebarExpanded" fixed-width />
    <Icon :icon="faBars" v-else fixed-width />
  </ExtraDrawerButton>
</template>

<script lang="ts" setup>
import { faBars, faTimes } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { eventBus } from '@/utils'

import ExtraDrawerButton from '@/components/layout/main-wrapper/extra-drawer/ExtraDrawerButton.vue'

const sidebarExpanded = ref(false)

eventBus.on('TOGGLE_SIDEBAR', () => (sidebarExpanded.value = !sidebarExpanded.value))

const toggleSidebar = () => eventBus.emit('TOGGLE_SIDEBAR')
</script>
