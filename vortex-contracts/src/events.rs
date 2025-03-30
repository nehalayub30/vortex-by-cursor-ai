use borsh::{BorshSerialize, BorshDeserialize};
use solana_program::{
    msg,
    pubkey::Pubkey,
};

#[derive(BorshSerialize, BorshDeserialize, Debug)]
pub enum VortexEvent {
    // Token Events
    TokenInitialized {
        mint: Pubkey,
        total_supply: u64,
    },
    VestingCreated {
        beneficiary: Pubkey,
        amount: u64,
        start_time: i64,
        duration: i64,
    },
    StakeCreated {
        staker: Pubkey,
        amount: u64,
        duration: i64,
    },
    
    // Marketplace Events
    ArtworkListed {
        seller: Pubkey,
        nft_mint: Pubkey,
        price: u64,
        royalty_percentage: u8,
    },
    ArtworkPurchased {
        buyer: Pubkey,
        seller: Pubkey,
        nft_mint: Pubkey,
        price: u64,
    },
    ListingCancelled {
        seller: Pubkey,
        nft_mint: Pubkey,
    },
    
    // Governance Events
    ProposalCreated {
        creator: Pubkey,
        proposal_id: Pubkey,
        title: String,
        voting_period: i64,
    },
    VoteCast {
        voter: Pubkey,
        proposal_id: Pubkey,
        amount: u64,
        vote: bool,
    },
    ProposalExecuted {
        proposal_id: Pubkey,
        yes_votes: u64,
        no_votes: u64,
    },
}

impl VortexEvent {
    pub fn log(&self) {
        let serialized = self.try_to_vec().unwrap();
        msg!("EVENT:{}", base64::encode(&serialized));
        
        // Also log human-readable format
        match self {
            VortexEvent::TokenInitialized { mint, total_supply } => {
                msg!("Token Initialized: Mint={}, Supply={}", mint, total_supply);
            }
            VortexEvent::VestingCreated { beneficiary, amount, start_time, duration } => {
                msg!("Vesting Created: Beneficiary={}, Amount={}", beneficiary, amount);
            }
            VortexEvent::StakeCreated { staker, amount, duration } => {
                msg!("Stake Created: Staker={}, Amount={}", staker, amount);
            }
            VortexEvent::ArtworkListed { seller, nft_mint, price, royalty_percentage } => {
                msg!("Artwork Listed: Seller={}, NFT={}, Price={}", seller, nft_mint, price);
            }
            VortexEvent::ArtworkPurchased { buyer, seller, nft_mint, price } => {
                msg!("Artwork Purchased: Buyer={}, NFT={}, Price={}", buyer, nft_mint, price);
            }
            VortexEvent::ListingCancelled { seller, nft_mint } => {
                msg!("Listing Cancelled: Seller={}, NFT={}", seller, nft_mint);
            }
            VortexEvent::ProposalCreated { creator, proposal_id, title, voting_period } => {
                msg!("Proposal Created: ID={}, Title={}", proposal_id, title);
            }
            VortexEvent::VoteCast { voter, proposal_id, amount, vote } => {
                msg!("Vote Cast: Voter={}, Proposal={}, Amount={}, Vote={}", 
                    voter, proposal_id, amount, vote);
            }
            VortexEvent::ProposalExecuted { proposal_id, yes_votes, no_votes } => {
                msg!("Proposal Executed: ID={}, Yes={}, No={}", 
                    proposal_id, yes_votes, no_votes);
            }
        }
    }
}

// Helper function to emit events
pub fn emit(event: VortexEvent) {
    event.log();
} 