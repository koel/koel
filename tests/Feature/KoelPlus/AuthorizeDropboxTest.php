<?php

namespace Tests\Feature\KoelPlus;

use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class AuthorizeDropboxTest extends PlusTestCase
{
    #[Test]
    public function authorize(): void
    {
        $this->get('/dropbox/authorize/foo')
            ->assertRedirect(
                "https://www.dropbox.com/oauth2/authorize?client_id=foo&response_type=code&token_access_type=offline",
            );
    }
}
