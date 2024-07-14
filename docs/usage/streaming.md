# Streaming Music

## Streaming Methods

Koel supports three streaming methods which can be configured via the `STREAMING_METHOD` setting in `.env` file:

* `php`: Uses native PHP file reading. This is the default method.
* `x-accel-redirect`: Only applicable if your webserver is nginx. Uses nginx's [X-Accel](https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/) module, designed for serving larger contents directly to the end user. Refer to [`nginx.conf.example`](https://github.com/koel/koel/blob/master/nginx.conf.example) for a sample nginx configuration file.
* `x-sendfile`: Only applicable if your webserver is Apache (with or without a proxy server like nginx). Uses Apache's [mod_xsendfile](https://tn123.org/mod_xsendfile/) module. You'll need to install and configure the module manually. A sample configuration is as following:
    ```apache
    LoadModule xsendfile_module   libexec/apache2/mod_xsendfile.so

    # These configuration can be put in the VirtualHost directive as well
    <IfModule mod_xsendfile.c>
      XSendFile on
      XSendFilePath /mnt/media
    </IfModule>
    ```
  Note that although its home page denotes "Apache2/Apache2.2," the module itself is known to work with later versions of Apache as well.

:::tip Tip
If you're using [Koel mobile app](https://koel.dev/#mobile) and can't play the songs, try switching the streaming method to `x-accel-redirect` or `x-sendfile` instead of `php`.
:::

:::warning Notice
Koel always uses the native PHP method if you're transcoding or streaming from a cloud storage.
:::

## Transcoding FLAC

Koel supports transcoding FLAC to mp3 on the fly when streaming music. This behavior can be controlled via a `TRANSCODE_FLAC` setting in `.env` file:

* `false`: Disable FLAC transcoding. Koel will stream FLAC files as-is, producing the lossless audio quality. This is the default behavior.
* `true`: Enable FLAC transcoding. Koel will transcode FLAC to mp3 on the fly. You'll need to have [FFmpeg](https://ffmpeg.org/) installed on your server and set its executable path via the `FFMPEG_PATH` setting in the `.env` file. The transcoding quality can also be controlled via `OUTPUT_BIT_RATE` (defaults to `128`).

