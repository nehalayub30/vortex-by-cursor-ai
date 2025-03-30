use solana_program::{
    account_info::{next_account_info, AccountInfo},
    entrypoint,
    entrypoint::ProgramResult,
    msg,
    program_error::ProgramError,
    pubkey::Pubkey,
};
use borsh::{BorshDeserialize, BorshSerialize};
use spl_token::instruction::TokenInstruction;

// Token instruction enum
#[derive(BorshSerialize, BorshDeserialize, Debug)]
pub enum TolaInstruction {
    /// Initialize the TOLA token
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The account of the person deploying the contract
    /// 1. `[writable]` The token mint account
    /// 2. `[]` The rent sysvar
    /// 3. `[]` The token program
    Initialize {
        /// Total supply of tokens
        total_supply: u64,
    },

    /// Create vesting schedule
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The account creating the vesting schedule
    /// 1. `[writable]` The vesting account
    /// 2. `[writable]` The token account to vest
    CreateVesting {
        amount: u64,
        start_timestamp: i64,
        duration: i64,
    },

    /// Enable staking
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The staker
    /// 1. `[writable]` The stake account
    /// 2. `[writable]` The token account to stake from
    Stake {
        amount: u64,
        duration: i64,
    },
}

// Program state
#[derive(BorshSerialize, BorshDeserialize, Debug)]
pub struct TolaState {
    pub total_supply: u64,
    pub mint_authority: Pubkey,
    pub initialized: bool,
}

// Entry point
entrypoint!(process_instruction);

// Program logic
pub fn process_instruction(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    instruction_data: &[u8],
) -> ProgramResult {
    let instruction = TolaInstruction::try_from_slice(instruction_data)?;

    match instruction {
        TolaInstruction::Initialize { total_supply } => {
            msg!("Instruction: Initialize");
            process_initialize(program_id, accounts, total_supply)
        }
        TolaInstruction::CreateVesting { amount, start_timestamp, duration } => {
            msg!("Instruction: Create Vesting");
            process_create_vesting(program_id, accounts, amount, start_timestamp, duration)
        }
        TolaInstruction::Stake { amount, duration } => {
            msg!("Instruction: Stake");
            process_stake(program_id, accounts, amount, duration)
        }
    }
}

// Initialize the token
fn process_initialize(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    total_supply: u64,
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    let initializer = next_account_info(account_info_iter)?;
    let mint_account = next_account_info(account_info_iter)?;
    let rent_account = next_account_info(account_info_iter)?;
    let token_program = next_account_info(account_info_iter)?;

    // Verify the initializer is the signer
    if !initializer.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }

    // Initialize token mint
    // Implementation details to be added

    Ok(())
}

// Create vesting schedule
fn process_create_vesting(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    amount: u64,
    start_timestamp: i64,
    duration: i64,
) -> ProgramResult {
    // Implementation details to be added
    Ok(())
}

// Process staking
fn process_stake(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    amount: u64,
    duration: i64,
) -> ProgramResult {
    // Implementation details to be added
    Ok(())
} 