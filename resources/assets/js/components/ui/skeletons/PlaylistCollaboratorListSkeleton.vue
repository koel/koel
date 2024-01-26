<template>
  <div class="wrapper skeleton">
    <div v-for="i in 3" :key="i" class="pulse"/>
  </div>
</template>

<script setup lang="ts">
</script>

<style scoped lang="scss">
.wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
  gap: .5rem;

  > div {
    border-radius: 5px;
    height: 49px;
  }
}
</style>
