<template>
  <ExtraDrawerButton class="block md:hidden" @click.prevent="toggleSidebar">
    <Icon :icon="faTimes" v-if="sidebarExpanded" fixed-width />
    <Icon :icon="faBars" v-else fixed-width />
  </ExtraDrawerButton>
</template>

<script lang="ts" setup>
import { faBars, faTimes } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'

import ExtraDrawerButton from '@/components/layout/main-wrapper/extra-drawer/ExtraDrawerButton.vue'
import { ref } from 'vue'

const sidebarExpanded = ref(false)

const toggleSidebar = () => {
  // Since the sidebar will be hidden if we click outside the menu anyway, we don't actually need
  // to emit the event to collapse it. We just need to toggle the state to show the
  // icon accordingly.
  if (!sidebarExpanded.value) eventBus.emit('TOGGLE_SIDEBAR')
  sidebarExpanded.value = !sidebarExpanded.value
}
</script>
