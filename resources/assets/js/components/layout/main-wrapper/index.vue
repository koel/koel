<template>
  <div class="relative flex flex-1 overflow-hidden">
    <SideBar />
    <MainContent />
    <ExtraDrawer />
    <ModalWrapper />
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent } from 'vue'

import SideBar from '@/components/layout/main-wrapper/sidebar/Sidebar.vue'
import MainContent from '@/components/layout/main-wrapper/MainContent.vue'
import ExtraDrawer from '@/components/layout/main-wrapper/extra-drawer/ExtraDrawer.vue'

const ModalWrapper = defineAsyncComponent(() => import('@/components/layout/ModalWrapper.vue'))
</script>
