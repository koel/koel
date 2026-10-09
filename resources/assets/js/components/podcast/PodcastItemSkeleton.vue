<template>
  <article
    class="skeleton flex overflow-hidden rounded-lg border border-k-fg-5 hover:bg-k-fg-10 bg-k-fg-5 text-k-fg! hover:text-k-fg!"
  >
    <aside class="hidden md:block md:flex-[0_0_11rem] pulse" />
    <main class="flex-1 p-5">
      <header>
        <h3 class="pulse w-2/3 mt-4 h-8" />
        <p class="mt-2 pulse w-1/4 h-6" />
      </header>
      <div class="space-y-2 mt-3">
        <p class="pulse w-full h-4" />
        <p class="pulse w-1/5 h-4" />
      </div>
    </main>
  </article>
</template>

<script setup lang="ts"></script>
