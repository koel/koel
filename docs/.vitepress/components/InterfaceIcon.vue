<template>
  <span>
    <img class="inline border-0 rounded-0" v-bind="$attrs">
  </span>
</template>

<script lang="ts" setup>
defineOptions({
  inheritAttrs: false
})
</script>

<style scoped>
span {
  width: 20px;
  height: 20px;
  background-color: var(--vp-code-bg);
  border-bottom: 1px solid var(--vp-code-bg);
  vertical-align: text-bottom;

  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 3px;
  border-radius: 3px;
}

img {
  html.dark & {
    filter: invert(1);
  }

  border-radius: 0;
}
</style>
