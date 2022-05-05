<template>
  <div class="middle-pane" data-testid="footer-middle-pane">
    <div id="progressPane" class="progress">
      <template v-if="song">
        <h3 class="title">{{ song.title }}</h3>
        <p class="meta">
          <a :href="`/#!/artist/${song.artist.id}`" class="artist">{{ song.artist.name }}</a> –
          <a :href="`/#!/album/${song.album.id}`" class="album">{{ song.album.name }}</a>
        </p>
      </template>

      <div class="plyr">
        <audio controls crossorigin="anonymous"></audio>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = defineProps<{ song?: Song }>()
const { song } = toRefs(props)
</script>

<style lang="scss">/* no scoping here because we're overriding some plyr classes */
.middle-pane {
  flex: 1;
  display: flex;

  @media only screen and (max-width: 768px) {
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
    height: 8px;
  }
}

#progressPane {
  flex: 1;
  position: relative;
  display: flex;
  flex-direction: column;
  place-content: center;
  place-items: center;

  .meta {
    font-size: .9rem;

    a {
      &:hover {
        color: var(--color-highlight);
      }
    }
  }

  // Some little tweaks here and there
  .plyr {
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
  }

  .plyr__progress {
    &--seek {
      height: 11px; // increase click area
    }
  }

  .plyr__controls {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    padding: 0;

    &--left, &--right {
      display: none;
    }
  }

  @media only screen and (max-width: 768px) {
    .meta, .title {
      display: none;
    }

    .plyr__progress {
      &--seek {
        border-bottom-color: var(--color-bg-primary);
      }
    }
  }
}
</style>
