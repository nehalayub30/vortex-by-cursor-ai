// SPDX-License-Identifier: MIT
pragma solidity ^0.8.17;

import "@openzeppelin/contracts/token/ERC721/extensions/ERC721URIStorage.sol";
import "@openzeppelin/contracts/token/ERC721/extensions/ERC721Enumerable.sol";
import "@openzeppelin/contracts/access/AccessControl.sol";
import "@openzeppelin/contracts/utils/Counters.sol";

/**
 * @title Vortex Achievement
 * @dev NFT contract for achievement badges in the VORTEX ecosystem
 */
contract VortexAchievement is ERC721URIStorage, ERC721Enumerable, AccessControl {
    using Counters for Counters.Counter;
    
    bytes32 public constant ACHIEVEMENT_MANAGER_ROLE = keccak256("ACHIEVEMENT_MANAGER_ROLE");
    Counters.Counter private _tokenIdCounter;
    
    // Achievement types and their properties
    struct AchievementType {
        string name;
        string description;
        uint256 pointValue;
        bool transferable;  // Some achievements might be non-transferable
        bool active;
    }
    
    // Mapping from achievement type ID to achievement properties
    mapping(uint256 => AchievementType) public achievementTypes;
    
    // Mapping from token ID to achievement type
    mapping(uint256 => uint256) public tokenAchievementType;
    
    // Mapping to track achievement types earned by users
    mapping(address => mapping(uint256 => bool)) public userAchievements;
    
    // Counter for achievement types
    Counters.Counter private _achievementTypeCounter;
    
    // Events
    event AchievementTypeCreated(uint256 indexed typeId, string name, uint256 pointValue);
    event AchievementEarned(address indexed user, uint256 indexed typeId, uint256 tokenId);
    
    /**
     * @dev Constructor that sets up roles
     * @param admin Address that will have admin rights
     */
    constructor(address admin) ERC721("VORTEX Achievement", "VACH") {
        _grantRole(DEFAULT_ADMIN_ROLE, admin);
        _grantRole(ACHIEVEMENT_MANAGER_ROLE, admin);
    }
    
    /**
     * @dev Creates a new achievement type
     */
    function createAchievementType(
        string memory name,
        string memory description,
        uint256 pointValue,
        bool transferable
    ) external onlyRole(ACHIEVEMENT_MANAGER_ROLE) returns (uint256) {
        uint256 typeId = _achievementTypeCounter.current();
        _achievementTypeCounter.increment();
        
        achievementTypes[typeId] = AchievementType({
            name: name,
            description: description,
            pointValue: pointValue,
            transferable: transferable,
            active: true
        });
        
        emit AchievementTypeCreated(typeId, name, pointValue);
        return typeId;
    }
    
    /**
     * @dev Mints a new achievement NFT for a user
     */
    function awardAchievement(
        address to,
        uint256 achievementTypeId,
        string memory tokenURI
    ) external onlyRole(ACHIEVEMENT_MANAGER_ROLE) returns (uint256) {
        require(achievementTypes[achievementTypeId].active, "Achievement type not active");
        require(!userAchievements[to][achievementTypeId], "User already has this achievement");
        
        uint256 tokenId = _tokenIdCounter.current();
        _tokenIdCounter.increment();
        
        _safeMint(to, tokenId);
        _setTokenURI(tokenId, tokenURI);
        
        tokenAchievementType[tokenId] = achievementTypeId;
        userAchievements[to][achievementTypeId] = true;
        
        emit AchievementEarned(to, achievementTypeId, tokenId);
        return tokenId;
    }
    
    /**
     * @dev Deactivates an achievement type so it can't be awarded anymore
     */
    function deactivateAchievementType(uint256 typeId) external onlyRole(ACHIEVEMENT_MANAGER_ROLE) {
        require(achievementTypes[typeId].active, "Achievement already inactive");
        achievementTypes[typeId].active = false;
    }
    
    /**
     * @dev Returns all achievement tokenIds owned by an address
     */
    function getAchievementsByOwner(address owner) external view returns (uint256[] memory) {
        uint256 balance = balanceOf(owner);
        uint256[] memory tokenIds = new uint256[](balance);
        
        for (uint256 i = 0; i < balance; i++) {
            tokenIds[i] = tokenOfOwnerByIndex(owner, i);
        }
        
        return tokenIds;
    }
    
    /**
     * @dev Returns achievement types owned by a user
     */
    function getAchievementTypesByOwner(address owner) external view returns (uint256[] memory) {
        uint256 count = 0;
        for (uint256 i = 0; i < _achievementTypeCounter.current(); i++) {
            if (userAchievements[owner][i]) {
                count++;
            }
        }
        
        uint256[] memory typeIds = new uint256[](count);
        uint256 index = 0;
        for (uint256 i = 0; i < _achievementTypeCounter.current(); i++) {
            if (userAchievements[owner][i]) {
                typeIds[index] = i;
                index++;
            }
        }
        
        return typeIds;
    }
    
    /**
     * @dev Gets the total points from achievements owned by an address
     */
    function getAchievementPoints(address owner) external view returns (uint256) {
        uint256 totalPoints = 0;
        
        for (uint256 i = 0; i < _achievementTypeCounter.current(); i++) {
            if (userAchievements[owner][i]) {
                totalPoints += achievementTypes[i].pointValue;
            }
        }
        
        return totalPoints;
    }
    
    /**
     * @dev Override to prevent transfer of non-transferable achievements
     */
    function _beforeTokenTransfer(
        address from,
        address to,
        uint256 tokenId,
        uint256 batchSize
    ) internal override(ERC721, ERC721Enumerable) {
        // Allow minting (from == address(0)) regardless of transferable status
        if (from != address(0)) {
            uint256 achievementTypeId = tokenAchievementType[tokenId];
            require(achievementTypes[achievementTypeId].transferable, "Achievement is not transferable");
            
            // Update the userAchievements mapping when transferring
            if (to != address(0)) { // Not burning
                userAchievements[to][achievementTypeId] = true;
            }
            userAchievements[from][achievementTypeId] = false;
        }
        
        super._beforeTokenTransfer(from, to, tokenId, batchSize);
    }
    
    /**
     * @dev Required override for inherited contracts
     */
    function supportsInterface(bytes4 interfaceId)
        public
        view
        override(ERC721, ERC721Enumerable, AccessControl)
        returns (bool)
    {
        return super.supportsInterface(interfaceId);
    }
    
    /**
     * @dev Required override for inherited contracts
     */
    function _burn(uint256 tokenId) internal override(ERC721, ERC721URIStorage) {
        super._burn(tokenId);
    }
    
    /**
     * @dev Required override for inherited contracts
     */
    function tokenURI(uint256 tokenId)
        public
        view
        override(ERC721, ERC721URIStorage)
        returns (string memory)
    {
        return super.tokenURI(tokenId);
    }
} 