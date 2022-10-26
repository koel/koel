<template>
  <div class="skeleton">
    <div class="song-list-header">
      <span class="track-number">
        <span class="text"/>
      </span>
      <span class="title">
        <span class="text"/>
      </span>
      <span class="artist">
        <span class="text"/>
      </span>
      <span class="album">
        <span class="text"/>
      </span>
      <span class="time">
        <span class="text"/>
      </span>
      <span class="favorite"/>
    </div>
    <div v-for="i in 40" :key="i" class="song-item">
      <span class="track-number">
        <span class="text pulse"/>
      </span>
      <span class="title">
        <span class="thumbnail pulse"/>
        <span class="text pulse"/>
      </span>
      <span class="artist">
        <span class="text pulse"/>
      </span>
      <span class="album">
        <span class="text pulse"/>
      </span>
      <span class="time">
        <span class="text pulse"/>
      </span>
      <span class="favorite">
        <span class="text pulse"/>
      </span>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.skeleton {
  .song-list-header {
    background: var(--color-bg-secondary);
    height: 35px;
    display: flex;
  }

  .song-item {
    display: flex;
    border-bottom: 1px solid var(--color-bg-secondary);
    height: 64px;
  }

  .song-list-header span span, .pulse {
    display: inline-block;
    height: 1.2rem;
    border-radius: 999px;
    width: 80%;
  }

  .song-list-header span span {
    background: rgba(255, 255, 255, .1);
    width: 40%;
  }

  span:not(.text) {
    padding: 8px;
    vertical-align: middle;
  }

  .track-number {
    flex-basis: 66px;
    padding-left: 24px;
  }

  .thumbnail {
    display: block;
    width: 48px;
    height: 48px;
    border-radius: 5px;
  }

  .title {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .artist {
    flex-basis: 23%;
  }

  .album {
    flex-basis: 27%;
  }

  .time {
    flex-basis: 96px;
  }

  .favorite {
    flex-basis: 36px;
  }

  @media screen and (max-width: 768px) {
    span {
      display: none;
    }

    .title, .artist {
      display: flex;
    }

    .artist .text {
      width: 100%;
    }

    .song-list-header, .song-item {
      padding: 0 16px;
    }
  }
}
</style>
