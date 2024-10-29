<template>
  <div class="relative flex flex-1 overflow-hidden">
    <SideBar />
    <MainContent />
    <SideSheet />
    <ModalWrapper />
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent } from '@/utils/helpers'

import MainContent from '@/components/layout/main-wrapper/MainContent.vue'
import SideBar from '@/components/layout/main-wrapper/sidebar/Sidebar.vue'
import SideSheet from '@/components/layout/main-wrapper/side-sheet/SideSheet.vue'

const ModalWrapper = defineAsyncComponent(() => import('@/components/layout/ModalWrapper.vue'))
</script>
