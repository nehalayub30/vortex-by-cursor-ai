use solana_program::{
    account_info::{next_account_info, AccountInfo},
    entrypoint,
    entrypoint::ProgramResult,
    msg,
    program_error::ProgramError,
    pubkey::Pubkey,
};
use borsh::{BorshDeserialize, BorshSerialize};

#[derive(BorshSerialize, BorshDeserialize, Debug)]
pub enum GovernanceInstruction {
    /// Create a new proposal
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The proposal creator's account
    /// 1. `[writable]` The proposal account
    CreateProposal {
        title: String,
        description: String,
        voting_period: i64,
    },

    /// Cast a vote on a proposal
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The voter's account
    /// 1. `[writable]` The proposal account
    /// 2. `[writable]` The voter's token account
    CastVote {
        vote: bool,
        amount: u64,
    },

    /// Execute a proposal
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The executor's account
    /// 1. `[writable]` The proposal account
    ExecuteProposal {},
}

#[derive(BorshSerialize, BorshDeserialize, Debug)]
pub struct Proposal {
    pub creator: Pubkey,
    pub title: String,
    pub description: String,
    pub start_time: i64,
    pub end_time: i64,
    pub yes_votes: u64,
    pub no_votes: u64,
    pub executed: bool,
}

// Entry point
entrypoint!(process_instruction);

pub fn process_instruction(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    instruction_data: &[u8],
) -> ProgramResult {
    let instruction = GovernanceInstruction::try_from_slice(instruction_data)?;

    match instruction {
        GovernanceInstruction::CreateProposal { title, description, voting_period } => {
            msg!("Instruction: Create Proposal");
            process_create_proposal(program_id, accounts, title, description, voting_period)
        }
        GovernanceInstruction::CastVote { vote, amount } => {
            msg!("Instruction: Cast Vote");
            process_cast_vote(program_id, accounts, vote, amount)
        }
        GovernanceInstruction::ExecuteProposal {} => {
            msg!("Instruction: Execute Proposal");
            process_execute_proposal(program_id, accounts)
        }
    }
}

fn process_create_proposal(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    title: String,
    description: String,
    voting_period: i64,
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    let creator = next_account_info(account_info_iter)?;
    let proposal_account = next_account_info(account_info_iter)?;

    // Verify creator is signer
    if !creator.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }

    // Create proposal
    let proposal = Proposal {
        creator: *creator.key,
        title,
        description,
        start_time: solana_program::clock::Clock::get()?.unix_timestamp,
        end_time: solana_program::clock::Clock::get()?.unix_timestamp + voting_period,
        yes_votes: 0,
        no_votes: 0,
        executed: false,
    };

    proposal.serialize(&mut *proposal_account.data.borrow_mut())?;
    Ok(())
}

fn process_cast_vote(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    vote: bool,
    amount: u64,
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    let voter = next_account_info(account_info_iter)?;
    let proposal_account = next_account_info(account_info_iter)?;
    let voter_token_account = next_account_info(account_info_iter)?;

    // Verify voter is signer
    if !voter.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }

    // Update vote counts
    let mut proposal = Proposal::try_from_slice(&proposal_account.data.borrow())?;
    let current_time = solana_program::clock::Clock::get()?.unix_timestamp;
    
    if current_time > proposal.end_time {
        return Err(ProgramError::InvalidInstructionData);
    }

    if vote {
        proposal.yes_votes += amount;
    } else {
        proposal.no_votes += amount;
    }

    proposal.serialize(&mut *proposal_account.data.borrow_mut())?;
    Ok(())
}

fn process_execute_proposal(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    let executor = next_account_info(account_info_iter)?;
    let proposal_account = next_account_info(account_info_iter)?;

    // Verify executor is signer
    if !executor.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }

    // Execute proposal
    let mut proposal = Proposal::try_from_slice(&proposal_account.data.borrow())?;
    let current_time = solana_program::clock::Clock::get()?.unix_timestamp;
    
    if current_time <= proposal.end_time {
        return Err(ProgramError::InvalidInstructionData);
    }

    if proposal.executed {
        return Err(ProgramError::InvalidAccountData);
    }

    // Check if proposal passed
    if proposal.yes_votes > proposal.no_votes {
        proposal.executed = true;
        proposal.serialize(&mut *proposal_account.data.borrow_mut())?;
    } else {
        return Err(ProgramError::InvalidAccountData);
    }

    Ok(())
} 