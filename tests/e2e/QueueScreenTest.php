<?php

namespace E2E;

class QueueScreenTest extends TestCase
{
    public function test()
    {
        $this->loginAndGoTo('queue');
        static::assertContains('Current Queue', $this->el('#queueWrapper > h1 > span')->getText());

        // As the queue is currently empty, the "Shuffling all song" link should be there
        $this->click('#queueWrapper a.start');
        $this->see('#queueWrapper .song-item');

        // Clear the queue
        $this->click('#queueWrapper .buttons button.btn-clear-queue');
        static::assertEmpty($this->els('#queueWrapper tr.song-item'));
    }
}
