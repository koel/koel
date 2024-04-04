<template>
  <div
    class="flex items-center flex-wrap justify-center gap-x-2 gap-y-4 p-4 bg-black/10
    rounded-md border border-white/5"
  >
    <a
      v-for="sponsor in sponsors"
      :key="sponsor.url"
      :href="sponsor.url"
      :title="sponsor.description"
      class="opacity-70 hover:opacity-100"
      target="_blank"
    >
      <img
        :alt="sponsor.description"
        :src="sponsor.logo.src"
        :style="sponsor.logo.style"
        class="brightness-[10] h-[32px]"
      >
    </a>
  </div>
</template>

<script lang="ts" setup>
import sponsors from '@/sponsors'
</script>
