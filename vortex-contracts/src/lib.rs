pub mod token;
pub mod marketplace;
pub mod governance;
pub mod events;

#[cfg(test)]
mod tests;

// Re-export main entry points
pub use token::process_instruction as process_token_instruction;
pub use marketplace::process_instruction as process_marketplace_instruction;
pub use governance::process_instruction as process_governance_instruction;
pub use events::{VortexEvent, emit};

// Error types
#[derive(Debug)]
pub enum VortexError {
    InvalidInstruction,
    InvalidAccount,
    InsufficientFunds,
    InvalidState,
    Unauthorized,
}

impl From<VortexError> for solana_program::program_error::ProgramError {
    fn from(e: VortexError) -> Self {
        solana_program::program_error::ProgramError::Custom(e as u32)
    }
}

pub fn add(left: u64, right: u64) -> u64 {
    left + right
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn it_works() {
        let result = add(2, 2);
        assert_eq!(result, 4);
    }

    #[test]
    fn test_error_conversion() {
        let error = VortexError::InvalidInstruction;
        let program_error: solana_program::program_error::ProgramError = error.into();
        assert!(matches!(program_error, solana_program::program_error::ProgramError::Custom(_)));
    }
}
