<template>
  <div class="relative flex flex-1 overflow-hidden">
    <a
      class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-1000 focus:px-4 focus:py-2 focus:rounded-md bg-k-highlight text-k-highlight-fg"
      href="#mainContent"
      @click.prevent="focusMainContent"
    >
      Skip to content
    </a>
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

const focusMainContent = () => document.querySelector<HTMLElement>('#mainContent')?.focus()
</script>
