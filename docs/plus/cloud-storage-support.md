# Cloud Storage Support

In addition to storing your music on the same server as Koel’s installation via the `local` storage drivers,
Koel Plus offers several different file storage options, including SFTP, Amazon S3, S3-compatible services, Dropbox, and likely more in the future.
This page will guide you through the process of setting up these storage options.

:::warning Warning
Though possible, changing storage drivers _after_ you've already stored files is not recommended, as it may break links to your existing media.
:::

:::tip Service UI may change
The screenshots and instructions on this page may not be 100% up-to-date as 3rd-party services' UI may change.
The general idea, however, should remain the same.
:::

## SFTP

To use SFTP as your storage driver, you need to have an SFTP server set up and running. Many cloud hosting providers offer SFTP access to their storage services.
To enable SFTP storage support in Koel, you need to provide the following configuration in your `.env` file:

```
STORAGE_DRIVER=sftp

SFTP_HOST=
SFTP_PORT=

# The absolute path of the directory to store the media files on the SFTP server.
# Make sure the directory exists and is writable by the SFTP user.
SFTP_ROOT=

# You can use either a username/password pair…
SFTP_USERNAME=
SFTP_PASSWORD=

# …or private key authentication:
SFTP_PRIVATE_KEY=
SFTP_PASSPHRASE=
```

After reloading, Koel will start using SFTP as its storage driver. You can now upload your music files to your SFTP server directly from Koel’s web interface.

## Amazon S3 and Compatible Services

Since Amazon S3 and S3-compatible services share the same API, you can use the same configuration (`AWS_*`) for both.
Koel has been tested with Amazon S3, [DigitalOcean Spaces](https://www.digitalocean.com/products/spaces), and [Cloudflare R2](https://www.cloudflare.com/developer-platform/r2/), but it should work with any S3-compatible service given the right configuration.

### Amazon S3

Create a new S3 bucket and obtain your access key ID and secret key from the AWS console. Then, populate these values in your `.env` file:

```
STORAGE_DRIVER=s3

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_REGION=
AWS_ENDPOINT=
AWS_BUCKET=
```

After reloading, Koel will start using S3 as its storage. You can now upload your music files to your S3 bucket directly from Koel’s web interface.

In order to use S3 for streaming, you'll also need to set up a cross-origin resource sharing (CORS) configuration for your bucket by going to the Permissions tab. Here's a sample policy that allows streaming from any origin:

```json
[
    {
        "AllowedHeaders": [],
        "AllowedMethods": [
            "GET"
        ],
        "AllowedOrigins": [
            "*"
        ],
        "ExposeHeaders": []
    }
]
```

### DigitalOcean Spaces

To use [DigitalOcean Spaces](https://www.digitalocean.com/products/spaces), you can follow the same steps as with Amazon S3. Below are some screenshots to help you get started.

<CaptionedImage :src="doCreateBucket" alt="Create DO Spaces bucket screen">Create a DigitalOcean Spaces bucket</CaptionedImage>

<CaptionedImage :src="doBucketSettings" alt="DO Spaces bucket settings">DigitalOcean Spaces bucket settings with CORS Configurations</CaptionedImage>

The access key ID and secret key can be generated and obtained via the API -> Space Keys tab in the DigitalOcean control panel:

![DigitalOcean Spaces keys](../assets/img/plus/storage/do-keys.webp)

A sample `.env` configuration for DigitalOcean Spaces may look like this:

```
STORAGE_DRIVER=s3

AWS_ACCESS_KEY_ID=DO000000000000000000
AWS_SECRET_ACCESS_KEY=TheSecretKeyObtainedFromDO
AWS_REGION=fra1
AWS_ENDPOINT=https://fra1.digitaloceanspaces.com
AWS_BUCKET=koel
```

### Cloudflare R2

[Cloudflare R2](https://www.cloudflare.com/developer-platform/r2/) is yet another excellent choice for an S3-compatible service.
The setup is pretty much similar to the above:

<CaptionedImage :src="r2CreateBucket" alt="Create CF R2 bucket screen">Create a Cloudflare R2 bucket</CaptionedImage>

<CaptionedImage :src="r2BucketSettings" alt="CF R2 bucket settings">Cloudflare R2 bucket settings with CORS Policy</CaptionedImage>

A sample `.env` configuration for Cloudflare R2 may look like this:

```
STORAGE_DRIVER=s3

AWS_ACCESS_KEY_ID=c50000000000000000
AWS_SECRET_ACCESS_KEY=TheSecretKeyObtainedFromR2
AWS_REGION=auto # Cloudflare R2 explicitly uses the `auto` region
AWS_ENDPOINT=https://fa37a667b0038bbb7054133627ce74b4.r2.cloudflarestorage.com
AWS_BUCKET=koel
```

## Dropbox

Koel Plus also supports using Dropbox as a storage driver, although the setup is a bit different.

1. First, [create a new Dropbox app](https://www.dropbox.com/developers/apps/create) with the "App folder" access type and set up sufficient permissions to read and write files.
    <CaptionedImage :src="dropboxCreateApp" alt="Create a Dropbox app">Create a Dropbox app</CaptionedImage>
    <CaptionedImage :src="dropboxAppSettings" alt="Dropbox app permissions">Set sufficient app permissions</CaptionedImage>
2. Under your Dropbox app's Settings tab, make a note of the "App key" and "App secret" values. Now, from the root folder of your Koel installation, run the following command:
    ```bash
    php artisan koel:storage:dropbox
    ```
    You'll be prompted to enter the "App key" and "App secret" values obtained earlier.
3. Koel will generate a URL for you to visit and authorize the Dropbox app. Afterward, you'll receive an access code.
4. Enter the access code back into the command line's prompt. Koel will then finalize the setup automatically and start using Dropbox as its storage.

Now when you upload music files to Koel, they'll be stored in your Dropbox app's folder.

:::warning Two-way sync not supported
Koel does not support two-way sync with Dropbox — at least not yet. This means manual changes made to your Dropbox folder will not be reflected in Koel.
:::

<script lang="ts" setup>
import doCreateBucket from '../assets/img/plus/storage/do-create-bucket.webp'
import doBucketSettings from '../assets/img/plus/storage/do-bucket-settings.webp'
import r2CreateBucket from '../assets/img/plus/storage/r2-create-bucket.webp'
import r2BucketSettings from '../assets/img/plus/storage/r2-bucket-settings.webp'
import dropboxCreateApp from '../assets/img/plus/storage/dropbox-create-app.webp'
import dropboxAppSettings from '../assets/img/plus/storage/dropbox-app-permissions.webp'
</script>
