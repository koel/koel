<template>
  <div class="relative flex flex-1 overflow-hidden">
    <SideBar />
    <MainContent class="m-4 sm:ml-0 mb-0 bg-k-fg-3 border border-k-fg-5 rounded-xl" />
    <SideSheet />
    <ModalWrapper />
  </div>
</template>

<script lang="ts" setup>
import MainContent from '@/components/layout/main-wrapper/MainContent.vue'
import SideBar from '@/components/layout/main-wrapper/sidebar/Sidebar.vue'
import SideSheet from '@/components/layout/main-wrapper/side-sheet/SideSheet.vue'
import ModalWrapper from '@/components/layout/ModalWrapper.vue'
</script>
