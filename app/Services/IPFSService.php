<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IPFSService
{
    private string $apiUrl;
    private string $apiKey;
    private string $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('blockchain.ipfs.api_url');
        $this->apiKey = config('blockchain.ipfs.api_key');
        $this->secretKey = config('blockchain.ipfs.secret_key');
    }

    public function uploadImage(string $imagePath): ?string
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->secretKey,
            ])->attach(
                'file',
                Storage::get($imagePath),
                basename($imagePath)
            )->post($this->apiUrl . '/pinning/pinFileToIPFS');

            if ($response->successful()) {
                $data = $response->json();
                return $data['IpfsHash'];
            }

            Log::error('IPFS image upload failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('IPFS image upload exception: ' . $e->getMessage());
            return null;
        }
    }

    public function uploadMetadata(array $metadata): ?string
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/pinning/pinJSONToIPFS', [
                'pinataContent' => $metadata,
                'pinataMetadata' => [
                    'name' => 'charlie-unicorn-metadata-' . time(),
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['IpfsHash'];
            }

            Log::error('IPFS metadata upload failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('IPFS metadata upload exception: ' . $e->getMessage());
            return null;
        }
    }

    public function createNFTMetadata(array $cardData, string $imageHash): array
    {
        return [
            'name' => $cardData['name'],
            'description' => "Charlie Unicorn PNFT Battle Card - {$cardData['name']}",
            'image' => config('blockchain.ipfs.gateway') . $imageHash,
            'external_url' => url('/cards/' . $cardData['id']),
            'attributes' => [
                [
                    'trait_type' => 'Rarity',
                    'value' => ucfirst($cardData['rarity'])
                ],
                [
                    'trait_type' => 'Power Level',
                    'value' => $cardData['power_level'],
                    'display_type' => 'number'
                ],
                [
                    'trait_type' => 'Charlie Points',
                    'value' => $cardData['charlie_points'],
                    'display_type' => 'number'
                ],
                [
                    'trait_type' => 'Battle Style',
                    'value' => 'Universal'
                ],
            ],
            'properties' => [
                'game' => 'Charlie Unicorn Battle',
                'type' => 'PNFT Card',
                'card_id' => $cardData['id'],
                'original_attributes' => $cardData['attributes'],
            ]
        ];
    }
}
