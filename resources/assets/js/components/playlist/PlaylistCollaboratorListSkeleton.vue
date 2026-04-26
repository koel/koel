<template>
  <div class="skeleton flex flex-col w-full gap-3" role="status" aria-busy="true" aria-label="Loading">
    <div v-for="i in 3" :key="i" class="rounded-md h-[47px] pulse" />
  </div>
</template>
