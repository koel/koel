<template>
  <div id="mainWrapper">
    <sidebar/>
    <main-content/>
    <extra-panel/>
    <modal-wrapper/>
  </div>
</template>

<script lang="ts">
import Vue from 'vue'

export default Vue.extend({
  components: {
    Sidebar: () => import('@/components/layout/main-wrapper/sidebar.vue'),
    MainContent: () => import('@/components/layout/main-wrapper/main-content.vue'),
    ExtraPanel: () => import('@/components/layout/main-wrapper/extra-panel.vue'),
    ModalWrapper: () => import('@/components/layout/modal-wrapper.vue')
  }
})
</script>

<style lang="scss">
#mainWrapper {
  display: flex;
  flex: 1;
  height: 0; // fix a flex-box bug https://github.com/philipwalton/flexbugs/issues/197#issuecomment-378908438
  overflow: hidden;
}
</style>
