<template>
  <span class="btn-group">
    <slot>
      <Btn green>Foo</Btn>
      <Btn orange>Bar</Btn>
      <Btn red>Baz</Btn>
    </slot>
  </span>
</template>

<script lang="ts" setup>
import { defineAsyncComponent } from 'vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
</script>

<style lang="scss">
.btn-group {
  display: flex;
  position: relative;
  flex-wrap: nowrap;

  button {
    &:not(:first-child) {
      border-radius: 0;
    }

    &:first-of-type {
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
      border-top-left-radius: 9999px;
      border-bottom-left-radius: 9999px;
    }

    &:last-of-type {
      border-top-right-radius: 9999px;
      border-bottom-right-radius: 9999px;
    }
  }

  &[uppercased] button {
    text-transform: uppercase;
  }
}
</style>
