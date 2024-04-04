<template>
  <ExtraDrawerButton class="block md:hidden" @click.prevent.stop="toggleSidebar">
    <Icon :icon="faBars" />
  </ExtraDrawerButton>
</template>

<script lang="ts" setup>
import { faBars } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'

import ExtraDrawerButton from '@/components/layout/main-wrapper/extra-drawer/ExtraDrawerButton.vue'

const toggleSidebar = () => eventBus.emit('TOGGLE_SIDEBAR')
</script>
