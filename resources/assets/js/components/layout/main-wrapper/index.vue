<template>
  <div id="mainWrapper">
    <Sidebar/>
    <MainContent/>
    <ExtraPanel/>
    <ModalWrapper/>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent } from 'vue'

const Sidebar = defineAsyncComponent(() => import('@/components/layout/main-wrapper/sidebar.vue'))
const MainContent = defineAsyncComponent(() => import('@/components/layout/main-wrapper/main-content.vue'))
const ExtraPanel = defineAsyncComponent(() => import('@/components/layout/main-wrapper/extra-panel.vue'))
const ModalWrapper = defineAsyncComponent(() => import('@/components/layout/ModalWrapper.vue'))
</script>

<style lang="scss">
#mainWrapper {
  display: flex;
  flex: 1;
  height: 0; // fix a flex-box bug https://github.com/philipwalton/flexbugs/issues/197#issuecomment-378908438
  overflow: hidden;
}
</style>
