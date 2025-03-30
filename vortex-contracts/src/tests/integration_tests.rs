use solana_program::{
    program_pack::Pack,
    pubkey::Pubkey,
    account_info::AccountInfo,
    rent::Rent,
    sysvar::Sysvar,
};
use solana_program_test::*;
use solana_sdk::{
    signature::{Keypair, Signer},
    transaction::Transaction,
    account::Account,
};
use crate::{
    token::{TolaInstruction, process_instruction as token_process},
    marketplace::{MarketplaceInstruction, process_instruction as marketplace_process},
    governance::{GovernanceInstruction, process_instruction as governance_process},
    events::{VortexEvent, emit},
};

#[tokio::test]
async fn test_token_marketplace_integration() {
    let program_id = Pubkey::new_unique();
    let mut program_test = ProgramTest::new(
        "vortex_contracts",
        program_id,
        processor!(token_process),
    );

    // Create accounts
    let seller = Keypair::new();
    let buyer = Keypair::new();
    let mint_account = Keypair::new();
    let nft_account = Keypair::new();
    let payment_account = Keypair::new();

    // Add accounts to test environment
    program_test.add_account(
        seller.pubkey(),
        Account {
            lamports: 1_000_000_000,
            data: vec![],
            owner: program_id,
            ..Account::default()
        },
    );

    program_test.add_account(
        buyer.pubkey(),
        Account {
            lamports: 1_000_000_000,
            data: vec![],
            owner: program_id,
            ..Account::default()
        },
    );

    let (mut banks_client, payer, recent_blockhash) = program_test.start().await;

    // Initialize token
    let init_token = TolaInstruction::Initialize {
        total_supply: 1_000_000_000,
    };

    let mut transaction = Transaction::new_with_payer(
        &[init_token],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // List artwork
    let list_artwork = MarketplaceInstruction::ListArtwork {
        price: 100_000,
        royalty_percentage: 5,
    };

    let mut transaction = Transaction::new_with_payer(
        &[list_artwork],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &seller], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // Purchase artwork
    let purchase = MarketplaceInstruction::PurchaseArtwork {
        price: 100_000,
    };

    let mut transaction = Transaction::new_with_payer(
        &[purchase],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &buyer], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // Verify token transfer
    let payment_account_info = banks_client
        .get_account(payment_account.pubkey())
        .await
        .unwrap()
        .unwrap();

    assert!(payment_account_info.lamports > 0);
}

#[tokio::test]
async fn test_token_governance_integration() {
    let program_id = Pubkey::new_unique();
    let mut program_test = ProgramTest::new(
        "vortex_contracts",
        program_id,
        processor!(token_process),
    );

    // Create accounts
    let token_holder = Keypair::new();
    let proposal_account = Keypair::new();
    let stake_account = Keypair::new();

    // Add accounts to test environment
    program_test.add_account(
        token_holder.pubkey(),
        Account {
            lamports: 1_000_000_000,
            data: vec![],
            owner: program_id,
            ..Account::default()
        },
    );

    let (mut banks_client, payer, recent_blockhash) = program_test.start().await;

    // Stake tokens
    let stake = TolaInstruction::Stake {
        amount: 50_000,
        duration: 30 * 24 * 60 * 60, // 30 days
    };

    let mut transaction = Transaction::new_with_payer(
        &[stake],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &token_holder], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // Create proposal
    let create_proposal = GovernanceInstruction::CreateProposal {
        title: "Test Proposal".to_string(),
        description: "Test Description".to_string(),
        voting_period: 7 * 24 * 60 * 60, // 7 days
    };

    let mut transaction = Transaction::new_with_payer(
        &[create_proposal],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &token_holder], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // Cast vote
    let vote = GovernanceInstruction::CastVote {
        vote: true,
        amount: 50_000,
    };

    let mut transaction = Transaction::new_with_payer(
        &[vote],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &token_holder], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // Verify voting power
    let proposal_info = banks_client
        .get_account(proposal_account.pubkey())
        .await
        .unwrap()
        .unwrap();

    assert!(proposal_info.data.len() > 0);
}

#[tokio::test]
async fn test_full_marketplace_flow() {
    let program_id = Pubkey::new_unique();
    let mut program_test = ProgramTest::new(
        "vortex_contracts",
        program_id,
        processor!(token_process),
    );

    // Create accounts
    let artist = Keypair::new();
    let collector = Keypair::new();
    let token_account = Keypair::new();
    let nft_account = Keypair::new();
    let listing_account = Keypair::new();

    // Add accounts to test environment
    program_test.add_account(
        artist.pubkey(),
        Account {
            lamports: 1_000_000_000,
            data: vec![],
            owner: program_id,
            ..Account::default()
        },
    );

    program_test.add_account(
        collector.pubkey(),
        Account {
            lamports: 1_000_000_000,
            data: vec![],
            owner: program_id,
            ..Account::default()
        },
    );

    let (mut banks_client, payer, recent_blockhash) = program_test.start().await;

    // Initialize token and mint NFT
    let init_token = TolaInstruction::Initialize {
        total_supply: 1_000_000_000,
    };

    let mut transaction = Transaction::new_with_payer(
        &[init_token],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // List artwork
    let list_artwork = MarketplaceInstruction::ListArtwork {
        price: 100_000,
        royalty_percentage: 5,
    };

    let mut transaction = Transaction::new_with_payer(
        &[list_artwork],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &artist], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // Collector purchases artwork
    let purchase = MarketplaceInstruction::PurchaseArtwork {
        price: 100_000,
    };

    let mut transaction = Transaction::new_with_payer(
        &[purchase],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &collector], recent_blockhash);
    banks_client.process_transaction(transaction).await.unwrap();

    // Verify NFT transfer and payment
    let nft_account_info = banks_client
        .get_account(nft_account.pubkey())
        .await
        .unwrap()
        .unwrap();

    let token_account_info = banks_client
        .get_account(token_account.pubkey())
        .await
        .unwrap()
        .unwrap();

    assert!(nft_account_info.data.len() > 0);
    assert!(token_account_info.lamports > 0);

    // Verify listing is closed
    let listing_account_info = banks_client
        .get_account(listing_account.pubkey())
        .await
        .unwrap()
        .unwrap();

    let listing_data = listing_account_info.data.as_slice();
    assert!(!listing_data.is_empty());
} 