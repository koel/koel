<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private function jsonAsUser(?User $user, string $method, $uri, array $data = [], array $headers = []): TestResponse
    {
        $user = $user ?: factory(User::class)->create();
        $headers['X-Requested-With'] = 'XMLHttpRequest';
        $headers['Authorization'] = 'Bearer '.$user->createToken('koel')->plainTextToken;

        return parent::json($method, $uri, $data, $headers);
    }

    protected function getAsUser(string $url, ?User $user = null): TestResponse
    {
        return $this->jsonAsUser($user, 'get', $url);
    }

    protected function deleteAsUser(string $url, array $data = [], ?User $user = null): TestResponse
    {
        return $this->jsonAsUser($user, 'delete', $url, $data);
    }

    protected function postAsUser(string $url, array $data, ?User $user = null): TestResponse
    {
        return $this->jsonAsUser($user, 'post', $url, $data);
    }

    protected function putAsUser(string $url, array $data, ?User $user = null): TestResponse
    {
        return $this->jsonAsUser($user, 'put', $url, $data);
    }
}
