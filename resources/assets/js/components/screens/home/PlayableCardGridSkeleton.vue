<template>
  <ol class="skeleton grid grid-cols-1 md:grid-cols-2" data-testid="playable-card-grid-skeleton">
    <li v-for="key in 6" :key class="flex items-center gap-4 px-6 py-4">
      <div class="w-14 aspect-square rounded pulse shrink-0" />
      <span class="flex-1 space-y-2">
        <span class="block h-4 rounded-full w-3/5 pulse" />
        <span class="block h-3 rounded-full w-2/5 pulse" />
      </span>
      <div class="h-4 rounded-full w-10 pulse shrink-0" />
    </li>
  </ol>
</template>
