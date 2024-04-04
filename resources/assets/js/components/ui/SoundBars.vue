<template>
  <i>
    <span />
    <span />
    <span />
  </i>
</template>

<style lang="postcss" scoped>
i {
  position: relative;
  display: flex;
  justify-content: space-between;
  width: 13px;
  height: 13px;
}

span {
  width: 3px;
  height: 100%;
  background-color: currentColor;
  border-radius: 9999px;
  animation: bounce 2.2s ease-out infinite alternate;
  transform-origin: bottom;

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
