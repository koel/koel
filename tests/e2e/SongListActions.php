<?php

namespace E2E;

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\WebDriverKeys;

trait SongListActions
{
    use WebDriverShortcuts;

    public function selectAllSongs()
    {
        $this->click($this->wrapperId); // make sure focus is there before executing shortcut keys
        (new WebDriverActions($this->driver))
            ->keyDown(null, WebDriverKeys::COMMAND)
            ->keyDown(null, 'A')
            ->keyUp(null, 'A')
            ->keyUp(null, WebDriverKeys::COMMAND)
            ->perform();

        return $this;
    }

    public function selectSong($i = 1)
    {
        return $this->click("{$this->wrapperId} tr.song-item:nth-child($i)");
    }

    /**
     * Select a range of songs.
     *
     * @param int $from
     * @param int $to
     *
     * @return $this
     */
    public function selectRange($from = 1, $to = 5)
    {
        $this->click("{$this->wrapperId} tr.song-item:nth-child($from)");

        (new WebDriverActions($this->driver))
            ->keyDown(null, WebDriverKeys::SHIFT)
            ->click($this->el("{$this->wrapperId} tr.song-item:nth-child($to)"))
            ->keyUp(null, WebDriverKeys::SHIFT)
            ->perform();

        return $this;
    }

    /**
     * Deselect (Ctrl/Cmd+click) songs.
     *
     * @return $this
     */
    public function cmdSelectSongs()
    {
        $actions = (new WebDriverActions($this->driver))->keyDown(null, WebDriverKeys::COMMAND);
        foreach (func_get_args() as $i) {
            $actions->click($this->el("{$this->wrapperId} tr.song-item:nth-child($i)"));
        }
        $actions->keyUp(null, WebDriverKeys::COMMAND)->perform();

        return $this;
    }

    public function rightClickOnSong($i = 1)
    {
        $this->rightClick("{$this->wrapperId} tr.song-item:nth-child($i)");

        return $this;
    }

    public function createPlaylist($playlistName)
    {
        // Try adding a song into a new playlist
        $this->click("{$this->wrapperId} .buttons button.btn-add-to");
        $this->typeIn("{$this->wrapperId} .buttons input[type='text']", $playlistName)
            ->enter();

        return $this;
    }
}
