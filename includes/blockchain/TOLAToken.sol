// SPDX-License-Identifier: MIT
pragma solidity ^0.8.17;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/token/ERC20/extensions/ERC20Burnable.sol";
import "@openzeppelin/contracts/security/Pausable.sol";
import "@openzeppelin/contracts/access/AccessControl.sol";
import "@openzeppelin/contracts/utils/math/SafeMath.sol";

/**
 * @title TOLA Token
 * @dev ERC20 Token for VORTEX AI Marketplace, called "Token of Love and Appreciation"
 */
contract TOLAToken is ERC20, ERC20Burnable, Pausable, AccessControl {
    using SafeMath for uint256;
    
    bytes32 public constant MINTER_ROLE = keccak256("MINTER_ROLE");
    bytes32 public constant PAUSER_ROLE = keccak256("PAUSER_ROLE");
    bytes32 public constant MARKETPLACE_ROLE = keccak256("MARKETPLACE_ROLE");
    
    // Royalty tracking
    mapping(address => uint256) private _artistRoyalties;
    
    // Events
    event RoyaltyPaid(address indexed artist, address indexed collector, uint256 amount);
    event MarketplaceFee(address indexed seller, address indexed buyer, uint256 amount);
    
    // Fee structure
    uint256 public marketplaceFeePercent = 5; // 5% marketplace fee
    uint256 public constant MAX_FEE = 10; // Maximum fee 10%
    
    constructor(uint256 initialSupply) ERC20("Token of Love and Appreciation", "TOLA") {
        _grantRole(DEFAULT_ADMIN_ROLE, msg.sender);
        _grantRole(MINTER_ROLE, msg.sender);
        _grantRole(PAUSER_ROLE, msg.sender);
        _grantRole(MARKETPLACE_ROLE, msg.sender);
        
        // Mint initial supply to deployer
        _mint(msg.sender, initialSupply * 10 ** decimals());
    }
    
    /**
     * @dev Creates `amount` new tokens for `to`.
     * Can only be called by accounts with the MINTER_ROLE
     */
    function mint(address to, uint256 amount) public onlyRole(MINTER_ROLE) {
        _mint(to, amount);
    }
    
    /**
     * @dev Pauses all token transfers.
     * Can only be called by accounts with the PAUSER_ROLE
     */
    function pause() public onlyRole(PAUSER_ROLE) {
        _pause();
    }
    
    /**
     * @dev Unpauses all token transfers.
     * Can only be called by accounts with the PAUSER_ROLE
     */
    function unpause() public onlyRole(PAUSER_ROLE) {
        _unpause();
    }
    
    /**
     * @dev Sets the marketplace fee percentage
     * Can only be called by the admin
     */
    function setMarketplaceFee(uint256 newFeePercent) public onlyRole(DEFAULT_ADMIN_ROLE) {
        require(newFeePercent <= MAX_FEE, "Fee exceeds maximum");
        marketplaceFeePercent = newFeePercent;
    }
    
    /**
     * @dev Process a marketplace purchase with fee and potential royalty
     * @param seller Address of the artwork seller
     * @param artist Address of the original artist
     * @param buyer Address of the buyer
     * @param amount Total amount of the transaction
     * @param royaltyPercent Percentage of the sale that goes to the artist as royalty
     */
    function processPurchase(
        address seller, 
        address artist, 
        address buyer, 
        uint256 amount, 
        uint256 royaltyPercent
    ) public onlyRole(MARKETPLACE_ROLE) whenNotPaused returns (bool) {
        require(royaltyPercent <= 100, "Invalid royalty percentage");
        require(balanceOf(buyer) >= amount, "Insufficient balance");
        
        // Calculate fees
        uint256 marketplaceFee = amount.mul(marketplaceFeePercent).div(100);
        uint256 royaltyAmount = amount.mul(royaltyPercent).div(100);
        uint256 sellerAmount = amount.sub(marketplaceFee).sub(royaltyAmount);
        
        // Execute transfers
        _transfer(buyer, address(this), marketplaceFee);
        
        if (royaltyAmount > 0 && artist != seller) {
            _transfer(buyer, artist, royaltyAmount);
            _artistRoyalties[artist] = _artistRoyalties[artist].add(royaltyAmount);
            emit RoyaltyPaid(artist, buyer, royaltyAmount);
        }
        
        _transfer(buyer, seller, sellerAmount);
        emit MarketplaceFee(seller, buyer, marketplaceFee);
        
        return true;
    }
    
    /**
     * @dev Returns the total royalties earned by an artist
     */
    function artistRoyalties(address artist) public view returns (uint256) {
        return _artistRoyalties[artist];
    }
    
    /**
     * @dev Hook that is called before any transfer of tokens
     */
    function _beforeTokenTransfer(
        address from,
        address to,
        uint256 amount
    ) internal override whenNotPaused {
        super._beforeTokenTransfer(from, to, amount);
    }
} 