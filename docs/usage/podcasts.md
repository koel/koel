# Podcasts

Aside from music, Koel also supports podcast streaming. This feature is still in its early stages, so expect more improvements in the future.

![Podcasts](../assets/img/podcasts.avif)

## Subscribe to a Podcast

To subscribe to a podcast, click the <InterfaceIcon :src="plusIcon" /> button on the top right of the "Podcasts" page and enter the podcast's RSS feed URL.
You can find the RSS feed URL on the podcast's website or by searching for it with your favorite search engine.
Note that Koel doesn't support proprietary podcast services like Spotify or Apple Podcasts.

:::warning
Podcast parsing can be time-consuming, and not all podcasts are created equal.
If the process errors out, check [Troubleshooting](../troubleshooting#first-steps) for help.
:::

After successful subscription, you will be able to browse the podcast's episodes and stream them directly from Koel.

## Listening to Podcasts

Koel treats podcast episodes not much differently from songs. You can stream them, mark them as favorites, add them to playlists, search for them etc. using the same
[interface elements](./web-interface.md). However, there are some main differences:

* Koel maintains the last played position for each episode, so you can resume listening where you left off.
* Smart playlists don't work with podcasts and will not contain podcast episodes.
* You can't edit the metadata of podcasts and episodes like you can with songs.
* Podcast episodes don't have "genres" (though they may have categories), so you can't browse them by genre.
* [Playlist collaboration](../plus/collaboration) <PlusBadge /> doesn't work with podcasts.
* When streaming an episode, Koel will first try with its original source and only download it to your server if a CORS error occurs in the attempt. This means that you can stream podcasts without worrying about your server's storage space, but your streaming experience will depend on the podcast's hosting service.

## Updating Podcasts

If you have Koel's [command scheduler](../cli-commands.md#command-scheduling) set up, the `koel:podcasts:sync` command will automatically run at midnight to keep your podcasts up to date.
You can also update a podcast's episodes manually from the web interface by clicking the <InterfaceIcon :src="refreshIcon" alt="Refresh" /> button on the podcast's page.

<script lang="ts" setup>
import plusIcon from '../assets/icons/plus.svg'
import refreshIcon from '../assets/icons/refresh.svg'
</script>

