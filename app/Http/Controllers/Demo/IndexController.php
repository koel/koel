<?php

namespace App\Http\Controllers\Demo;

use App\Attributes\RequiresDemo;
use App\Http\Controllers\Controller;

#[RequiresDemo]
class IndexController extends Controller
{
    public function __invoke()
    {
        if (!request()->session()->has('demo_account')) {
            // redirect to the new session controller to create or get a demo account
            return redirect()->route('demo.new-session');
        }

        return view('index', [
            'demo_account' => request()->session()->get('demo_account'),
        ]);
    }
}
