<template>
  <Btn
    class="block w-full rounded-md px-4 py-3 text-white/80 hover:text-white bg-gradient-to-r to-[#c62be8] from-[#671ce4] !text-left"
    @click.prevent="showPlusModal"
  >
    <Icon :icon="faPlus" fixed-width />
    Upgrade to Plus
  </Btn>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { defineAsyncComponent } from '@/utils/helpers'
import { useModal } from '@/composables/useModal'

import Btn from '@/components/ui/form/Btn.vue'

const KoelPlusModal = defineAsyncComponent(() => import('@/components/koel-plus/KoelPlusModal.vue'))
const { openModal } = useModal()

const showPlusModal = () => openModal<'KOEL_PLUS'>(KoelPlusModal)
</script>
