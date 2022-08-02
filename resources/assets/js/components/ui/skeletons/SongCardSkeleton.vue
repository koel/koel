<template>
  <article class="skeleton">
    <aside class="pulse"/>
    <main>
      <div class="details">
        <h3 class="pulse"/>
        <p class="pulse"/>
      </div>
    </main>
  </article>
</template>

<style lang="scss" scoped>
.skeleton {
  display: flex;
  gap: 12px;
  padding: 8px 12px 8px 8px;
  border-radius: 5px;
  align-items: center;
  border: 1px solid var(--color-bg-secondary);

  aside {
    width: 48px;
    aspect-ratio: 1/1;
    border-radius: 50%;
  }

  main {
    flex: 1;
    display: flex;
    align-items: flex-start;
    gap: 8px;

    .details {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    h3, p {
      width: 66%;
      height: 1.2em;
    }

    p {
      width: 33%;
    }
  }
}
</style>
