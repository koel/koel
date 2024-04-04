<template>
  <div class="smart-playlist-form">
    <slot />
  </div>
</template>

<script lang="ts" setup>
</script>

<style lang="postcss" scoped>
.smart-playlist-form {
  width: 560px;
  max-height: calc(100vh - 4rem);
}

:slotted(form) {
  max-height: calc(100vh - 4rem);
  display: flex;
  flex-direction: column;
  overflow: auto;

  main {
    flex-grow: 1;
    overflow: scroll;
    display: flex;
    flex-direction: column;

    .rules {
      flex-grow: 1;
      overflow: auto;
    }
  }
}

:slotted(label.folder) {
  flex: .6;
}

:slotted(.rules) {
  background: rgba(0, 0, 0, .1);
  border: 1px solid rgba(255, 255, 255, .1);
  padding: .75rem;
  border-radius: .3rem;
}
</style>
