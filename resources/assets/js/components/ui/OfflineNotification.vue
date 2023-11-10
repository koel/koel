<template>
  <div class="offline">
    <IconLayers fixed-width>
      <Icon :icon="faWifi" fixed-width size="xl" />
      <Icon :icon="faSlash" fixed-width size="lg" transform="up-2" />
    </IconLayers>
    <span class="text">You’re offline.</span>
  </div>
</template>

<script lang="ts" setup>
import { faSlash, faWifi } from '@fortawesome/free-solid-svg-icons'
</script>

<style lang="scss" scoped>
.offline {
  color: #ed5135;
  width: 144px;
  height: 42px;
  background: #fff;
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  position: fixed;
  z-index: 10000;
  left: 1.6rem;

  bottom: calc(var(--footer-height) + 1.2rem);
  box-shadow: 0 2px 30px rgba(0, 0, 0, .3);
}

.text {
  color: #222;
}

.fa-slash {
  filter: drop-shadow(0 2px 0 #fff);
}
</style>
