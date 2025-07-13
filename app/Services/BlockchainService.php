<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Web3\Web3;
use Web3\Contract;
use Web3\Utils;

class BlockchainService
{
    private array $networkConfig;
    private string $privateKey;
    private string $walletAddress;

    public function __construct(string $network = 'polygon')
    {
        $this->networkConfig = config("blockchain.networks.{$network}");
        $this->privateKey = config('blockchain.wallet.private_key');
        $this->walletAddress = config('blockchain.wallet.address');
    }

    public function mintNFT(string $recipientAddress, string $metadataURI): ?array
    {
        try {
            $web3 = new Web3($this->networkConfig['rpc_url']);

            $contractABI = $this->getContractABI();
            $contract = new Contract($web3->provider, $contractABI);

            $contractInstance = $contract->at($this->networkConfig['contract_address']);

            // Get next token ID
            $tokenId = $this->getNextTokenId();

            // Prepare transaction data
            $transactionData = $contractInstance->getData('mintTo', $recipientAddress, $tokenId, $metadataURI);

            if ($transactionData === null) {
                throw new \RuntimeException('Failed to generate transaction data for mintTo.');
            }

            // Get gas estimate
            $gasEstimate = $this->estimateGas($transactionData);

            // Get current nonce
            $nonce = $this->getNonce();

            // Build transaction
            $transaction = [
                'from' => $this->walletAddress,
                'to' => $this->networkConfig['contract_address'],
                'value' => '0x0',
                'gas' => '0x' . dechex($gasEstimate),
                'gasPrice' => '0x' . dechex((string) Utils::toWei(config('blockchain.gas.price_gwei'), 'gwei')),
                'nonce' => '0x' . dechex($nonce),
                'data' => $transactionData,
                'chainId' => $this->networkConfig['chain_id']
            ];

            // Sign and send transaction
            $signedTransaction = $this->signTransaction($transaction);
            $txHash = $this->sendRawTransaction($signedTransaction);

            return [
                'transaction_hash' => $txHash,
                'token_id' => $tokenId,
                'network' => $this->networkConfig['name'],
                'explorer_url' => $this->networkConfig['explorer_url'] . '/tx/' . $txHash
            ];

        } catch (\Exception $e) {
            Log::error('NFT minting failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getTransactionStatus(string $txHash): ?array
    {
        try {
            $response = Http::post($this->networkConfig['rpc_url'], [
                'jsonrpc' => '2.0',
                'method' => 'eth_getTransactionReceipt',
                'params' => [$txHash],
                'id' => 1
            ]);

            $data = $response->json();

            if (isset($data['result']) && $data['result']) {
                $receipt = $data['result'];

                return [
                    'status' => $receipt['status'] === '0x1' ? 'success' : 'failed',
                    'block_number' => hexdec($receipt['blockNumber']),
                    'gas_used' => hexdec($receipt['gasUsed']),
                    'transaction_hash' => $receipt['transactionHash'],
                    'confirmations' => $this->getConfirmations($receipt['blockNumber'])
                ];
            }

            return ['status' => 'pending'];

        } catch (\Exception $e) {
            Log::error('Failed to get transaction status: ' . $e->getMessage());
            return null;
        }
    }

    public function getTokenMetadata(int $tokenId): ?array
    {
        try {
            $web3 = new Web3($this->networkConfig['rpc_url']);
            $contractABI = $this->getContractABI();
            $contract = new Contract($web3->provider, $contractABI);

            $contractInstance = $contract->at($this->networkConfig['contract_address']);

            $tokenURI = null;
            $contractInstance->call('tokenURI', $tokenId, function ($err, $result) use (&$tokenURI) {
                if (!$err && isset($result[0])) {
                    $tokenURI = $result[0];
                }
            });

            if ($tokenURI) {
                // Fetch metadata from IPFS
                $metadataResponse = Http::get($tokenURI);

                if ($metadataResponse->successful()) {
                    return $metadataResponse->json();
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get token metadata: ' . $e->getMessage());
            return null;
        }
    }

    private function getContractABI(): array
    {
        // Simplified ERC721 ABI for minting
        return [
            [
                'inputs' => [
                    ['name' => 'to', 'type' => 'address'],
                    ['name' => 'tokenId', 'type' => 'uint256'],
                    ['name' => 'uri', 'type' => 'string']
                ],
                'name' => 'mintTo',
                'outputs' => [],
                'stateMutability' => 'nonpayable',
                'type' => 'function'
            ],
            [
                'inputs' => [['name' => 'tokenId', 'type' => 'uint256']],
                'name' => 'tokenURI',
                'outputs' => [['name' => '', 'type' => 'string']],
                'stateMutability' => 'view',
                'type' => 'function'
            ],
            [
                'inputs' => [],
                'name' => 'nextTokenId',
                'outputs' => [['name' => '', 'type' => 'uint256']],
                'stateMutability' => 'view',
                'type' => 'function'
            ]
        ];
    }

    private function getNextTokenId(): int
    {
        // Implementation to get next available token ID
        // This would call the contract's nextTokenId function
        return time(); // Simplified for example
    }

    private function estimateGas(string $data): int
    {
        // Gas estimation logic
        return config('blockchain.gas.limit');
    }

    private function getNonce(): int
    {
        $response = Http::post($this->networkConfig['rpc_url'], [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionCount',
            'params' => [$this->walletAddress, 'pending'],
            'id' => 1
        ]);

        $data = $response->json();
        return hexdec($data['result'] ?? '0x0');
    }

    private function signTransaction(array $transaction): string
    {
        // Transaction signing implementation
        // This would use a proper Web3 library for signing
        return '0x...'; // Placeholder
    }

    private function sendRawTransaction(string $signedTx): string
    {
        $response = Http::post($this->networkConfig['rpc_url'], [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendRawTransaction',
            'params' => [$signedTx],
            'id' => 1
        ]);

        $data = $response->json();
        return $data['result'];
    }

    private function getConfirmations(string $blockNumberHex): int
    {
        $response = Http::post($this->networkConfig['rpc_url'], [
            'jsonrpc' => '2.0',
            'method' => 'eth_blockNumber',
            'params' => [],
            'id' => 1
        ]);

        $data = $response->json();
        $currentBlock = hexdec($data['result']);
        $txBlock = hexdec($blockNumberHex);

        return max(0, $currentBlock - $txBlock);
    }
}
