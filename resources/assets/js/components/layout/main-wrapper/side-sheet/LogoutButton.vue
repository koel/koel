<template>
  <SideSheetButton v-koel-tooltip.left :title="t('ui.tooltips.logout')" @click.prevent="logout">
    <Icon :icon="faArrowRightFromBracket" />
  </SideSheetButton>
</template>

<script lang="ts" setup>
import { faArrowRightFromBracket } from '@fortawesome/free-solid-svg-icons'
import { useI18n } from 'vue-i18n'
import { eventBus } from '@/utils/eventBus'

import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const { t } = useI18n()
const logout = () => eventBus.emit('LOG_OUT')
</script>
