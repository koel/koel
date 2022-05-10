<template>
  <button data-testid="close-modal-btn" @click.prevent="$emit('click')">
    <i class="fa fa-times"></i>
  </button>
</template>

<style lang="scss" scoped>
button {
  display: flex;
  place-content: center;
  place-items: center;
  position: absolute;
  right: 0;
  top: 0;
  background: var(--color-red);
  width: 24px;
  height: 24px;
  border-radius: 0;
  padding: 0;
  color: var(--color-text-primary);
  transition: opacity .3s;

  @media (hover: none) {
    opacity: 1;
  }
}
</style>
