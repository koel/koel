<template>
  <section class="max-h-full min-h-full w-full flex flex-col transform-gpu">
    <slot name="header" />

    <main
      v-koel-overflow-fade
      class="overflow-scroll flex flex-col b-16 md:b-6 p-6 flex-1 place-content-start"
    >
      <slot />
    </main>
  </section>
</template>

<style lang="postcss" scoped>
main {
  -ms-overflow-style: -ms-autohiding-scrollbar;
  overflow: hidden;
}
</style>
