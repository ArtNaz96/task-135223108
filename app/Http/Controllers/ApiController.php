<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ApiController extends Controller
{
    public function health(): Response
    {
        return response('О\'кей', 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}