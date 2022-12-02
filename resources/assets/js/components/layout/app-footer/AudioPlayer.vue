<template>
  <!--
    A very thin wrapper around Plyr, extracted as a standalone component for easier styling and to work better with HMR.
  -->
  <div class="plyr">
    <audio controls crossorigin="anonymous" />
  </div>
</template>

<script lang="ts" setup>
//
</script>

<style lang="scss">
// can't be scoped as it would be overridden by the plyr css
.plyr {
  width: 100%;
  height: 4px;

  audio {
    display: none;
  }

  .plyr__controls {
    background: transparent;
    box-shadow: none;
    padding: 0 !important;
    position: absolute;
    top: 0;
    width: 100%;
  }

  .plyr__progress--played[value] {
    transition: .3s ease-in-out;
    color: rgba(255, 255, 255, .1);
  }

  &:hover {
    .plyr__progress--played[value] {
      color: var(--color-highlight);
    }
  }

  @media(hover: none) {
    .plyr__progress--played[value] {
      color: var(--color-highlight);
    }
  }
}
</style>
