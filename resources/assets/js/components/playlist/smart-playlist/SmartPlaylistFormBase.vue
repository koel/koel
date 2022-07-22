<template>
  <div class="smart-playlist-form">
    <slot/>
  </div>
</template>

<script lang="ts" setup>
</script>

<style lang="scss">
.smart-playlist-form {
  width: 560px;
}

.rules {
  background: rgba(0, 0, 0, .1);
  border: 1px solid rgba(255, 255, 255, .1);
  padding: .75rem;
  border-radius: .3rem;
}
</style>
