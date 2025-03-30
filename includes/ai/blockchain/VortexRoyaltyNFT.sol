// SPDX-License-Identifier: MIT
pragma solidity ^0.8.17;

import "@openzeppelin/contracts/token/ERC721/extensions/ERC721URIStorage.sol";
import "@openzeppelin/contracts/token/common/ERC2981.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/utils/Counters.sol";
import "@openzeppelin/contracts/utils/math/SafeMath.sol";

/**
 * @title VortexRoyaltyNFT
 * @dev Implementation of NFT with royalty management for VORTEX AI AGENTS
 */
contract VortexRoyaltyNFT is ERC721URIStorage, ERC2981, Ownable {
    using Counters for Counters.Counter;
    using SafeMath for uint256;

    // Token ID counter
    Counters.Counter private _tokenIdCounter;

    // Fixed platform royalty percentage (300 = 3%)
    uint96 private constant PLATFORM_ROYALTY_PERCENTAGE = 300;

    // Maximum total royalty (10000 = 100%)
    uint96 private constant MAX_ROYALTY = 10000;

    // Platform wallet address
    address public platformWallet;

    // Mapping from token ID to royalty recipients
    mapping(uint256 => address[]) private _tokenRoyaltyRecipients;

    // Mapping from token ID to royalty shares (in basis points, 1/100 of a percent)
    mapping(uint256 => uint96[]) private _tokenRoyaltyShares;

    // Events
    event RoyaltiesSet(uint256 indexed tokenId, address[] indexed recipients, uint96[] shares);
    event UpdatedPlatformWallet(address indexed oldWallet, address indexed newWallet);

    /**
     * @dev Constructor
     * @param name NFT collection name
     * @param symbol NFT collection symbol
     * @param platformWallet_ Platform wallet address
     */
    constructor(
        string memory name,
        string memory symbol,
        address platformWallet_
    ) ERC721(name, symbol) {
        require(platformWallet_ != address(0), "Platform wallet cannot be zero address");
        platformWallet = platformWallet_;
    }

    /**
     * @dev See {IERC165-supportsInterface}.
     */
    function supportsInterface(bytes4 interfaceId)
        public
        view
        override(ERC721, ERC2981)
        returns (bool)
    {
        return super.supportsInterface(interfaceId);
    }

    /**
     * @dev Mint new NFT with royalties
     * @param to Address to mint the NFT to
     * @param tokenURI URI for the token metadata
     * @param royaltyRecipients Array of royalty recipient addresses
     * @param royaltyShares Array of royalty shares (in basis points)
     */
    function mintWithRoyalties(
        address to,
        string memory tokenURI,
        address[] memory royaltyRecipients,
        uint96[] memory royaltyShares
    ) public returns (uint256) {
        _tokenIdCounter.increment();
        uint256 tokenId = _tokenIdCounter.current();
        
        // Mint the NFT
        _safeMint(to, tokenId);
        _setTokenURI(tokenId, tokenURI);
        
        // Set royalties
        _setTokenRoyalties(tokenId, royaltyRecipients, royaltyShares);
        
        return tokenId;
    }

    /**
     * @dev Set platform wallet address (owner only)
     * @param newWallet New platform wallet address
     */
    function setPlatformWallet(address newWallet) public onlyOwner {
        require(newWallet != address(0), "Platform wallet cannot be zero address");
        
        address oldWallet = platformWallet;
        platformWallet = newWallet;
        
        emit UpdatedPlatformWallet(oldWallet, newWallet);
    }

    /**
     * @dev Get platform wallet address
     */
    function getPlatformWallet() public view returns (address) {
        return platformWallet;
    }

    /**
     * @dev Get creator royalty information for a token
     * @param tokenId Token ID
     */
    function getCreatorRoyaltyInfo(uint256 tokenId) 
        public 
        view 
        returns (address[] memory recipients, uint96[] memory shares) 
    {
        require(_exists(tokenId), "Token does not exist");
        return (_tokenRoyaltyRecipients[tokenId], _tokenRoyaltyShares[tokenId]);
    }

    /**
     * @dev See {IERC2981-royaltyInfo}.
     */
    function royaltyInfo(
        uint256 tokenId,
        uint256 salePrice
    ) public view override returns (address receiver, uint256 royaltyAmount) {
        require(_exists(tokenId), "Token does not exist");
        
        // For ERC-2981 compliance, we return the platform wallet and total royalty amount
        uint256 totalRoyaltyAmount = 0;
        
        // Calculate platform royalty (3%)
        uint256 platformRoyalty = salePrice.mul(PLATFORM_ROYALTY_PERCENTAGE).div(10000);
        totalRoyaltyAmount = totalRoyaltyAmount.add(platformRoyalty);
        
        // Calculate creator royalties
        address[] memory recipients = _tokenRoyaltyRecipients[tokenId];
        uint96[] memory shares = _tokenRoyaltyShares[tokenId];
        
        for (uint i = 0; i < recipients.length; i++) {
            uint256 recipientRoyalty = salePrice.mul(shares[i]).div(10000);
            totalRoyaltyAmount = totalRoyaltyAmount.add(recipientRoyalty);
        }
        
        // Return platform wallet as the receiver for ERC-2981 compliance
        // The actual distribution happens in distributeSaleRoyalties
        return (platformWallet, totalRoyaltyAmount);
    }

    /**
     * @dev Distribute royalties after a sale
     * @param tokenId Token ID
     * @param saleAmount Sale amount
     */
    function distributeSaleRoyalties(uint256 tokenId, uint256 saleAmount) external payable {
        require(_exists(tokenId), "Token does not exist");
        
        // Calculate royalties
        (address receiver, uint256 totalRoyalty) = royaltyInfo(tokenId, saleAmount);
        
        // Verify sufficient funds sent
        require(msg.value >= totalRoyalty, "Insufficient funds for royalty payment");
        
        // Distribute platform royalty
        uint256 platformRoyalty = saleAmount.mul(PLATFORM_ROYALTY_PERCENTAGE).div(10000);
        (bool platformSuccess, ) = platformWallet.call{value: platformRoyalty}("");
        require(platformSuccess, "Platform royalty transfer failed");
        
        // Distribute creator royalties
        address[] memory recipients = _tokenRoyaltyRecipients[tokenId];
        uint96[] memory shares = _tokenRoyaltyShares[tokenId];
        
        for (uint i = 0; i < recipients.length; i++) {
            uint256 recipientRoyalty = saleAmount.mul(shares[i]).div(10000);
            (bool success, ) = recipients[i].call{value: recipientRoyalty}("");
            require(success, "Creator royalty transfer failed");
        }
        
        // Return any excess payment
        uint256 excess = msg.value.sub(totalRoyalty);
        if (excess > 0) {
            (bool returnSuccess, ) = msg.sender.call{value: excess}("");
            require(returnSuccess, "Excess return failed");
        }
    }

    /**
     * @dev Internal function to set token royalties
     * @param tokenId Token ID
     * @param royaltyRecipients Array of royalty recipient addresses
     * @param royaltyShares Array of royalty shares (in basis points)
     */
    function _setTokenRoyalties(
        uint256 tokenId,
        address[] memory royaltyRecipients,
        uint96[] memory royaltyShares
    ) internal {
        // Validate input
        require(royaltyRecipients.length == royaltyShares.length, "Recipients and shares length mismatch");
        require(royaltyRecipients.length > 0, "At least one royalty recipient required");
        
        // Calculate total royalty
        uint256 totalShares = 0;
        for (uint i = 0; i < royaltyShares.length; i++) {
            // Validate recipient address
            require(royaltyRecipients[i] != address(0), "Recipient cannot be zero address");
            
            // Validate share
            require(royaltyShares[i] > 0, "Royalty share must be greater than 0");
            
            totalShares = totalShares.add(royaltyShares[i]);
        }
        
        // Add platform royalty to total
        totalShares = totalShares.add(PLATFORM_ROYALTY_PERCENTAGE);
        
        // Ensure total royalty doesn't exceed maximum
        require(totalShares <= MAX_ROYALTY, "Total royalty exceeds maximum");
        
        // Store royalty information
        _tokenRoyaltyRecipients[tokenId] = royaltyRecipients;
        _tokenRoyaltyShares[tokenId] = royaltyShares;
        
        // Set default royalty info for ERC2981
        // This isn't used directly but helps with marketplace compatibility
        _setTokenRoyalty(tokenId, platformWallet, uint96(totalShares));
        
        emit RoyaltiesSet(tokenId, royaltyRecipients, royaltyShares);
    }
} 