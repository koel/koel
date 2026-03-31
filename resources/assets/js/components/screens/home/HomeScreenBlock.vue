<template>
  <div>
    <h3 class="text-2xl font-thin text-k-fg">
      <slot name="header" />
    </h3>
    <p v-if="$slots.subheading" class="text-k-fg-50 mt-1">
      <slot name="subheading" />
    </p>
    <div class="mt-8">
      <slot />
    </div>
  </div>
</template>
