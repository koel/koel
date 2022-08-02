<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private function jsonAs(?User $user, string $method, $uri, array $data = [], array $headers = []): TestResponse
    {
        /** @var User $user */
        $user = $user ?: User::factory()->create();
        $this->withToken($user->createToken('koel')->plainTextToken);

        return parent::json($method, $uri, $data, $headers);
    }

    protected function getAs(string $url, ?User $user = null): TestResponse
    {
        return $this->jsonAs($user, 'get', $url);
    }

    protected function deleteAs(string $url, array $data = [], ?User $user = null): TestResponse
    {
        return $this->jsonAs($user, 'delete', $url, $data);
    }

    protected function postAs(string $url, array $data, ?User $user = null): TestResponse
    {
        return $this->jsonAs($user, 'post', $url, $data);
    }

    protected function putAs(string $url, array $data, ?User $user = null): TestResponse
    {
        return $this->jsonAs($user, 'put', $url, $data);
    }
}
