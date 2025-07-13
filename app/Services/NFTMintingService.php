<?php

namespace App\Services;

use App\Models\PnftCard;
use App\Models\NFTMintingTransaction;
use App\Jobs\ProcessNFTMinting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NFTMintingService
{
    public function __construct(
        private IPFSService $ipfsService,
        private BlockchainService $blockchainService
    ) {}

    public function requestMinting(PnftCard $card, string $recipientAddress, string $network = 'polygon'): ?NFTMintingTransaction
    {
        try {
            DB::beginTransaction();

            // Check if card is already minted or being minted
            if ($card->token_id || $card->nftMintingTransactions()->where('status', 'pending')->exists()) {
                throw new \Exception('Card is already minted or minting is in progress');
            }

            // Create minting transaction record
            $mintingTx = NFTMintingTransaction::create([
                'pnft_card_id' => $card->id,
                'telegram_user_id' => $card->telegram_user_id,
                'recipient_address' => $recipientAddress,
                'network' => $network,
                'status' => 'pending',
                'metadata' => [
                    'card_name' => $card->name,
                    'rarity' => $card->rarity,
                    'power_level' => $card->power_level,
                    'charlie_points' => $card->charlie_points,
                ]
            ]);

            // Queue the minting job
            ProcessNFTMinting::dispatch($mintingTx)->onQueue('nft-minting');

            DB::commit();

            return $mintingTx;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to request NFT minting: ' . $e->getMessage());
            return null;
        }
    }

    public function processMinting(NFTMintingTransaction $mintingTx): bool
    {
        try {
            $card = $mintingTx->pnftCard;

            // Step 1: Upload image to IPFS
            $imageHash = $this->ipfsService->uploadImage($card->image_path);
            if (!$imageHash) {
                throw new \Exception('Failed to upload image to IPFS');
            }

            // Step 2: Create and upload metadata to IPFS
            $metadata = $this->ipfsService->createNFTMetadata([
                'id' => $card->id,
                'name' => $card->name,
                'rarity' => $card->rarity,
                'power_level' => $card->power_level,
                'charlie_points' => $card->charlie_points,
                'attributes' => $card->attributes,
            ], $imageHash);

            $metadataHash = $this->ipfsService->uploadMetadata($metadata);
            if (!$metadataHash) {
                throw new \Exception('Failed to upload metadata to IPFS');
            }

            $metadataURI = config('blockchain.ipfs.gateway') . $metadataHash;

            // Step 3: Mint NFT on blockchain
            $blockchainService = new BlockchainService($mintingTx->network);
            $mintResult = $blockchainService->mintNFT($mintingTx->recipient_address, $metadataURI);

            if (!$mintResult) {
                throw new \Exception('Failed to mint NFT on blockchain');
            }

            // Step 4: Update records
            DB::transaction(function () use ($mintingTx, $card, $mintResult, $imageHash, $metadataHash, $metadataURI) {
                $mintingTx->update([
                    'transaction_hash' => $mintResult['transaction_hash'],
                    'token_id' => $mintResult['token_id'],
                    'ipfs_image_hash' => $imageHash,
                    'ipfs_metadata_hash' => $metadataHash,
                    'metadata_uri' => $metadataURI,
                    'status' => 'minting',
                    'blockchain_data' => $mintResult,
                ]);

                $card->update([
                    'token_id' => $mintResult['token_id'],
                    'contract_address' => config("blockchain.networks.{$mintingTx->network}.contract_address"),
                ]);
            });

            Log::info('NFT minting initiated', [
                'card_id' => $card->id,
                'transaction_hash' => $mintResult['transaction_hash'],
                'token_id' => $mintResult['token_id']
            ]);

            return true;

        } catch (\Exception $e) {
            $mintingTx->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('NFT minting failed', [
                'minting_tx_id' => $mintingTx->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function checkMintingStatus(NFTMintingTransaction $mintingTx): void
    {
        if ($mintingTx->status !== 'minting' || !$mintingTx->transaction_hash) {
            return;
        }

        try {
            $blockchainService = new BlockchainService($mintingTx->network);
            $txStatus = $blockchainService->getTransactionStatus($mintingTx->transaction_hash);

            if ($txStatus) {
                $mintingTx->update([
                    'blockchain_confirmations' => $txStatus['confirmations'] ?? 0,
                    'gas_used' => $txStatus['gas_used'] ?? null,
                ]);

                if ($txStatus['status'] === 'success' && ($txStatus['confirmations'] ?? 0) >= 3) {
                    $mintingTx->update(['status' => 'completed']);

                    Log::info('NFT minting completed', [
                        'minting_tx_id' => $mintingTx->id,
                        'transaction_hash' => $mintingTx->transaction_hash,
                        'confirmations' => $txStatus['confirmations']
                    ]);

                } elseif ($txStatus['status'] === 'failed') {
                    $mintingTx->update([
                        'status' => 'failed',
                        'error_message' => 'Blockchain transaction failed'
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to check minting status: ' . $e->getMessage());
        }
    }

    public function getMintingHistory(int $userId): array
    {
        return NFTMintingTransaction::where('telegram_user_id', $userId)
            ->with('pnftCard')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'card_name' => $tx->pnftCard->name,
                    'status' => $tx->status,
                    'network' => $tx->network,
                    'transaction_hash' => $tx->transaction_hash,
                    'token_id' => $tx->token_id,
                    'explorer_url' => $tx->blockchain_data['explorer_url'] ?? null,
                    'created_at' => $tx->created_at,
                    'error_message' => $tx->error_message,
                ];
            })
            ->toArray();
    }
}
