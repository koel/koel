<template>
  <div class="skeleton">
    <div v-for="i in 10" :key="i" class="h-[40px] px-6 py-4 border-b border-b-white/5">
      <div class="h-full rounded-full pulse" :style="{ width: randomWidth() }" />
    </div>
  </div>
</template>

<script lang="ts" setup>
const randomWidth = () => {
  const min = 20
  const max = 50
  return `${Math.floor(Math.random() * (max - min + 1)) + min}%`
}
</script>
