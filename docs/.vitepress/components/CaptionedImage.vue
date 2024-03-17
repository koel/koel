<script lang="ts" setup>
defineOptions({
  inheritAttrs: false
})
</script>

<template>
  <figure class="rounded-md">
    <img class="border-0 rounded-0" v-bind="$attrs" />
    <figcaption>
      <slot />
    </figcaption>
  </figure>
</template>

<style scoped lang="scss">
figure {
  margin: 1.5rem 0;
  overflow: hidden;

  img {
    margin: 0;
  }

  figcaption {
    background: rgb(66 141 185 / 5%);
    text-align: center;
    font-size: .85rem;
    padding: .5rem;
    font-style: italic;
  }
}
</style>
