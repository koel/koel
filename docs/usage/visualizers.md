---
description: Watch your music in Koel's visualizers, including projectM, which plays MilkDrop presets you import.
---

# Visualizers

Click the bolt icon in the footer, or press <kbd>v</kbd>, to open the visualizer while music plays. Hover over it to bring up its controls, and pick a visualizer from the dropdown at the bottom right. Double-click to go full screen.

Visualizers pause along with your music and pick up again when it plays.

## MilkDrop Presets

The **projectM (MilkDrop)** visualizer plays MilkDrop presets, the `.milk` files made popular by Winamp. Koel doesn't come with any presets out of the box, but you can add them yourself easily.

<CaptionedImage :src="projectm" alt="The projectM visualizer playing a MilkDrop preset in Koel">Urchin Kali Mod preset from projectM</CaptionedImage>

To add presets:

1. Download some presets. The [Cream of the Crop](https://github.com/projectM-visualizer/presets-cream-of-the-crop) pack is a good start.
2. With projectM selected, choose **Manage presets…** from the preset dropdown next to the visualizer picker.
3. Click **Import** and pick one or more `.milk` files.

The same dropdown lets you choose a preset, or **Random** to switch to a different one every 30 seconds. In **Manage presets…**, you can also search your presets, apply one, or delete the ones you don't want.

Presets are saved in your browser only. They don't follow you to other browsers or devices, and clearing your browser's site data removes them.

<script lang="ts" setup>
import projectm from '../assets/img/interface/visualizer-projectm.avif'
</script>
