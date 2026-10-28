---
description: What FFmpeg enables in Koel, and how to install it and point Koel to it.
---

# FFmpeg

[FFmpeg](https://ffmpeg.org/) is the most popular and powerful multimedia toolkit out there: it records, converts, and
streams just about any audio or video format, and powers countless media apps. Though not a
requirement for Koel to function, it unlocks these features:

* **Every format plays:** songs in formats browsers can't play, such as APE or WMA, are
  [converted on the fly](./streaming#forced-transcoding).
* **Smaller streams:** [FLAC transcoding](./streaming#flac-transcoding), [transcoding on mobile](./streaming#transcoding-on-mobile),
  and lower bit rates for [Subsonic apps](./subsonic).
* **Song previews:** 30-second clips for [embeds](./embedding) in Preview mode <PlusBadge />.
* **Even volume and waveforms:** [volume normalization and waveforms](../plus/volume-normalization) <PlusBadge />.

## Installing FFmpeg

If you use the [Docker image](https://github.com/koel/docker/), FFmpeg is already installed.

On Debian or Ubuntu:

```bash
sudo apt install ffmpeg
```

On macOS, with [Homebrew](https://brew.sh/):

```bash
brew install ffmpeg
```

For other systems, see [FFmpeg's download page](https://ffmpeg.org/download.html).

### Better AAC Quality

If your FFmpeg was built with Fraunhofer's `libfdk_aac` encoder (which needs `--enable-nonfree` when building FFmpeg),
Koel uses it automatically. It gives better quality than FFmpeg's own AAC encoder at the same bit rate, and in our tests
it was also faster, even than FFmpeg's encoder with `TRANSCODE_AAC_FAST` on.

## Pointing Koel to FFmpeg

Koel finds FFmpeg automatically. If it doesn't, set `FFMPEG_PATH` in your `.env` file to the full path of the `ffmpeg`
binary, for example:

```dotenv
FFMPEG_PATH=/usr/bin/ffmpeg
```

## Checking the Setup

Run:

```bash
php artisan koel:doctor
```

It tells you whether Koel can find and run FFmpeg.
