<template>
  <Btn
    class="block w-full rounded-md px-4 py-3 text-white/80 hover:text-white bg-gradient-to-r to-[#c62be8] from-[#671ce4] !text-left"
    @click.prevent="openModal"
  >
    <Icon :icon="faPlus" fixed-width />
    Upgrade to Plus
  </Btn>
</template>

<script setup lang="ts">
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'

import Btn from '@/components/ui/form/Btn.vue'

const openModal = () => eventBus.emit('MODAL_SHOW_KOEL_PLUS')
</script>
