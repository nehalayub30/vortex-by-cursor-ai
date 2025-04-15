                    return Err(ProgramError::InvalidArgument);
                }
                
                Self::UpdateListing {
                    new_price,
                    new_artist_royalty_percentage,
                }
            },
            4 => {
                let swap_fee = rest
                    .get(..8)
                    .and_then(|slice| slice.try_into().ok())
                    .map(u64::from_le_bytes)
                    .ok_or(ProgramError::InvalidInstructionData)?;
                
                Self::SwapArtwork {
                    swap_fee,
                }
            },
            _ => return Err(ProgramError::InvalidInstructionData),
        })
    }
}

// Process ListArtwork instruction
fn process_list_artwork(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    artwork_id: u64,
    price: u64,
    artist_royalty_percentage: u8,
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    
    let seller_account = next_account_info(account_info_iter)?;
    let artwork_account = next_account_info(account_info_iter)?;
    let metadata_account = next_account_info(account_info_iter)?;
    let system_program = next_account_info(account_info_iter)?;
    
    // Ensure the artwork account is owned by this program
    if artwork_account.owner != program_id {
        return Err(ProgramError::IncorrectProgramId);
    }
    
    // Ensure the seller signed the transaction
    if !seller_account.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }
    
    // Validate royalty parameters
    if artist_royalty_percentage > 15 {
        msg!("Artist royalty percentage exceeds maximum (15%)");
        return Err(ProgramError::InvalidArgument);
    }
    
    // Create and save the artwork listing
    let mut artwork_listing = ArtworkListing::default();
    artwork_listing.is_initialized = true;
    artwork_listing.artwork_id = artwork_id;
    artwork_listing.seller = *seller_account.key;
    artwork_listing.price = price;
    artwork_listing.artist_royalty_percentage = artist_royalty_percentage;
    artwork_listing.is_listed = true;
    
    ArtworkListing::pack(artwork_listing, &mut artwork_account.data.borrow_mut())?;
    
    msg!("Artwork listed successfully with ID: {}", artwork_id);
    msg!("Price: {}, Artist Royalty: {}%", price, artist_royalty_percentage);
    
    Ok(())
}

// Process BuyArtwork instruction
fn process_buy_artwork(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    
    let buyer_account = next_account_info(account_info_iter)?;
    let seller_account = next_account_info(account_info_iter)?;
    let artwork_account = next_account_info(account_info_iter)?;
    let metadata_account = next_account_info(account_info_iter)?;
    let artist_wallet_account = next_account_info(account_info_iter)?;
    let vortex_creator_wallet_account = next_account_info(account_info_iter)?;
    let vortex_company_wallet_account = next_account_info(account_info_iter)?;
    let dao_treasury_wallet_account = next_account_info(account_info_iter)?;
    let system_program = next_account_info(account_info_iter)?;
    
    // Ensure the artwork account is owned by this program
    if artwork_account.owner != program_id {
        return Err(ProgramError::IncorrectProgramId);
    }
    
    // Ensure the buyer signed the transaction
    if !buyer_account.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }
    
    // Unpack the artwork listing
    let mut artwork_listing = ArtworkListing::unpack_from_slice(&artwork_account.data.borrow())?;
    
    // Verify the artwork is listed
    if !artwork_listing.is_listed {
        return Err(ProgramError::InvalidAccountData);
    }
    
    // Verify the seller account
    if artwork_listing.seller != *seller_account.key {
        return Err(ProgramError::InvalidAccountData);
    }
    
    // Determine if seller is also the artist (first-time sale)
    let seller_is_creator = *seller_account.key == *artist_wallet_account.key;
    
    // Calculate distribution amounts
    let price = artwork_listing.price;
    let artist_royalty_percentage = artwork_listing.artist_royalty_percentage;
    
    // Fixed percentages based on VORTEX DAO configuration
    let vortex_creator_royalty_percentage = 5; // 5% creator royalty
    let marketplace_commission_percentage = 15; // 15% marketplace commission
    let dao_allocation_percentage = 3; // 3% to DAO treasury
    let admin_allocation_percentage = 7; // 7% to Vortex Inc.
    let creator_allocation_percentage = 5; // 5% to creator (already included in vortex_creator_royalty)
    
    // Calculate amounts
    let marketplace_commission = (price * marketplace_commission_percentage) / 100;
    let dao_allocation = (price * dao_allocation_percentage) / 100;
    let admin_allocation = (price * admin_allocation_percentage) / 100;
    
    // Calculate royalties and seller amount based on first-time sale or resale
    let (artist_royalty, creator_royalty, seller_amount) = if seller_is_creator {
        // First-time sale: artist royalty included in price, creator royalty from commission
        let creator_royalty = (price * vortex_creator_royalty_percentage) / 100;
        let seller_amount = price - marketplace_commission;
        (0, creator_royalty, seller_amount)
    } else {
        // Resale: calculate artist and creator royalties separately
        let artist_royalty = (price * artist_royalty_percentage) / 100;
        let creator_royalty = (price * vortex_creator_royalty_percentage) / 100;
        let seller_amount = price - (marketplace_commission + artist_royalty + creator_royalty);
        (artist_royalty, creator_royalty, seller_amount)
    };
    
    // Distribute funds
    
    // 1. DAO treasury allocation (from marketplace commission)
    invoke_signed(
        &system_instruction::transfer(buyer_account.key, dao_treasury_wallet_account.key, dao_allocation),
        &[buyer_account.clone(), dao_treasury_wallet_account.clone(), system_program.clone()],
        &[],
    )?;
    
    // 2. Admin allocation (from marketplace commission)
    invoke_signed(
        &system_instruction::transfer(buyer_account.key, vortex_company_wallet_account.key, admin_allocation),
        &[buyer_account.clone(), vortex_company_wallet_account.clone(), system_program.clone()],
        &[],
    )?;
    
    // 3. Creator royalty (Marianne Nems)
    invoke_signed(
        &system_instruction::transfer(buyer_account.key, vortex_creator_wallet_account.key, creator_royalty),
        &[buyer_account.clone(), vortex_creator_wallet_account.clone(), system_program.clone()],
        &[],
    )?;
    
    // 4. Artist royalty (if resale)
    if !seller_is_creator && artist_royalty > 0 {
        invoke_signed(
            &system_instruction::transfer(buyer_account.key, artist_wallet_account.key, artist_royalty),
            &[buyer_account.clone(), artist_wallet_account.clone(), system_program.clone()],
            &[],
        )?;
    }
    
    // 5. Seller amount
    invoke_signed(
        &system_instruction::transfer(buyer_account.key, seller_account.key, seller_amount),
        &[buyer_account.clone(), seller_account.clone(), system_program.clone()],
        &[],
    )?;
    
    // Mark artwork as not listed
    artwork_listing.is_listed = false;
    ArtworkListing::pack(artwork_listing, &mut artwork_account.data.borrow_mut())?;
    
    msg!("Artwork purchase completed successfully");
    msg!("Sale price: {}", price);
    msg!("Marketplace commission: {}", marketplace_commission);
    msg!("DAO allocation: {}", dao_allocation);
    msg!("Admin allocation: {}", admin_allocation);
    msg!("Creator royalty: {}", creator_royalty);
    if !seller_is_creator {
        msg!("Artist royalty: {}", artist_royalty);
    }
    msg!("Seller amount: {}", seller_amount);
    
    Ok(())
}

// Process CancelListing instruction
fn process_cancel_listing(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    
    let seller_account = next_account_info(account_info_iter)?;
    let artwork_account = next_account_info(account_info_iter)?;
    let system_program = next_account_info(account_info_iter)?;
    
    // Ensure the artwork account is owned by this program
    if artwork_account.owner != program_id {
        return Err(ProgramError::IncorrectProgramId);
    }
    
    // Ensure the seller signed the transaction
    if !seller_account.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }
    
    // Unpack the artwork listing
    let mut artwork_listing = ArtworkListing::unpack_from_slice(&artwork_account.data.borrow())?;
    
    // Verify the artwork is listed
    if !artwork_listing.is_listed {
        return Err(ProgramError::InvalidAccountData);
    }
    
    // Verify the seller account
    if artwork_listing.seller != *seller_account.key {
        return Err(ProgramError::InvalidAccountData);
    }
    
    // Mark artwork as not listed
    artwork_listing.is_listed = false;
    ArtworkListing::pack(artwork_listing, &mut artwork_account.data.borrow_mut())?;
    
    msg!("Artwork listing cancelled successfully");
    
    Ok(())
}

// Process UpdateListing instruction
fn process_update_listing(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    new_price: u64,
    new_artist_royalty_percentage: u8,
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    
    let seller_account = next_account_info(account_info_iter)?;
    let artwork_account = next_account_info(account_info_iter)?;
    let system_program = next_account_info(account_info_iter)?;
    
    // Ensure the artwork account is owned by this program
    if artwork_account.owner != program_id {
        return Err(ProgramError::IncorrectProgramId);
    }
    
    // Ensure the seller signed the transaction
    if !seller_account.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }
    
    // Validate royalty parameters
    if new_artist_royalty_percentage > 15 {
        msg!("Artist royalty percentage exceeds maximum (15%)");
        return Err(ProgramError::InvalidArgument);
    }
    
    // Unpack the artwork listing
    let mut artwork_listing = ArtworkListing::unpack_from_slice(&artwork_account.data.borrow())?;
    
    // Verify the artwork is listed
    if !artwork_listing.is_listed {
        return Err(ProgramError::InvalidAccountData);
    }
    
    // Verify the seller account
    if artwork_listing.seller != *seller_account.key {
        return Err(ProgramError::InvalidAccountData);
    }
    
    // Update the listing
    artwork_listing.price = new_price;
    artwork_listing.artist_royalty_percentage = new_artist_royalty_percentage;
    
    ArtworkListing::pack(artwork_listing, &mut artwork_account.data.borrow_mut())?;
    
    msg!("Artwork listing updated successfully");
    msg!("New price: {}, New artist royalty: {}%", new_price, new_artist_royalty_percentage);
    
    Ok(())
}

// Process SwapArtwork instruction
fn process_swap_artwork(
    program_id: &Pubkey,
    accounts: &[AccountInfo],
    swap_fee: u64,
) -> ProgramResult {
    let account_info_iter = &mut accounts.iter();
    
    let artist1_account = next_account_info(account_info_iter)?;
    let artist2_account = next_account_info(account_info_iter)?;
    let artwork1_account = next_account_info(account_info_iter)?;
    let artwork2_account = next_account_info(account_info_iter)?;
    let vortex_company_wallet_account = next_account_info(account_info_iter)?;
    let system_program = next_account_info(account_info_iter)?;
    
    // Ensure both artwork accounts are owned by this program
    if artwork1_account.owner != program_id || artwork2_account.owner != program_id {
        return Err(ProgramError::IncorrectProgramId);
    }
    
    // Ensure both artists signed the transaction
    if !artist1_account.is_signer || !artist2_account.is_signer {
        return Err(ProgramError::MissingRequiredSignature);
    }
    
    // Unpack both artwork listings
    let mut artwork1_listing = ArtworkListing::unpack_from_slice(&artwork1_account.data.borrow())?;
    let mut artwork2_listing = ArtworkListing::unpack_from_slice(&artwork2_account.data.borrow())?;
    
    // Verify both artworks are listed
    if !artwork1_listing.is_listed || !artwork2_listing.is_listed {
        return Err(ProgramError::InvalidAccountData);
    }
    
    // Verify the seller accounts
    if artwork1_listing.seller != *artist1_account.key || artwork2_listing.seller != *artist2_account.key {
        return Err(ProgramError::InvalidAccountData);
    }
    
    // Calculate fee per artist (should be 3 USD equivalent in lamports)
    let fee_per_artist = swap_fee / 2;
    
    // Collect swap fee from both artists
    // Artist 1 fee
    invoke_signed(
        &system_instruction::transfer(artist1_account.key, vortex_company_wallet_account.key, fee_per_artist),
        &[artist1_account.clone(), vortex_company_wallet_account.clone(), system_program.clone()],
        &[],
    )?;
    
    // Artist 2 fee
    invoke_signed(
        &system_instruction::transfer(artist2_account.key, vortex_company_wallet_account.key, fee_per_artist),
        &[artist2_account.clone(), vortex_company_wallet_account.clone(), system_program.clone()],
        &[],
    )?;
    
    // Swap the artwork owners
    artwork1_listing.seller = *artist2_account.key;
    artwork2_listing.seller = *artist1_account.key;
    
    // Save the updated listings
    ArtworkListing::pack(artwork1_listing, &mut artwork1_account.data.borrow_mut())?;
    ArtworkListing::pack(artwork2_listing, &mut artwork2_account.data.borrow_mut())?;
    
    msg!("Artwork swap completed successfully");
    msg!("Swap fee per artist: {}", fee_per_artist);
    
    Ok(())
} 