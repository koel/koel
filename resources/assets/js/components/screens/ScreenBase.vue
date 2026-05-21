<template>
  <section class="max-h-full min-h-full w-full flex flex-col transform-gpu overflow-hidden">
    <div
      v-if="backgroundImage"
      class="cover-bg"
      data-testid="cover-bg"
      :style="{ backgroundImage: `url(${backgroundImage})` }"
    />
    <slot name="header" />

    <main v-koel-overflow-fade class="overflow-scroll flex flex-col b-16 md:b-6 p-6 flex-1 place-content-start">
      <slot />
    </main>
  </section>
</template>

<script lang="ts" setup>
withDefaults(
  defineProps<{
    backgroundImage?: string
  }>(),
  {
    backgroundImage: undefined,
  },
)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
main {
  -ms-overflow-style: -ms-autohiding-scrollbar;
}

.cover-bg {
  @apply absolute bg-cover bg-center pointer-events-none;
  inset: -32px;
  filter: blur(24px);
  -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.3) 0%, transparent 50%);
  mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.3) 0%, transparent 50%);
}
</style>
