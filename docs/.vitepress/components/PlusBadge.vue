<template>
  <span title="Only available in Koel Plus" />
</template>

<script lang="ts" setup>
</script>

<style scoped lang="postcss">
span {
  display: inline-block;

  &::before {
    padding: .25rem calc(0.375rem + 1px) .25rem .375rem;
    line-height: 1;
    border-radius: 5px;
    background-image: linear-gradient(to top right, #ec4899, #ef4444, #7e22ce);
    color: white;
    font-size: .75rem;
    text-transform: uppercase;
    font-weight: bold;
    text-shadow: 1px 1px 0 rgba(0, 0, 0, 0.25);
    content: 'Plus'
  }
}
</style>
