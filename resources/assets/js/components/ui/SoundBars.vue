<template>
  <i class="relative flex gap-1 content-between w-[13px] aspect-square">
    <span v-for="i in 3" :key="i" />
  </i>
</template>

<style lang="postcss" scoped>
span {
  @apply w-[3px] h-full bg-current rounded-full origin-bottom;
  animation: bounce 2.2s ease-out infinite alternate;

  &:nth-of-type(2) {
    animation-delay: -2.2s;
  }

  &:nth-of-type(3) {
    animation-delay: -3.7s;
  }
}

@keyframes bounce {
  10% {
    transform: scaleY(0.2);
  }

  40% {
    transform: scaleY(.7);
  }

  60% {
    transform: scaleY(0.5);
  }

  75% {
    transform: scaleY(1);
  }

  100% {
    transform: scaleY(0.4);
  }
}
</style>
