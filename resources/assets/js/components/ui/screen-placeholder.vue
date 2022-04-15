<template>
  <div class="screen-placeholder" data-test="screen-placeholder">
    <div>
      <span class="jumbo-icon">
        <slot name="icon">
          <i class="fa fa-upload"></i>
        </slot>
      </span>
      <div class="text">
        <slot>Placeholder text goes here.</slot>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
</script>

<style lang="scss" scoped>
.screen-placeholder {
  display: flex;
  place-content: center;
  place-items: center;
  height: 100%;
  width: 100%;
  text-align: center;
  color: rgba(255, 255, 255, .5);
  position: relative;

  > div {
    max-width: 75%;
    padding: 0 2rem;
    margin: 0 auto;

    @media only screen and (max-width: 768px) {
      max-width: 100%;
    }
  }

  .jumbo-icon {
    display: block;
    font-size: 6em;
    margin-bottom: 1.5rem;
    opacity: .7;
  }

  .text {
    font-size: 2em;
    font-weight: 200;
    line-height: 1.3;

    a {
      color: rgba(255, 255, 255, .8);

      &:hover {
        color: rgba(255, 255, 255, 1);
      }
    }

    .secondary {
      font-size: 1.5rem;
    }
  }
}
</style>
