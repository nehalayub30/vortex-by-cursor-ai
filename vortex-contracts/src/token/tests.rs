use super::*;
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

#[tokio::test]
async fn test_token_initialization() {
    let program_id = Pubkey::new_unique();
    let mut program_test = ProgramTest::new(
        "vortex_contracts",
        program_id,
        processor!(process_instruction),
    );

    // Create accounts
    let initializer = Keypair::new();
    let mint_account = Keypair::new();
    
    // Add accounts to test environment
    program_test.add_account(
        initializer.pubkey(),
        Account {
            lamports: 1_000_000_000,
            data: vec![],
            owner: program_id,
            ..Account::default()
        },
    );

    let (mut banks_client, payer, recent_blockhash) = program_test.start().await;

    // Create initialization instruction
    let init_instruction = TolaInstruction::Initialize {
        total_supply: 1_000_000_000,
    };

    // Create transaction
    let mut transaction = Transaction::new_with_payer(
        &[init_instruction],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer], recent_blockhash);

    // Process transaction
    banks_client.process_transaction(transaction).await.unwrap();

    // Verify initialization
    let mint_account = banks_client
        .get_account(mint_account.pubkey())
        .await
        .unwrap()
        .unwrap();

    assert!(mint_account.data.len() > 0);
}

#[tokio::test]
async fn test_vesting_schedule() {
    let program_id = Pubkey::new_unique();
    let mut program_test = ProgramTest::new(
        "vortex_contracts",
        program_id,
        processor!(process_instruction),
    );

    // Create accounts
    let creator = Keypair::new();
    let vesting_account = Keypair::new();
    let token_account = Keypair::new();

    // Add accounts to test environment
    program_test.add_account(
        creator.pubkey(),
        Account {
            lamports: 1_000_000_000,
            data: vec![],
            owner: program_id,
            ..Account::default()
        },
    );

    let (mut banks_client, payer, recent_blockhash) = program_test.start().await;

    // Create vesting instruction
    let vesting_instruction = TolaInstruction::CreateVesting {
        amount: 100_000,
        start_timestamp: 1_000_000,
        duration: 365 * 24 * 60 * 60, // 1 year
    };

    // Create transaction
    let mut transaction = Transaction::new_with_payer(
        &[vesting_instruction],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &creator], recent_blockhash);

    // Process transaction
    banks_client.process_transaction(transaction).await.unwrap();

    // Verify vesting schedule
    let vesting_account = banks_client
        .get_account(vesting_account.pubkey())
        .await
        .unwrap()
        .unwrap();

    assert!(vesting_account.data.len() > 0);
}

#[tokio::test]
async fn test_staking() {
    let program_id = Pubkey::new_unique();
    let mut program_test = ProgramTest::new(
        "vortex_contracts",
        program_id,
        processor!(process_instruction),
    );

    // Create accounts
    let staker = Keypair::new();
    let stake_account = Keypair::new();
    let token_account = Keypair::new();

    // Add accounts to test environment
    program_test.add_account(
        staker.pubkey(),
        Account {
            lamports: 1_000_000_000,
            data: vec![],
            owner: program_id,
            ..Account::default()
        },
    );

    let (mut banks_client, payer, recent_blockhash) = program_test.start().await;

    // Create staking instruction
    let stake_instruction = TolaInstruction::Stake {
        amount: 50_000,
        duration: 30 * 24 * 60 * 60, // 30 days
    };

    // Create transaction
    let mut transaction = Transaction::new_with_payer(
        &[stake_instruction],
        Some(&payer.pubkey()),
    );
    transaction.sign(&[&payer, &staker], recent_blockhash);

    // Process transaction
    banks_client.process_transaction(transaction).await.unwrap();

    // Verify staking
    let stake_account = banks_client
        .get_account(stake_account.pubkey())
        .await
        .unwrap()
        .unwrap();

    assert!(stake_account.data.len() > 0);
} 