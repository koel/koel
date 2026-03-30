<template>
  <ol class="grid grid-cols-1 md:grid-cols-2">
    <li v-for="key in 6" :key class="flex items-center gap-3 p-4">
      <div class="w-12 aspect-square rounded pulse flex-shrink-0" />
      <span class="flex-1 space-y-2">
        <div class="h-4 rounded-full w-3/5 pulse" />
        <div class="h-3 rounded-full w-2/5 pulse" />
      </span>
      <div class="h-4 rounded-full w-10 pulse flex-shrink-0" />
    </li>
  </ol>
</template>
