// SPDX-License-Identifier: MIT
pragma solidity ^0.8.17;

import "@openzeppelin/contracts/access/AccessControl.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";
import "./VortexAchievement.sol";

/**
 * @title Vortex Reputation
 * @dev Contract for tracking user reputation and contributions in the VORTEX ecosystem
 */
contract VortexReputation is AccessControl, ReentrancyGuard {
    bytes32 public constant REPUTATION_MANAGER_ROLE = keccak256("REPUTATION_MANAGER_ROLE");
    bytes32 public constant AI_AGENT_ROLE = keccak256("AI_AGENT_ROLE");
    
    // Achievement contract reference
    VortexAchievement public achievementContract;
    
    // Contribution types for different platform actions
    enum ContributionType {
        ArtworkCreation,
        ArtworkPurchase,
        ArtworkCuration,
        MarketplaceEngagement,
        GovernanceParticipation,
        CommunityModeration,
        AICollaboration,
        BlockchainValidation
    }
    
    // Structure to track reputation metrics
    struct ReputationData {
        uint256 totalPoints;
        uint256 achievementPoints;
        uint256 contributionPoints;
        uint256 lastUpdateBlock;
        mapping(uint8 => uint256) contributionTypePoints; // Points by contribution type
        uint256 level;
    }
    
    // Reputation data by user
    mapping(address => ReputationData) private reputationData;
    
    // Mapping of level thresholds
    mapping(uint256 => uint256) public levelThresholds;
    
    // Global reputation stats
    uint256 public totalReputationPoints;
    uint256 public totalUsers;
    
    // Events
    event ContributionAdded(address indexed user, uint8 contributionType, uint256 points, string details);
    event ReputationUpdated(address indexed user, uint256 newTotalPoints, uint256 level);
    event LevelUp(address indexed user, uint256 newLevel);
    
    /**
     * @dev Constructor that sets up roles and initial level thresholds
     * @param admin Address that will have admin rights
     * @param achievementAddr Address of the VortexAchievement contract
     */
    constructor(address admin, address achievementAddr) {
        _grantRole(DEFAULT_ADMIN_ROLE, admin);
        _grantRole(REPUTATION_MANAGER_ROLE, admin);
        
        achievementContract = VortexAchievement(achievementAddr);
        
        // Set up default level thresholds
        levelThresholds[1] = 0;      // Level 1: 0 points
        levelThresholds[2] = 100;    // Level 2: 100 points
        levelThresholds[3] = 300;    // Level 3: 300 points
        levelThresholds[4] = 700;    // Level 4: 700 points
        levelThresholds[5] = 1500;   // Level 5: 1,500 points
        levelThresholds[6] = 3000;   // Level 6: 3,000 points
        levelThresholds[7] = 5000;   // Level 7: 5,000 points
        levelThresholds[8] = 8000;   // Level 8: 8,000 points
        levelThresholds[9] = 12000;  // Level 9: 12,000 points
        levelThresholds[10] = 20000; // Level 10: 20,000 points
    }
    
    /**
     * @dev Add contribution points for a user
     * @param user Address of the user
     * @param contributionType Type of contribution
     * @param points Points to add
     * @param details Description of the contribution
     */
    function addContribution(
        address user,
        uint8 contributionType,
        uint256 points,
        string calldata details
    ) external onlyRole(REPUTATION_MANAGER_ROLE) nonReentrant {
        require(contributionType <= uint8(ContributionType.BlockchainValidation), "Invalid contribution type");
        require(points > 0, "Points must be positive");
        
        // Initialize user data if first contribution
        if (reputationData[user].lastUpdateBlock == 0) {
            reputationData[user].level = 1;
            totalUsers++;
        }
        
        // Update contribution points
        reputationData[user].contributionPoints += points;
        reputationData[user].contributionTypePoints[contributionType] += points;
        
        // Update total reputation
        updateUserReputation(user);
        
        // Global stats update
        totalReputationPoints += points;
        
        emit ContributionAdded(user, contributionType, points, details);
    }
    
    /**
     * @dev Allow AI agents to record contributions automatically
     */
    function recordAIDetectedContribution(
        address user,
        uint8 contributionType,
        uint256 points,
        string calldata details
    ) external onlyRole(AI_AGENT_ROLE) nonReentrant {
        // Similar to addContribution but with potentially lower point values
        uint256 adjustedPoints = points / 2; // AI-detected contributions worth half of manual ones
        
        require(contributionType <= uint8(ContributionType.BlockchainValidation), "Invalid contribution type");
        require(adjustedPoints > 0, "Points must be positive");
        
        // Initialize user data if first contribution
        if (reputationData[user].lastUpdateBlock == 0) {
            reputationData[user].level = 1;
            totalUsers++;
        }
        
        // Update contribution points
        reputationData[user].contributionPoints += adjustedPoints;
        reputationData[user].contributionTypePoints[contributionType] += adjustedPoints;
        
        // Update total reputation
        updateUserReputation(user);
        
        // Global stats update
        totalReputationPoints += adjustedPoints;
        
        emit ContributionAdded(user, contributionType, adjustedPoints, details);
    }
    
    /**
     * @dev Update a user's total reputation score and level
     */
    function updateUserReputation(address user) public {
        // Get achievement points
        uint256 achievementPoints = achievementContract.getAchievementPoints(user);
        
        // Update total points
        reputationData[user].achievementPoints = achievementPoints;
        uint256 newTotalPoints = reputationData[user].contributionPoints + achievementPoints;
        reputationData[user].totalPoints = newTotalPoints;
        
        // Check for level up
        uint256 currentLevel = reputationData[user].level;
        uint256 newLevel = currentLevel;
        
        // Find the highest level threshold that the user has passed
        for (uint256 i = 10; i >= 1; i--) {
            if (newTotalPoints >= levelThresholds[i]) {
                newLevel = i;
                break;
            }
        }
        
        // Handle level change
        if (newLevel > currentLevel) {
            reputationData[user].level = newLevel;
            emit LevelUp(user, newLevel);
        }
        
        reputationData[user].lastUpdateBlock = block.number;
        emit ReputationUpdated(user, newTotalPoints, newLevel);
    }
    
    /**
     * @dev Set level thresholds
     */
    function setLevelThreshold(uint256 level, uint256 threshold) external onlyRole(DEFAULT_ADMIN_ROLE) {
        require(level > 0 && level <= 20, "Invalid level");
        // Level 1 must always have 0 threshold
        if (level == 1) {
            require(threshold == 0, "Level 1 threshold must be 0");
        } else {
            require(threshold > levelThresholds[level - 1], "Thresholds must increase with levels");
        }
        
        levelThresholds[level] = threshold;
    }
    
    /**
     * @dev Get user's reputation data
     */
    function getUserReputation(address user) external view returns (
        uint256 totalPoints,
        uint256 contributionPoints,
        uint256 achievementPoints,
        uint256 level
    ) {
        return (
            reputationData[user].totalPoints,
            reputationData[user].contributionPoints,
            reputationData[user].achievementPoints,
            reputationData[user].level
        );
    }
    
    /**
     * @dev Get user's points for a specific contribution type
     */
    function getUserContributionTypePoints(address user, uint8 contributionType) external view returns (uint256) {
        return reputationData[user].contributionTypePoints[contributionType];
    }
    
    /**
     * @dev Get user's level
     */
    function getUserLevel(address user) external view returns (uint256) {
        return reputationData[user].level;
    }
    
    /**
     * @dev Calculate the percentage progress to the next level
     */
    function getLevelProgress(address user) external view returns (uint256) {
        uint256 currentLevel = reputationData[user].level;
        uint256 currentPoints = reputationData[user].totalPoints;
        
        // If at max level, return 100%
        if (currentLevel >= 10) {
            return 100;
        }
        
        uint256 nextLevelThreshold = levelThresholds[currentLevel + 1];
        uint256 currentLevelThreshold = levelThresholds[currentLevel];
        
        // Calculate progress percentage
        uint256 levelPointsRequired = nextLevelThreshold - currentLevelThreshold;
        uint256 userPointsInLevel = currentPoints - currentLevelThreshold;
        
        return (userPointsInLevel * 100) / levelPointsRequired;
    }
    
    /**
     * @dev Check if user meets a minimum reputation requirement
     */
    function meetsReputationRequirement(address user, uint256 requiredPoints) external view returns (bool) {
        return reputationData[user].totalPoints >= requiredPoints;
    }
    
    /**
     * @dev Set the achievement contract address
     */
    function setAchievementContract(address newAchievementAddr) external onlyRole(DEFAULT_ADMIN_ROLE) {
        achievementContract = VortexAchievement(newAchievementAddr);
    }
} 