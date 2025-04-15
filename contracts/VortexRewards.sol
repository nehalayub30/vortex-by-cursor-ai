// SPDX-License-Identifier: MIT
pragma solidity ^0.8.17;

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";
import "@openzeppelin/contracts/access/AccessControl.sol";
import "./VortexReputation.sol";

/**
 * @title Vortex Rewards
 * @dev Automated reward distribution based on contribution metrics
 */
contract VortexRewards is AccessControl, ReentrancyGuard {
    using SafeERC20 for IERC20;
    
    bytes32 public constant REWARD_MANAGER_ROLE = keccak256("REWARD_MANAGER_ROLE");
    bytes32 public constant AI_AGENT_ROLE = keccak256("AI_AGENT_ROLE");
    
    // Token contract references
    IERC20 public rewardToken;
    
    // Reputation contract reference
    VortexReputation public reputationContract;
    
    // Reward pool configurations
    struct RewardPool {
        string name;
        uint256 totalAmount;
        uint256 distributedAmount;
        uint256 startTime;
        uint256 endTime;
        uint256 periodLength; // In seconds
        bool active;
    }
    
    // Reward types for different activities
    enum RewardType {
        ContributionBased,     // Based on reputation/contribution scores
        AchievementBased,      // Based on achievements earned
        DailyActivity,         // For daily login/active participation
        ContentCreation,       // For creating new artworks/content
        MarketplaceActivity,   // For buying/selling on marketplace
        GovernanceActivity,    // For governance participation
        AICollaboration,       // For working with AI agents
        CustomChallenge        // For specific challenges or quests
    }
    
    // Mapping from pool ID to pool configuration
    mapping(uint256 => RewardPool) public rewardPools;
    uint256 public nextPoolId;
    
    // Distribution records
    struct DistributionRecord {
        uint256 poolId;
        address user;
        uint256 amount;
        uint256 timestamp;
        RewardType rewardType;
        string metadata;
    }
    
    // Mapping from distribution ID to distribution record
    mapping(uint256 => DistributionRecord) public distributions;
    uint256 public nextDistributionId;
    
    // User reward limits
    struct UserRewardLimit {
        mapping(uint256 => uint256) poolClaimed; // poolId => amount
        mapping(uint8 => uint256) lastClaimTime; // rewardType => timestamp
    }
    
    // Mapping of user reward limits
    mapping(address => UserRewardLimit) private userRewardLimits;
    
    // Events
    event RewardPoolCreated(uint256 indexed poolId, string name, uint256 totalAmount, uint256 startTime, uint256 endTime);
    event RewardPoolFunded(uint256 indexed poolId, uint256 amount);
    event RewardPoolActivated(uint256 indexed poolId);
    event RewardPoolDeactivated(uint256 indexed poolId);
    event RewardDistributed(uint256 indexed distributionId, uint256 indexed poolId, address indexed user, uint256 amount, uint8 rewardType);
    
    /**
     * @dev Constructor for initializing the reward contract
     * @param admin Admin address
     * @param _rewardToken Address of the token used for rewards
     * @param _reputationContract Address of the reputation contract
     */
    constructor(
        address admin,
        address _rewardToken,
        address _reputationContract
    ) {
        _grantRole(DEFAULT_ADMIN_ROLE, admin);
        _grantRole(REWARD_MANAGER_ROLE, admin);
        
        rewardToken = IERC20(_rewardToken);
        reputationContract = VortexReputation(_reputationContract);
        
        nextPoolId = 1;
        nextDistributionId = 1;
    }
    
    /**
     * @dev Creates a new reward pool
     */
    function createRewardPool(
        string calldata name,
        uint256 totalAmount,
        uint256 startTime,
        uint256 endTime,
        uint256 periodLength
    ) external onlyRole(REWARD_MANAGER_ROLE) returns (uint256) {
        require(totalAmount > 0, "Total amount must be positive");
        require(startTime >= block.timestamp, "Start time must be in the future");
        require(endTime > startTime, "End time must be after start time");
        require(periodLength > 0, "Period length must be positive");
        
        uint256 poolId = nextPoolId;
        nextPoolId++;
        
        rewardPools[poolId] = RewardPool({
            name: name,
            totalAmount: totalAmount,
            distributedAmount: 0,
            startTime: startTime,
            endTime: endTime,
            periodLength: periodLength,
            active: true
        });
        
        emit RewardPoolCreated(poolId, name, totalAmount, startTime, endTime);
        return poolId;
    }
    
    /**
     * @dev Adds funds to an existing reward pool
     */
    function fundRewardPool(uint256 poolId, uint256 amount) external onlyRole(REWARD_MANAGER_ROLE) {
        require(rewardPools[poolId].startTime > 0, "Pool does not exist");
        require(amount > 0, "Amount must be positive");
        
        // Transfer tokens from the caller to this contract
        rewardToken.safeTransferFrom(msg.sender, address(this), amount);
        
        // Update pool total amount
        rewardPools[poolId].totalAmount += amount;
        
        emit RewardPoolFunded(poolId, amount);
    }
    
    /**
     * @dev Activates a reward pool
     */
    function activateRewardPool(uint256 poolId) external onlyRole(REWARD_MANAGER_ROLE) {
        require(rewardPools[poolId].startTime > 0, "Pool does not exist");
        require(!rewardPools[poolId].active, "Pool already active");
        
        rewardPools[poolId].active = true;
        
        emit RewardPoolActivated(poolId);
    }
    
    /**
     * @dev Deactivates a reward pool
     */
    function deactivateRewardPool(uint256 poolId) external onlyRole(REWARD_MANAGER_ROLE) {
        require(rewardPools[poolId].startTime > 0, "Pool does not exist");
        require(rewardPools[poolId].active, "Pool already inactive");
        
        rewardPools[poolId].active = false;
        
        emit RewardPoolDeactivated(poolId);
    }
    
    /**
     * @dev Distribute rewards to a user
     */
    function distributeReward(
        uint256 poolId,
        address user,
        uint256 amount,
        uint8 rewardType,
        string calldata metadata
    ) external onlyRole(REWARD_MANAGER_ROLE) nonReentrant returns (uint256) {
        return _distributeReward(poolId, user, amount, rewardType, metadata);
    }
    
    /**
     * @dev Allow AI agents to trigger reward distribution
     */
    function aiDistributeReward(
        uint256 poolId,
        address user,
        uint256 amount,
        uint8 rewardType,
        string calldata metadata
    ) external onlyRole(AI_AGENT_ROLE) nonReentrant returns (uint256) {
        // AI-triggered rewards have lower limits
        uint256 adjustedAmount = amount / 2; // AI-distributed rewards worth half of manual ones
        require(adjustedAmount > 0, "Adjusted amount must be positive");
        
        return _distributeReward(poolId, user, adjustedAmount, rewardType, metadata);
    }
    
    /**
     * @dev Internal function to distribute rewards
     */
    function _distributeReward(
        uint256 poolId,
        address user,
        uint256 amount,
        uint8 rewardType,
        string calldata metadata
    ) internal returns (uint256) {
        RewardPool storage pool = rewardPools[poolId];
        
        require(pool.startTime > 0, "Pool does not exist");
        require(pool.active, "Pool not active");
        require(block.timestamp >= pool.startTime, "Pool not started");
        require(block.timestamp <= pool.endTime, "Pool ended");
        require(pool.distributedAmount + amount <= pool.totalAmount, "Exceeds pool allocation");
        require(rewardType <= uint8(RewardType.CustomChallenge), "Invalid reward type");
        require(amount > 0, "Amount must be positive");
        
        // Check user limits based on reward type
        UserRewardLimit storage userLimit = userRewardLimits[user];
        
        // Check if user has claimed this reward type recently
        if (rewardType != uint8(RewardType.CustomChallenge)) { // Custom challenges don't have time limits
            uint256 cooldownPeriod = getRewardTypeCooldown(RewardType(rewardType));
            require(
                block.timestamp >= userLimit.lastClaimTime[rewardType] + cooldownPeriod,
                "Reward on cooldown"
            );
        }
        
        // Update user's claimed amount for this pool
        userLimit.poolClaimed[poolId] += amount;
        userLimit.lastClaimTime[rewardType] = block.timestamp;
        
        // Update pool's distributed amount
        pool.distributedAmount += amount;
        
        // Create distribution record
        uint256 distributionId = nextDistributionId;
        nextDistributionId++;
        
        distributions[distributionId] = DistributionRecord({
            poolId: poolId,
            user: user,
            amount: amount,
            timestamp: block.timestamp,
            rewardType: RewardType(rewardType),
            metadata: metadata
        });
        
        // Transfer tokens to the user
        rewardToken.safeTransfer(user, amount);
        
        emit RewardDistributed(distributionId, poolId, user, amount, rewardType);
        
        return distributionId;
    }
    
    /**
     * @dev Get cooldown period for different reward types
     */
    function getRewardTypeCooldown(RewardType rewardType) public pure returns (uint256) {
        if (rewardType == RewardType.DailyActivity) {
            return 1 days;
        } else if (rewardType == RewardType.ContentCreation) {
            return 4 hours;
        } else if (rewardType == RewardType.MarketplaceActivity) {
            return 1 hours;
        } else if (rewardType == RewardType.GovernanceActivity) {
            return 12 hours;
        } else if (rewardType == RewardType.AICollaboration) {
            return 6 hours;
        } else if (rewardType == RewardType.ContributionBased) {
            return 1 days;
        } else if (rewardType == RewardType.AchievementBased) {
            return 0; // No cooldown for achievements
        } else {
            return 0; // No cooldown for custom challenges
        }
    }
    
    /**
     * @dev Get user's total rewards claimed
     */
    function getUserTotalRewards(address user) external view returns (uint256) {
        uint256 total = 0;
        
        for (uint256 i = 1; i < nextDistributionId; i++) {
            if (distributions[i].user == user) {
                total += distributions[i].amount;
            }
        }
        
        return total;
    }
    
    /**
     * @dev Get user's rewards by type
     */
    function getUserRewardsByType(address user, uint8 rewardType) external view returns (uint256) {
        uint256 total = 0;
        
        for (uint256 i = 1; i < nextDistributionId; i++) {
            if (distributions[i].user == user && uint8(distributions[i].rewardType) == rewardType) {
                total += distributions[i].amount;
            }
        }
        
        return total;
    }
    
    /**
     * @dev Get user's rewards from a specific pool
     */
    function getUserPoolRewards(address user, uint256 poolId) external view returns (uint256) {
        return userRewardLimits[user].poolClaimed[poolId];
    }
    
    /**
     * @dev Set the reward token address
     */
    function setRewardToken(address newRewardToken) external onlyRole(DEFAULT_ADMIN_ROLE) {
        rewardToken = IERC20(newRewardToken);
    }
    
    /**
     * @dev Set the reputation contract address
     */
    function setReputationContract(address newReputationContract) external onlyRole(DEFAULT_ADMIN_ROLE) {
        reputationContract = VortexReputation(newReputationContract);
    }
    
    /**
     * @dev Withdraw unclaimed rewards after pool end time
     */
    function withdrawUnclaimedRewards(uint256 poolId, address recipient) external onlyRole(DEFAULT_ADMIN_ROLE) {
        RewardPool storage pool = rewardPools[poolId];
        
        require(pool.startTime > 0, "Pool does not exist");
        require(block.timestamp > pool.endTime, "Pool not ended yet");
        
        uint256 unclaimedAmount = pool.totalAmount - pool.distributedAmount;
        require(unclaimedAmount > 0, "No unclaimed rewards");
        
        // Update pool distributed amount
        pool.distributedAmount = pool.totalAmount;
        
        // Transfer unclaimed tokens to recipient
        rewardToken.safeTransfer(recipient, unclaimedAmount);
    }
} 