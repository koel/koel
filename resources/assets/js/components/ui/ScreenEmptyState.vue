<template>
  <div class="screen-empty-state" data-testid="screen-empty-state">
    <div>
      <span class="jumbo-icon">
        <slot name="icon">☕️</slot>
      </span>
      <div class="text">
        <slot>Placeholder text goes here.</slot>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
.screen-empty-state {
  display: flex;
  place-content: center;
  place-items: center;
  flex: 1;
  text-align: center;
  color: rgba(255, 255, 255, .5);
  position: relative;

  > div {
    max-width: 75%;
    padding: 0 2rem var(--header-height); // visually more balanced
    margin: auto 0;

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
    font-size: 2rem;
    font-weight: 200;
    line-height: 1.3;

    a {
      color: rgba(255, 255, 255, .8);

      &:hover {
        color: rgba(255, 255, 255, 1);
      }
    }

    .secondary {
      margin-top: .2rem;
      font-size: 1.5rem;
    }
  }
}
</style>
