<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends Controller
{
    #[Route('/api/v1/health', methods: ['GET'])]
    public function health(): Response
    {
        return response('О\'кей', 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    #[Route('/api/v1/contact', methods: ['POST'])]
    public function contact(Request $request): JsonResponse
    {
        $bytesReceived = strlen($request->getContent());

        return response()->json([
            'bytes_received' => $bytesReceived,
        ]);
    }
}