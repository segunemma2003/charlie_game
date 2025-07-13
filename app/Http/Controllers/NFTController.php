<?php

namespace App\Http\Controllers;

use App\Models\PnftCard;
use App\Models\NFTMintingTransaction;
use App\Services\NFTMintingService;
use Illuminate\Http\Request;

class NFTController extends Controller
{
    protected $mintingService;

    public function __construct(NFTMintingService $mintingService)
    {
        $this->mintingService = $mintingService;
    }


    public function requestMinting(Request $request, PnftCard $card)
    {
        $request->validate([
            'recipient_address' => 'required|string|regex:/^0x[a-fA-F0-9]{40}$/',
            'network' => 'required|in:ethereum,polygon,goerli'
        ]);

        // Check if user owns the card
        if ($card->telegram_user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this card'
            ], 403);
        }

        $mintingTx = $this->mintingService->requestMinting(
            $card,
            $request->recipient_address,
            $request->network
        );

        if (!$mintingTx) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request NFT minting'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'minting_transaction' => [
                'id' => $mintingTx->id,
                'status' => $mintingTx->status,
                'network' => $mintingTx->network,
                'estimated_time' => '5-15 minutes',
            ],
            'message' => 'NFT minting requested successfully'
        ]);
    }

    public function getMintingStatus(Request $request, NFTMintingTransaction $transaction)
    {
        if ($transaction->telegram_user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Check latest status from blockchain
        $this->mintingService->checkMintingStatus($transaction);
        $transaction->refresh();

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'network' => $transaction->network,
                'transaction_hash' => $transaction->transaction_hash,
                'token_id' => $transaction->token_id,
                'confirmations' => $transaction->blockchain_confirmations,
                'explorer_url' => $transaction->blockchain_data['explorer_url'] ?? null,
                'error_message' => $transaction->error_message,
                'created_at' => $transaction->created_at,
            ]
        ]);
    }

    public function getMintingHistory(Request $request)
    {
        $history = $this->mintingService->getMintingHistory($request->user()->id);

        return response()->json([
            'success' => true,
            'minting_history' => $history
        ]);
    }

    public function getTokenMetadata(Request $request, string $tokenId)
    {
        $request->validate([
            'network' => 'required|in:ethereum,polygon,goerli'
        ]);

        $blockchainService = new \App\Services\BlockchainService($request->network);
        $metadata = $blockchainService->getTokenMetadata((int) $tokenId);

        if (!$metadata) {
            return response()->json([
                'success' => false,
                'message' => 'Token metadata not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'metadata' => $metadata
        ]);
    }
}
