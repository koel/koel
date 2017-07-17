<?php

namespace App\Services;

use App\Models\Song;
use Cache;
use GuzzleHttp\Client;
use Log;

class Musixmatch extends RESTfulService
{
    /**
     * Construct an instance of Musixmatch service.
     *
     * @param string      $key    The Musixmatch API key
     * @param Client|null $client The Guzzle HTTP client
     */
    public function __construct($key = null, Client $client = null)
    {
        parent::__construct(
            $key ?: config('koel.musixmatch.key'),
            null,
            'https://api.musixmatch.com/ws/1.1',
            $client ?: new Client()
        );
    }
    
    /**
     * Determine if our application is using Musixmatch.
     *
     * @return bool
     */
    public function enabled()
    {
        return (bool) config('koel.musixmatch.key');
    }
    
    /**
     * @param App\Models\Song $song
     * 
     * @return mixed|null
     */
    public function searchLyricsRelatedToSong(Song $song)
    {
        if($song->hasLyrics() == false)
        {
            if (!$song->artist->isUnknown() && !$song->artist->isVarious()) {
                $lyrics = $this->search($song->title, $song->artist->name);
            } else {
                $lyrics = $this->search($song->title);
            }
            
            return $song->updateSingle($song->title, $song->album->name, $song->artist->name, $lyrics, $song->track, (int) $song->compilationState);
        }
        
        return false;
    }
    
    /**
     * @param string $name
     * @param string $artistName
     * 
     * @return mixed|false
     */
    public function search(string $name, string $artistName = null)
    {
        if (!$this->enabled()) {
            return false;
        }
        
        $uri = sprintf('matcher.lyrics.get?format=jsonp&callback=callback&q_track=%s&q_artist=%s&apikey=%s',
            urlencode($name),
            urlencode($artistName),
            $this->key
        );
        
        try{
            $response = $this->jsonpDecode($this->get($uri));
            
            return $this->getLyrics($response);
        }catch(\Exception $e){
            Log::error($e);

            return false;
        }
    }
    
    /**
     * @param string $response
     * 
     * @return mixed
     */
    private function jsonpDecode($response)
    {
        return json_decode(substr(preg_replace("/[^(]*\((.*)\)/","$1",$response), 0, -1));
    }
    
    /**
     * @param JSON $response
     * 
     * @return string|null
     */
    private function getLyrics($response)
    {
        $lyrics = $response->message->body->lyrics->lyrics_body;
        
        if(strlen($lyrics))
        {
            // Correct Musixmatch spelling mistake (is for are).
            return substr($lyrics, 0, strpos($lyrics, "******* This Lyrics is NOT for Commercial use *******")).
                "*** This Lyrics are NOT for Commercial use ***";
        }
        
        return false;
    }
}