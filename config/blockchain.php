<?php

return [
    'networks' => [
        'ethereum' => [
            'name' => 'Ethereum Mainnet',
            'chain_id' => 1,
            'rpc_url' => env('ETHEREUM_RPC_URL', 'https://mainnet.infura.io/v3/' . env('INFURA_PROJECT_ID')),
            'contract_address' => env('ETHEREUM_CONTRACT_ADDRESS'),
            'explorer_url' => 'https://etherscan.io',
        ],
        'polygon' => [
            'name' => 'Polygon Mainnet',
            'chain_id' => 137,
            'rpc_url' => env('POLYGON_RPC_URL', 'https://polygon-mainnet.infura.io/v3/' . env('INFURA_PROJECT_ID')),
            'contract_address' => env('POLYGON_CONTRACT_ADDRESS'),
            'explorer_url' => 'https://polygonscan.com',
        ],
        'goerli' => [
            'name' => 'Goerli Testnet',
            'chain_id' => 5,
            'rpc_url' => env('GOERLI_RPC_URL', 'https://goerli.infura.io/v3/' . env('INFURA_PROJECT_ID')),
            'contract_address' => env('GOERLI_CONTRACT_ADDRESS'),
            'explorer_url' => 'https://goerli.etherscan.io',
        ],
    ],

    'ipfs' => [
        'gateway' => env('IPFS_GATEWAY', 'https://ipfs.io/ipfs/'),
        'api_url' => env('IPFS_API_URL', 'https://api.pinata.cloud'),
        'api_key' => env('PINATA_API_KEY'),
        'secret_key' => env('PINATA_SECRET_KEY'),
    ],

    'wallet' => [
        'private_key' => env('MINTING_WALLET_PRIVATE_KEY'),
        'address' => env('MINTING_WALLET_ADDRESS'),
    ],

    'gas' => [
        'limit' => env('GAS_LIMIT', 300000),
        'price_gwei' => env('GAS_PRICE_GWEI', 20),
    ],
];
