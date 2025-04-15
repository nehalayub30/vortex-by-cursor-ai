// SPDX-License-Identifier: MIT
pragma solidity ^0.8.17;

import "@openzeppelin/contracts/token/ERC20/extensions/ERC20Votes.sol";
import "@openzeppelin/contracts/access/AccessControl.sol";
import "@openzeppelin/contracts/security/Pausable.sol";

/**
 * @title TOLA Token
 * @dev ERC20 token with voting capabilities, used for governance in the VORTEX ecosystem
 */
contract TOLAToken is ERC20Votes, AccessControl, Pausable {
    bytes32 public constant MINTER_ROLE = keccak256("MINTER_ROLE");
    bytes32 public constant DAO_ROLE = keccak256("DAO_ROLE");
    
    // Cap for total token supply
    uint256 public immutable CAP;
    
    // Tracking for voting power boosts based on achievements
    mapping(address => uint256) public achievementBoost;
    
    // Events
    event AchievementBoostUpdated(address indexed user, uint256 newBoost);
    
    /**
     * @dev Constructor that sets up roles and initial token distribution
     * @param initialSupply The initial amount of tokens to mint
     * @param cap Maximum supply of tokens that can ever exist
     * @param admin Address that will have admin rights
     */
    constructor(
        uint256 initialSupply,
        uint256 cap,
        address admin
    ) ERC20("TOLA Governance Token", "TOLA") ERC20Permit("TOLA Governance Token") {
        require(cap >= initialSupply, "Cap must be greater than initial supply");
        CAP = cap;
        
        _grantRole(DEFAULT_ADMIN_ROLE, admin);
        _grantRole(MINTER_ROLE, admin);
        _grantRole(DAO_ROLE, admin);
        
        _mint(admin, initialSupply * (10 ** decimals()));
    }
    
    /**
     * @dev Mints new tokens, respecting the cap
     * @param to Address to receive the tokens
     * @param amount Amount to mint
     */
    function mint(address to, uint256 amount) external onlyRole(MINTER_ROLE) whenNotPaused {
        require(totalSupply() + amount <= CAP, "Cap exceeded");
        _mint(to, amount);
    }
    
    /**
     * @dev Burns tokens from the caller
     * @param amount Amount to burn
     */
    function burn(uint256 amount) external whenNotPaused {
        _burn(_msgSender(), amount);
    }
    
    /**
     * @dev Updates achievement-based voting boost for a user
     * @param user Address of the user
     * @param boost New boost value (represents percentage increase in voting power)
     */
    function updateAchievementBoost(address user, uint256 boost) external onlyRole(DAO_ROLE) {
        achievementBoost[user] = boost;
        emit AchievementBoostUpdated(user, boost);
    }
    
    /**
     * @dev Pauses token transfers and minting
     */
    function pause() external onlyRole(DEFAULT_ADMIN_ROLE) {
        _pause();
    }
    
    /**
     * @dev Unpauses token transfers and minting
     */
    function unpause() external onlyRole(DEFAULT_ADMIN_ROLE) {
        _unpause();
    }
    
    /**
     * @dev Override to add achievement boost to voting power
     */
    function _getVotingUnits(address account) internal view override returns (uint256) {
        uint256 baseVotes = super._getVotingUnits(account);
        if (achievementBoost[account] == 0) {
            return baseVotes;
        }
        
        // Apply achievement boost (e.g., if boost = 25, that's a 25% increase)
        return baseVotes + (baseVotes * achievementBoost[account] / 100);
    }
    
    /**
     * @dev Override to add transfer pause functionality
     */
    function _beforeTokenTransfer(
        address from,
        address to,
        uint256 amount
    ) internal override whenNotPaused {
        super._beforeTokenTransfer(from, to, amount);
    }
} 