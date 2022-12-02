<template>
  <header class="skeleton screen-header expanded">
    <aside class="thumbnail-wrapper pulse" />

    <main>
      <h1 class="pulse" />
      <p class="meta pulse" />
      <p class="controls pulse" />
    </main>
  </header>
</template>

<style lang="scss" scoped>
.skeleton {
  display: flex;
  align-items: flex-end;
  position: relative;
  align-content: stretch;
  padding: 1.8rem;

  .thumbnail-wrapper {
    margin-right: 1.5rem;
    width: 192px;
    aspect-ratio: 1/1;
    border-radius: 50%;
    display: block !important;
  }

  main {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    flex: 1;
    gap: 1rem;
  }

  h1 {
    height: 4rem;
    width: 80%;
  }

  p {
    height: 1.2rem;
    width: 40%;

    &.controls {
      height: 2.5rem;
      border-radius: 999px;
      width: 25%;
    }
  }
}
</style>
