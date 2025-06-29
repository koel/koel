# Streaming Music

## Streaming Methods

Koel supports three streaming methods which can be configured via the `STREAMING_METHOD` setting in `.env` file:

* `php`: Uses native PHP file reading. This is the default method.
* `x-accel-redirect`: Only applicable if your webserver is nginx. Uses
  nginx's [X-Accel](https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/) module, designed for serving
  larger contents directly to the end user. Refer to [`nginx.conf.example`](https://github.com/koel/koel/blob/master/nginx.conf.example)
  for a sample nginx configuration file.
* `x-sendfile`: Only applicable if your webserver is Apache (with or without a proxy server like nginx). Uses
  Apache's [mod_xsendfile](https://tn123.org/mod_xsendfile/) module. You'll need to install and configure the module
  manually. A sample configuration is as follows:
    ```apache
    LoadModule xsendfile_module   libexec/apache2/mod_xsendfile.so

    # These configuration can be put in the VirtualHost directive as well
    <IfModule mod_xsendfile.c>
      XSendFile on
      XSendFilePath /mnt/media
    </IfModule>
    ```
  Note that although its home page denotes "Apache2/Apache2.2," the module itself is known to work with later versions
  of Apache as well.

When streaming from a cloud storage like S3 or Dropbox, Koel will simply use the presigned URL provided by the
storage service. A **pre-signed URL** is a secret, generated on-the-fly URL that provides temporary access to your
(private) files stored in the cloud. Koel sets the expiration time of these URLs to 1 hour.

## Lossless Transcoding

Koel supports transcoding your lossless audio to AAC, a format [widely supported](https://caniuse.com/?search=aac)
by modern browsers. To enable this feature, you'll need to have [FFmpeg](https://ffmpeg.org/) installed on your server
and set its executable path via the `FFMPEG_PATH` setting in the `.env` file. If you use the
[Docker image](https://github.com/koel/docker/), this is already done for you.

The transcode quality can be controlled via `TRANSCODE_BIT_RATE`. The default value is `128`, which means the audio will
be transcoded to 128 kbps AAC. You can set it to a higher value, such as `192`, to improve the audio quality at the cost
of a larger file size. Practically, it's almost impossible to differentiate between 320 kbps AAC and lossless audio.

As transcoding can take some time (albeit typically several seconds) and resources, Koel will cache the transcoded
files for latter use. You should also expect a slight delay when you first play a song that requires transcoding, as
Koel will need to do its transcoding magic first (which can involve downloading the file from your cloud storage if
necessary).

### FLAC Transcoding

Since FLAC is [well-supported](https://caniuse.com/?search=flac) by modern browsers, Koel streams FLAC files as-is by
default and maintains the lossless experience. You can also force transcoding FLAC as well by setting `TRANSCODE_FLAC`
to `true` in the `.env` file.

### Forced Transcoding

For audio formats that aren't [widely supported by browsers](https://caniuse.com/?search=audio%20format),
such as AIFF or APE, Koel will always resort to transcoding. If you upload files in such formats, make sure the FFmpeg
setup and configuration (see above) are correct.

### Transcoding on Mobile

On a mobile device where data usage is a concern, the user might want to instruct Koel to transcode all songs
(regardless of their formats) to a lower bit rate to save bandwidth. This can be done via the
[Preferences screen](./profile-preferences#preferences).
