# Profile and Preferences

To manage your profile and preferences, click on your avatar in the bottom-right corner of the screen.
From here, you can manage a couple aspects of your account:

## Profile

To update your profile, you must first authenticate yourself by entering your current password.
After that, you can update your name and email, and set a new password.
Leaving the New Password field blank will keep your current password intact.

:::tip Pick a strong password
Koel enforces a strong password policy.
Make sure to pick a password that is at least 10 characters long and contains a mix of letters, numbers, and special characters.
Your password will also be checked against a list of leaked passwords for extra security.
:::

## Custom Avatar

By default, Koel uses [Gravatar](https://gravatar.com) to fetch your avatar based on your email address.
By hovering over the avatar and clicking the <InterfaceIcon :src="uploadIcon" /> icon, you can select an image file from your computer, crop it, and set it as your custom avatar.
Remember to click Save for the change to take effect.

To remove your custom avatar and revert to using Gravatar, click the <InterfaceIcon :src="timesIcon" /> icon.

## Themes

At the time of this writing, Koel comes with 17 themes built-in. You can activate a theme simply by clicking on it. The new theme will be applied immediately.

![Theme selection](../assets/img/themes.webp)

More themes are to be added in the future, along with the ability to create your own theme.

## Preferences

Koel allows you to set a couple of preferences:

* Whether playing a song should trigger continuous playback of the entire playlist, album, artist, or genre
* Whether to show a notification whenever a new song starts playing
* Whether to confirm before closing Koel’s browser tab
* Whether to show a translucent, blurred overlay of the current album’s art
* Whether to transcode music to a lower bitrate (mobile only, useful if you have a slow connection)
* Whether to set your uploaded music as public by default <PlusBadge />

These preferences are saved immediately upon change and synced across all of your devices.

## Service Integration Statuses

If your Koel installation is [integrated](../service-integrations) with any external services, such as Last.fm or Spotify, you can see their statuses here along with the ability to connect or disconnect them when applicable.

## QR Code Authentication

This tab displays a QR code that you can scan to log in to [Koel Player](../mobile-apps.md) on your phone without having to manually enter your credentials.
The code refreshes every ten minutes, but you can also manually refresh it.

<script lang="ts" setup>
import uploadIcon from '../assets/icons/upload.svg'
import timesIcon from '../assets/icons/times.svg'
</script>
