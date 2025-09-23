<?php

namespace App\Http\Controllers\Demo;

use App\Attributes\RequiresDemo;
use App\Enums\Acl\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Values\User\UserCreateData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

#[RequiresDemo]
class NewSessionController extends Controller
{
    public function __invoke(
        Request $request,
        CrawlerDetect $crawlerDetect,
        UserService $service,
        UserRepository $repository,
    ) {
        $email = $crawlerDetect->isCrawler()
            ? 'demo@koel.dev'
            : Str::take(sha1(config('app.key') . $request->ip()), 8) . '@' . User::DEMO_USER_DOMAIN;

        $user = $repository->findOneByEmail($email) ?? $service->createUser(UserCreateData::make(
            name: 'Koel',
            email: $email,
            plainTextPassword: User::DEMO_PASSWORD,
            role: Role::ADMIN,
        ));

        return redirect('/')->with('demo_account', [
            'email' => $user->email,
            'password' => User::DEMO_PASSWORD,
        ]);
    }
}
