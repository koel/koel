<template>
  <div class="tabs">
    <header>
      <slot name="header" />
    </header>
    <main>
      <slot />
    </main>
  </div>
</template>

<script lang="ts" setup>
</script>

<style lang="scss" scoped>
:deep(.tabs) {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  flex: 1;
  color: var(--color-text-secondary);
}

:deep(header) {
  display: flex;
  background: rgba(0, 0, 0, 0.05);
  overflow: hidden;
  flex-shrink: 0;
  border-bottom: 1px solid rgba(255, 255, 255, .04);

  label {
    font-size: 1rem;
    position: relative;
    padding: 1rem 1.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 0;
    opacity: .5;
    cursor: pointer;
    transition: opacity .2s ease-in-out;

    &:hover {
      opacity: .8;
    }

    &.active {
      opacity: 1;
    }

    input {
      display: none;
    }
  }
}

:deep(main) {
  display: flex;
  flex-direction: column;
  overflow: auto;
  flex: 1;
}

:deep(.songs-pane), :deep(.albums-pane) {
  display: flex;
  flex-direction: column;
  overflow: auto;
}

:deep(.albums-pane) {
  > ul {
    @include artist-album-wrapper();

    padding: 1.8rem;
    overflow: auto;
  }
}

:deep(.info-pane) {
  padding: 1.8rem;
}
</style>
