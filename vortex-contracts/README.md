# VORTEX AI Marketplace Smart Contracts

This repository contains the smart contracts for the VORTEX AI Marketplace, built on the Solana blockchain.

## Overview

The VORTEX AI Marketplace consists of three main components:

1. **TOLA Token Contract**
   - SPL Token implementation
   - Vesting schedule management
   - Staking functionality

2. **Marketplace Contract**
   - NFT listing and trading
   - Royalty management
   - Price discovery

3. **Governance Contract**
   - Proposal creation and voting
   - Token-weighted voting
   - Proposal execution

## Building and Testing

```bash
# Build the contracts
cargo build-bpf

# Run tests
cargo test-bpf

# Deploy to devnet
solana program deploy target/deploy/vortex_contracts.so
```

## Contract Architecture

### TOLA Token
- Token management (mint, burn, transfer)
- Vesting schedules for team and advisors
- Staking mechanism for governance

### Marketplace
- Artwork listing and sales
- Royalty distribution
- Platform fee management

### Governance
- Proposal lifecycle management
- Voting mechanism
- Proposal execution

## Security Features

- Multi-signature requirements
- Time-locked operations
- Access control
- Input validation
- State consistency checks

## Development Guidelines

1. All changes must pass tests
2. New features require test coverage
3. Follow Rust formatting guidelines
4. Document public interfaces
5. Use proper error handling

## License

All rights reserved. VORTEX AI Marketplace 2024. 