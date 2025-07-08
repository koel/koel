<?php

namespace App\Http\Controllers\Demo;

use App\Attributes\RequiresDemo;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

#[RequiresDemo]
class NewSessionController extends Controller
{
    private const DEMO_PASSWORD = 'demo';

    public function __invoke(
        Request $request,
        CrawlerDetect $crawlerDetect,
        UserService $service,
        UserRepository $repository,
    ) {
        $email = $crawlerDetect->isCrawler()
            ? 'demo@koel.dev'
            : Str::take(sha1(config('app.key') . $request->ip()), 8) . '@demo.koel.dev';

        $user = $repository->findOneByEmail($email)
            ?? $service->createUser(
                name: 'Koel',
                email: $email,
                plainTextPassword: self::DEMO_PASSWORD,
                isAdmin: false,
            );

        return redirect('/')->with('demo_account', [
            'email' => $user->email,
            'password' => self::DEMO_PASSWORD,
        ]);
    }
}
