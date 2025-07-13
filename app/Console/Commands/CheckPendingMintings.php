<?php

namespace App\Console\Commands;

use App\Models\NFTMintingTransaction;
use App\Services\NFTMintingService;
use Illuminate\Console\Command;

class CheckPendingMintings extends Command
{
    protected $signature = 'nft:check-pending';
    protected $description = 'Check status of pending NFT minting transactions';

    public function handle(NFTMintingService $mintingService)
    {
        $pendingTransactions = NFTMintingTransaction::where('status', 'minting')
            ->where('created_at', '>', now()->subHours(24))
            ->get();

        $this->info("Checking {$pendingTransactions->count()} pending minting transactions...");

        foreach ($pendingTransactions as $transaction) {
            $mintingService->checkMintingStatus($transaction);
            $this->line("Checked transaction {$transaction->id} - Status: {$transaction->fresh()->status}");
        }

        $this->info('Completed checking pending minting transactions.');
    }
}
