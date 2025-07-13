<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserBooster;
use App\Models\CryptoTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createCryptoPayment(Request $request)
    {
        $request->validate([
            'item_type' => 'required|in:boosters,attributes',
            'package' => 'required|string',
            'wallet_address' => 'required|string',
            'crypto_currency' => 'required|in:ETH,BTC,USDT,TON'
        ]);

        $user = $request->user();

        if ($request->item_type === 'boosters') {
            return $this->createBoosterCryptoPayment($request, $user);
        } elseif ($request->item_type === 'attributes') {
            return $this->createAttributeCryptoPayment($request, $user);
        }
    }

    private function createBoosterCryptoPayment(Request $request, User $user)
    {
        $packages = [
            '10' => ['quantity' => 10, 'price_usd' => 10.00],
            '100' => ['quantity' => 100, 'price_usd' => 80.00],
            '1000' => ['quantity' => 1000, 'price_usd' => 600.00],
            '5000' => ['quantity' => 5000, 'price_usd' => 2000.00]
        ];

        $package = $packages[$request->package] ?? null;
        if (!$package) {
            return response()->json(['success' => false, 'message' => 'Invalid package'], 400);
        }

        // Get crypto price conversion
        $cryptoAmount = $this->convertUsdToCrypto($package['price_usd'], $request->crypto_currency);

        // Generate payment address for this transaction
        $paymentAddress = $this->generatePaymentAddress($request->crypto_currency);

        // Create crypto transaction record
        $transaction = CryptoTransaction::create([
            'user_id' => $user->id,
            'transaction_id' => 'tx_' . uniqid(),
            'crypto_currency' => $request->crypto_currency,
            'amount_crypto' => $cryptoAmount,
            'amount_usd' => $package['price_usd'],
            'payment_address' => $paymentAddress,
            'user_wallet_address' => $request->wallet_address,
            'item_type' => 'boosters',
            'item_data' => json_encode(['package' => $request->package, 'quantity' => $package['quantity']]),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30)
        ]);

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->transaction_id,
            'payment_address' => $paymentAddress,
            'amount' => $cryptoAmount,
            'currency' => $request->crypto_currency,
            'expires_at' => $transaction->expires_at,
            'qr_code' => $this->generateQrCode($paymentAddress, $cryptoAmount, $request->crypto_currency)
        ]);
    }

    private function createAttributeCryptoPayment(Request $request, User $user)
    {
        $attributePrice = 10.00;
        $cryptoAmount = $this->convertUsdToCrypto($attributePrice, $request->crypto_currency);
        $paymentAddress = $this->generatePaymentAddress($request->crypto_currency);

        $transaction = CryptoTransaction::create([
            'user_id' => $user->id,
            'transaction_id' => 'tx_' . uniqid(),
            'crypto_currency' => $request->crypto_currency,
            'amount_crypto' => $cryptoAmount,
            'amount_usd' => $attributePrice,
            'payment_address' => $paymentAddress,
            'user_wallet_address' => $request->wallet_address,
            'item_type' => 'attribute',
            'item_data' => json_encode(['attribute_name' => $request->attribute_name]),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30)
        ]);

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->transaction_id,
            'payment_address' => $paymentAddress,
            'amount' => $cryptoAmount,
            'currency' => $request->crypto_currency,
            'expires_at' => $transaction->expires_at,
            'qr_code' => $this->generateQrCode($paymentAddress, $cryptoAmount, $request->crypto_currency)
        ]);
    }

    private function convertUsdToCrypto($usdAmount, $currency)
    {
        // Get current crypto prices from CoinGecko API
        $response = Http::get('https://api.coingecko.com/api/v3/simple/price', [
            'ids' => $this->getCoinGeckoId($currency),
            'vs_currencies' => 'usd'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $coinId = $this->getCoinGeckoId($currency);
            $pricePerCoin = $data[$coinId]['usd'];
            return round($usdAmount / $pricePerCoin, 8);
        }

        // Fallback prices if API fails
        $fallbackPrices = [
            'ETH' => 2000,
            'BTC' => 45000,
            'USDT' => 1,
            'TON' => 2.5
        ];

        return round($usdAmount / $fallbackPrices[$currency], 8);
    }

    private function getCoinGeckoId($currency)
    {
        $mapping = [
            'ETH' => 'ethereum',
            'BTC' => 'bitcoin',
            'USDT' => 'tether',
            'TON' => 'the-open-network'
        ];

        return $mapping[$currency];
    }

    private function generatePaymentAddress($currency)
    {
        // In production, integrate with your crypto wallet service
        // For now, return test addresses
        $testAddresses = [
            'ETH' => '0x' . bin2hex(random_bytes(20)),
            'BTC' => '1' . base58_encode(random_bytes(25)),
            'USDT' => '0x' . bin2hex(random_bytes(20)),
            'TON' => 'EQ' . base64_encode(random_bytes(32))
        ];

        return $testAddresses[$currency];
    }

    private function generateQrCode($address, $amount, $currency)
    {
        // Generate payment URI
        $uri = match($currency) {
            'ETH', 'USDT' => "ethereum:{$address}?value={$amount}",
            'BTC' => "bitcoin:{$address}?amount={$amount}",
            'TON' => "ton://transfer/{$address}?amount={$amount}",
            default => $address
        };

        // Return QR code data URL (you can use a QR code library)
        return "data:image/png;base64," . base64_encode($this->generateQrCodeImage($uri));
    }

    private function generateQrCodeImage($data)
    {
        // Placeholder - integrate with QR code library like endroid/qr-code
        return random_bytes(1000); // Dummy QR code data
    }

    public function checkPaymentStatus(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string'
        ]);

        $transaction = CryptoTransaction::where('transaction_id', $request->transaction_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        // Check blockchain for payment confirmation
        $confirmed = $this->checkBlockchainPayment($transaction);

        if ($confirmed && $transaction->status === 'pending') {
            $this->processConfirmedPayment($transaction);
        }

        return response()->json([
            'success' => true,
            'status' => $transaction->fresh()->status,
            'confirmations' => $transaction->confirmations ?? 0
        ]);
    }

    private function checkBlockchainPayment(CryptoTransaction $transaction)
    {
        // Integrate with blockchain APIs to check for payment
        // This is a simplified example
        switch ($transaction->crypto_currency) {
            case 'ETH':
            case 'USDT':
                return $this->checkEthereumPayment($transaction);
            case 'BTC':
                return $this->checkBitcoinPayment($transaction);
            case 'TON':
                return $this->checkTonPayment($transaction);
            default:
                return false;
        }
    }

    private function checkEthereumPayment(CryptoTransaction $transaction)
    {
        // Check Ethereum blockchain using Web3 or Infura API
        $response = Http::post('https://mainnet.infura.io/v3/' . env('INFURA_PROJECT_ID'), [
            'jsonrpc' => '2.0',
            'method' => 'eth_getBalance',
            'params' => [$transaction->payment_address, 'latest'],
            'id' => 1
        ]);

        if ($response->successful()) {
            $balance = hexdec($response->json()['result']) / pow(10, 18);
            if ($balance >= $transaction->amount_crypto) {
                $transaction->update([
                    'status' => 'confirmed',
                    'confirmations' => 1,
                    'blockchain_tx_hash' => 'dummy_hash_' . uniqid()
                ]);
                return true;
            }
        }

        return false;
    }

    private function checkBitcoinPayment(CryptoTransaction $transaction)
    {
        // Check Bitcoin blockchain using BlockCypher or similar API
        $response = Http::get("https://api.blockcypher.com/v1/btc/main/addrs/{$transaction->payment_address}/balance");

        if ($response->successful()) {
            $data = $response->json();
            $balance = $data['balance'] / 100000000; // Convert satoshi to BTC

            if ($balance >= $transaction->amount_crypto) {
                $transaction->update([
                    'status' => 'confirmed',
                    'confirmations' => $data['n_tx'] ?? 1,
                    'blockchain_tx_hash' => 'dummy_hash_' . uniqid()
                ]);
                return true;
            }
        }

        return false;
    }

    private function checkTonPayment(CryptoTransaction $transaction)
    {
        // Check TON blockchain using TON API
        // Placeholder implementation
        return false;
    }

    private function processConfirmedPayment(CryptoTransaction $transaction)
    {
        $user = $transaction->user;
        $itemData = json_decode($transaction->item_data, true);

        if ($transaction->item_type === 'boosters') {
            UserBooster::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'booster_type' => 'standard'
                ],
                [
                    'quantity' => \DB::raw('quantity + ' . $itemData['quantity']),
                    'power_multiplier' => 100
                ]
            );
        } elseif ($transaction->item_type === 'attribute') {
            $attributes = $user->character_attributes ?? [];
            $attributes[] = $itemData['attribute_name'];
            $user->update(['character_attributes' => $attributes]);
        }

        $transaction->update(['status' => 'completed']);

        Log::info('Crypto payment processed successfully', [
            'transaction_id' => $transaction->transaction_id,
            'user_id' => $user->id,
            'amount' => $transaction->amount_crypto,
            'currency' => $transaction->crypto_currency
        ]);
    }

    public function connectWallet(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string',
            'signature' => 'required|string',
            'message' => 'required|string'
        ]);

        // Verify wallet signature
        $verified = $this->verifyWalletSignature(
            $request->wallet_address,
            $request->message,
            $request->signature
        );

        if (!$verified) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $request->user()->update([
            'wallet_address' => $request->wallet_address
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet connected successfully'
        ]);
    }

    private function verifyWalletSignature($address, $message, $signature)
    {
        // Implement signature verification for different wallet types
        // This is a simplified example
        return true; // In production, use proper signature verification
    }
}
