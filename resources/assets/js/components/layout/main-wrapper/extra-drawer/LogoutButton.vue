<template>
  <ExtraDrawerButton v-koel-tooltip.left title="Log out" @click.prevent="logout">
    <Icon :icon="faArrowRightFromBracket" />
  </ExtraDrawerButton>
</template>

<script lang="ts" setup>
import { faArrowRightFromBracket } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'

import ExtraDrawerButton from '@/components/layout/main-wrapper/extra-drawer/ExtraDrawerButton.vue'

const logout = () => eventBus.emit('LOG_OUT')
</script>
