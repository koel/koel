# Media Browser

Apart from the standard ability to browse your music library via extracted metadata, Koel Plus also provides a
media browser—an interface in Windows Explorer or Finder style—that allows you to browse your music files directly.
This is particularly useful if you have a large collection of music files but without proper ID3 tagging, and prefer
to navigate through them by a folder structure.

## Enabling the Media Browser

Since this is not a common use case, the media browser is not enabled by default. To enable it, set
`MEDIA_BROWSER_ENABLED` to `true` in your `.env` file:

```dotenv
MEDIA_BROWSER_ENABLED=true
```

For the first run, you also need to run the following command to extract the folder structure from your music library.
This may take a while depending on the library size.

```bash
php artisan koel:extract-folders
```

This command only needs to be run once, as Koel will automatically update the folder structure when you add new music
files or perform scans.

Reload Koel, and you should see a new "Media Browser" link in the sidebar. Click it to open the media browser.

## Usage

![Media Browser](../assets/img/plus/media-browser.avif)

The media browser interface is similar to a file explorer. Typically, you will see a list of folders and files, as well
as a breadcrumb at the top for quick navigation. Doubling-clicking a folder will navigate into it, while double-clicking
a song will play it. You can also perform multi-selection and bring up the context menu to perform actions like Play,
Shuffle, Queue, and so on. Dragging and dropping songs or entire folders into the queue or a playlist is also
supported.

## Limitations

The media browser is not a full-fledged file manager. It does not support file operations like renaming, moving, or
deleting files or folders. Also, to keep things simple, browsing another user's upload folder is not supported (though
this limitation may be removed in the future).
