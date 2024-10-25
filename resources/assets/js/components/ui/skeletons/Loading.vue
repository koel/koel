<template>
  <div class="w-full pointer-events-none h-0.5 fixed z-50 top-0 left-0">
    <div class="bg-k-highlight marquee absolute h-full w-1/3" />
  </div>
  <div class="w-full h-full skeleton p-4">
    <div class="w-full h-full pulse rounded-md" />
  </div>
</template>

<style lang="postcss">
@keyframes marquee {
  from {
    left: -50%;
  }
  to {
    left: 100%;
  }
}

.marquee {
  animation: marquee 3s ease-in-out infinite;

  @media (prefers-reduced-motion: reduce) {
    animation: none;
  }
}
</style>
