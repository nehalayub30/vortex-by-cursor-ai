    function _executor() internal view override(Governor, GovernorTimelockControl) returns (address) {
        return super._executor();
    }
    
    /**
     * @dev Function to check if a user can create a specific proposal type
     */
    function canCreateProposalType(address user, ProposalType proposalType) public view returns (bool) {
        uint256 userLevel = reputationContract.getUserLevel(user);
        return userLevel >= minLevelRequired[proposalType];
    }
    
    /**
     * @dev Get all available proposal types that a user can create
     */
    function getAvailableProposalTypes(address user) external view returns (ProposalType[] memory) {
        ProposalType[] memory types = new ProposalType[](5);
        uint8 count = 0;
        
        for (uint8 i = 0; i <= uint8(ProposalType.ContentCuration); i++) {
            if (canCreateProposalType(user, ProposalType(i))) {
                types[count] = ProposalType(i);
                count++;
            }
        }
        
        // Resize array to remove empty slots
        assembly {
            mstore(types, count)
        }
        
        return types;
    }
    
    /**
     * @dev Override voting to provide reputation-weighted voting
     * Not an actual override but works alongside the token-based voting
     */
    function castReputationVote(
        uint256 proposalId,
        uint8 support
    ) external {
        // Ensure proper voting state
        ProposalState status = state(proposalId);
        require(
            status == ProposalState.Active,
            "Governor: vote not currently active"
        );
        
        // Implement in future version:
        // This function would allow users to cast votes with weight based on their reputation
        // rather than token holdings. This would be tracked separately from the token votes
        // and could be combined in a custom vote counting logic.
        
        // For now, we'll revert as this is a placeholder for future functionality
        revert("Reputation voting not yet implemented");
    }
} 