<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    public function index()
    {
        return response()->json([
            'name' => 'Charlie Unicorn PNFT Battle API',
            'version' => '1.0.0',
            'description' => 'API for the Charlie Unicorn PNFT Battle Game',
            'endpoints' => [
                'authentication' => '/api/auth/telegram',
                'profile' => '/api/profile',
                'cards' => '/api/cards',
                'battles' => '/api/battles',
                'tournaments' => '/api/tournaments',
                'marketplace' => '/api/marketplace',
                'leaderboards' => '/api/leaderboards'
            ]
        ]);
    }

    public function downloadOpenApi()
    {
        // Return OpenAPI specification
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Charlie Unicorn PNFT Battle API',
                'version' => '1.0.0',
                'description' => 'API for managing PNFT card battles'
            ],
            'paths' => [
                // Define your API paths here
            ]
        ];

        return response()->json($spec);
    }
}
