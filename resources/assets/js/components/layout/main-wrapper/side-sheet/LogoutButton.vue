<template>
  <SideSheetButton v-koel-tooltip.left title="Log out" @click.prevent="logout">
    <Icon :icon="faArrowRightFromBracket" />
  </SideSheetButton>
</template>

<script lang="ts" setup>
import { faArrowRightFromBracket } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils/eventBus'

import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const logout = () => eventBus.emit('LOG_OUT')
</script>
