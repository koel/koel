<template>
  <div id="mainWrapper">
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
import ExtraDrawer from '@/components/layout/main-wrapper/ExtraDrawer.vue'

const ModalWrapper = defineAsyncComponent(() => import('@/components/layout/ModalWrapper.vue'))
</script>

<style lang="scss">
#mainWrapper {
  position: relative;
  display: flex;
  flex: 1;
  height: 0; // fix a flex-box bug https://github.com/philipwalton/flexbugs/issues/197#issuecomment-378908438
  overflow: hidden;
}
</style>
