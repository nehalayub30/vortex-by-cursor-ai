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
pub enum MarketplaceInstruction {
    /// List an artwork for sale
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The seller's account
    /// 1. `[writable]` The listing account
    /// 2. `[]` The NFT mint account
    /// 3. `[writable]` The seller's NFT account
    ListArtwork {
        price: u64,
        royalty_percentage: u8,
    },

    /// Purchase an artwork
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The buyer's account
    /// 1. `[writable]` The listing account
    /// 2. `[writable]` The seller's token account
    /// 3. `[writable]` The buyer's token account
    /// 4. `[writable]` The NFT account
    PurchaseArtwork {
        price: u64,
    },

    /// Cancel a listing
    /// 
    /// Accounts expected:
    /// 0. `[signer]` The seller's account
    /// 1. `[writable]` The listing account
    CancelListing {},
}

#[derive(BorshSerialize, BorshDeserialize, Debug)]
pub struct ArtworkListing {
    pub seller: Pubkey,
    pub nft_mint: Pubkey,
    pub price: u64,
    pub royalty_percentage: u8,
    pub is_active: bool,
}

// Entry point
entrypoint!(process_instruction);

pub fn process_instruction(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    instruction_data: &[u8],
) -> ProgramResult {
    let instruction = MarketplaceInstruction::try_from_slice(instruction_data)?;

    match instruction {
        MarketplaceInstruction::ListArtwork { price, royalty_percentage } => {
            msg!("Instruction: List Artwork");
            process_list_artwork(program_id, accounts, price, royalty_percentage)
        }
        MarketplaceInstruction::PurchaseArtwork { price } => {
            msg!("Instruction: Purchase Artwork");
            process_purchase_artwork(program_id, accounts, price)
        }
        MarketplaceInstruction::CancelListing {} => {
            msg!("Instruction: Cancel Listing");
            process_cancel_listing(program_id, accounts)
        }
    }
}

fn process_list_artwork(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    price: u64,
    royalty_percentage: u8,
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    let seller = next_account_info(account_info_iter)?;
    let listing_account = next_account_info(account_info_iter)?;
    let nft_mint = next_account_info(account_info_iter)?;
    let seller_nft_account = next_account_info(account_info_iter)?;

    // Verify seller is signer
    if !seller.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }

    // Create listing
    let listing = ArtworkListing {
        seller: *seller.key,
        nft_mint: *nft_mint.key,
        price,
        royalty_percentage,
        is_active: true,
    };

    listing.serialize(&mut *listing_account.data.borrow_mut())?;
    Ok(())
}

fn process_purchase_artwork(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    price: u64,
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    let buyer = next_account_info(account_info_iter)?;
    let listing_account = next_account_info(account_info_iter)?;
    let seller_token_account = next_account_info(account_info_iter)?;
    let buyer_token_account = next_account_info(account_info_iter)?;
    let nft_account = next_account_info(account_info_iter)?;

    // Verify buyer is signer
    if !buyer.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }

    // Transfer tokens and NFT
    // Implementation details to be added

    Ok(())
}

fn process_cancel_listing(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    let seller = next_account_info(account_info_iter)?;
    let listing_account = next_account_info(account_info_iter)?;

    // Verify seller is signer
    if !seller.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }

    // Deactivate listing
    let mut listing = ArtworkListing::try_from_slice(&listing_account.data.borrow())?;
    if listing.seller != *seller.key {
        return Err(ProgramError::InvalidAccountData);
    }
    listing.is_active = false;
    listing.serialize(&mut *listing_account.data.borrow_mut())?;

    Ok(())
} 