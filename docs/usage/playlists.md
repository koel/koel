---
description: Creating standard and smart playlists, organizing with nested folders, and managing collaborative playlists in Koel.
---

# Creating and Managing Playlists

Koel supports creating an unlimited number of playlists and organizing them into nested folders.
Start by clicking the <InterfaceIcon :src="plusIcon" alt="Add" /> button next to the "Playlists" header in the
navigation bar. You'll be provided with three options:

* "New Playlist…" brings up a dialog to create a standard playlist
* "New Smart Playlist…" brings up a dialog to create a smart playlist
* "New Folder…" brings up a dialog to create a playlist folder

To create a **standard playlist** or **playlist folder**, enter a name and save it.
Once a playlist or playlist folder is created, you can:

* Drag and drop songs into a standard playlist to add them
* Drag and drop playlists into or out of playlist folders

## Organizing Playlist Folders

To create a folder inside another folder, open the parent folder's context menu and choose **Add** > **New Folder…**.

Drag a folder onto another folder to move it there. To move it back to the top level, drag it to the root of the
Playlists list.

To rename a folder or move it without dragging, open its context menu and choose **Edit…**. Change the folder name or
select a **Parent Folder**, then save your changes. Select **Root** to move the folder to the top level.

To remove a folder, open its context menu, choose **Delete**, and confirm the deletion.

::: info Deleting a folder
Deleting a playlist folder keeps its contents. Playlists directly inside it become unfiled, and its subfolders move to
the top level with their contents unchanged.
:::

For a **smart playlist**, you can define the criteria that determine which songs are included in the playlist,
and Koel will automatically update the playlist based on those criteria.
For example, with the criteria shown in the following screenshot:

<img loading="lazy" src="../assets/img/new-smart-playlist.avif" alt="Smart playlist" style="max-width: 560px" />

Koel will include in the playlist:

* all songs by Pink Floyd, and
* songs by Iron Maiden that have been played more than 99 times by the current user

There are a great variety of criteria to choose from, and you can combine them in any way you like, providing a powerful
tool for creating dynamic playlists.

## Collaboration <PlusBadge />

Users of Koel Plus can collaborate on playlists. See [Collaboration](../plus/collaboration.md) for more details.

<script lang="ts" setup>
import plusIcon from '../assets/icons/plus-circle.svg'
</script>
