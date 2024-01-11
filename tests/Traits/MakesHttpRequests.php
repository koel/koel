<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Testing\TestResponse;

use function Tests\create_user;

trait MakesHttpRequests
{
    /**
     * @param string $method
     * @param string $uri
     * @return TestResponse
     */
    abstract public function json($method, $uri, array $data = [], array $headers = []); // @phpcs:ignore

    private function jsonAs(?User $user, string $method, $uri, array $data = [], array $headers = []): TestResponse
    {
        $user ??= create_user();
        $this->withToken($user->createToken('koel')->plainTextToken);

        return $this->json($method, $uri, $data, $headers);
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

    protected function patchAs(string $url, array $data, ?User $user = null): TestResponse
    {
        return $this->jsonAs($user, 'patch', $url, $data);
    }
}
