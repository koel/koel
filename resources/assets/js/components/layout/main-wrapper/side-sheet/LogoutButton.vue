<template>
  <SideSheetButton v-koel-tooltip.left @click.prevent="logout">
    <Icon :icon="faArrowRightFromBracket" />
    <span class="sr-only">Log out</span>
  </SideSheetButton>
</template>

<script lang="ts" setup>
import { faArrowRightFromBracket } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils/eventBus'

import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const logout = () => eventBus.emit('LOG_OUT')
</script>
