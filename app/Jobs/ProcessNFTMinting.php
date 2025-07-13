<?php

namespace App\Jobs;

use App\Models\NFTMintingTransaction;
use App\Services\NFTMintingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNFTMinting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 minute

    public function __construct(
        private NFTMintingTransaction $mintingTransaction
    ) {}

    public function handle(NFTMintingService $mintingService): void
    {
        Log::info('Processing NFT minting', [
            'minting_tx_id' => $this->mintingTransaction->id
        ]);

        $success = $mintingService->processMinting($this->mintingTransaction);

        if (!$success && $this->attempts() < $this->tries) {
            $this->release($this->backoff * $this->attempts());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NFT minting job failed', [
            'minting_tx_id' => $this->mintingTransaction->id,
            'error' => $exception->getMessage()
        ]);

        $this->mintingTransaction->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
