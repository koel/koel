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

import Sidebar from '@/components/layout/main-wrapper/Sidebar.vue'
import MainContent from '@/components/layout/main-wrapper/MainContent.vue'
import ExtraPanel from '@/components/layout/main-wrapper/ExtraPanel.vue'

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
