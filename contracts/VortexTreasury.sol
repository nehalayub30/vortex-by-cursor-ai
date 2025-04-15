// SPDX-License-Identifier: MIT
pragma solidity ^0.8.17;

import "@openzeppelin/contracts/governance/TimelockController.sol";
import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/token/ERC721/IERC721.sol";
import "@openzeppelin/contracts/access/AccessControl.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";

/**
 * @title Vortex Treasury
 * @dev Treasury contract that manages DAO funds with multi-signature functionality
 */
contract VortexTreasury is TimelockController, AccessControl, ReentrancyGuard {
    using SafeERC20 for IERC20;
    
    bytes32 public constant TREASURER_ROLE = keccak256("TREASURER_ROLE");
    
    // Budget allocation tracking
    struct BudgetAllocation {
        string purpose;
        uint256 amount;
        uint256 spent;
        uint256 deadline;
        bool active;
    }
    
    // Mapping from budget ID to budget details
    mapping(uint256 => BudgetAllocation) public budgets;
    uint256 public nextBudgetId;
    
    // Spending proposal tracking
    struct SpendingProposal {
        uint256 budgetId;
        address recipient;
        uint256 amount;
        string purpose;
        uint256 proposalTime;
        address proposer;
        bool executed;
        mapping(address => bool) approvals;
        uint256 approvalCount;
    }
    
    // Mapping from proposal ID to proposal details
    mapping(uint256 => SpendingProposal) public spendingProposals;
    uint256 public nextProposalId;
    
    // Configuration
    uint256 public requiredApprovals;
    uint256 public minDelay; // Minimum delay between proposal and execution
    
    // Events
    event BudgetCreated(uint256 indexed budgetId, string purpose, uint256 amount, uint256 deadline);
    event BudgetModified(uint256 indexed budgetId, uint256 newAmount, uint256 newDeadline);
    event BudgetDeactivated(uint256 indexed budgetId);
    event SpendingProposed(uint256 indexed proposalId, uint256 indexed budgetId, address recipient, uint256 amount, string purpose);
    event SpendingApproved(uint256 indexed proposalId, address approver, uint256 currentApprovals);
    event SpendingExecuted(uint256 indexed proposalId, uint256 indexed budgetId, address recipient, uint256 amount);
    event TokensReceived(address indexed token, address indexed sender, uint256 amount);
    event NftReceived(address indexed nft, address indexed sender, uint256 tokenId);
    event ConfigUpdated(uint256 requiredApprovals, uint256 minDelay);
    
    /**
     * @dev Constructor for initializing the treasury
     * @param minDelay_ Minimum time delay for spending proposals
     * @param treasurers Initial set of treasurer addresses
     * @param admin Admin address
     * @param requiredApprovals_ Minimum required approvals for spending
     */
    constructor(
        uint256 minDelay_,
        address[] memory treasurers,
        address admin,
        uint256 requiredApprovals_
    ) TimelockController(
        minDelay_,
        treasurers, // proposers
        treasurers, // executors
        admin    // admin
    ) {
        require(requiredApprovals_ <= treasurers.length, "Required approvals cannot exceed treasurer count");
        require(requiredApprovals_ > 0, "Required approvals must be positive");
        
        _grantRole(DEFAULT_ADMIN_ROLE, admin);
        
        for (uint256 i = 0; i < treasurers.length; i++) {
            _grantRole(TREASURER_ROLE, treasurers[i]);
        }
        
        requiredApprovals = requiredApprovals_;
        minDelay = minDelay_;
        nextBudgetId = 1;
        nextProposalId = 1;
    }
    
    /**
     * @dev Creates a new budget allocation
     */
    function createBudget(
        string calldata purpose,
        uint256 amount,
        uint256 deadline
    ) external onlyRole(TREASURER_ROLE) returns (uint256) {
        require(deadline > block.timestamp, "Deadline must be in the future");
        
        uint256 budgetId = nextBudgetId;
        nextBudgetId++;
        
        budgets[budgetId] = BudgetAllocation({
            purpose: purpose,
            amount: amount,
            spent: 0,
            deadline: deadline,
            active: true
        });
        
        emit BudgetCreated(budgetId, purpose, amount, deadline);
        return budgetId;
    }
    
    /**
     * @dev Modifies an existing budget
     */
    function modifyBudget(
        uint256 budgetId,
        uint256 newAmount,
        uint256 newDeadline
    ) external onlyRole(TREASURER_ROLE) {
        require(budgets[budgetId].active, "Budget not active");
        require(newAmount >= budgets[budgetId].spent, "New amount cannot be less than spent amount");
        require(newDeadline > block.timestamp, "New deadline must be in the future");
        
        budgets[budgetId].amount = newAmount;
        budgets[budgetId].deadline = newDeadline;
        
        emit BudgetModified(budgetId, newAmount, newDeadline);
    }
    
    /**
     * @dev Deactivates a budget
     */
    function deactivateBudget(uint256 budgetId) external onlyRole(TREASURER_ROLE) {
        require(budgets[budgetId].active, "Budget already inactive");
        
        budgets[budgetId].active = false;
        
        emit BudgetDeactivated(budgetId);
    }
    
    /**
     * @dev Creates a new spending proposal from a budget
     */
    function proposeSpending(
        uint256 budgetId,
        address recipient,
        uint256 amount,
        string calldata purpose
    ) external onlyRole(TREASURER_ROLE) returns (uint256) {
        require(budgets[budgetId].active, "Budget not active");
        require(block.timestamp < budgets[budgetId].deadline, "Budget expired");
        require(budgets[budgetId].spent + amount <= budgets[budgetId].amount, "Exceeds budget allocation");
        require(recipient != address(0), "Invalid recipient");
        
        uint256 proposalId = nextProposalId;
        nextProposalId++;
        
        SpendingProposal storage proposal = spendingProposals[proposalId];
        proposal.budgetId = budgetId;
        proposal.recipient = recipient;
        proposal.amount = amount;
        proposal.purpose = purpose;
        proposal.proposalTime = block.timestamp;
        proposal.proposer = msg.sender;
        proposal.executed = false;
        proposal.approvalCount = 1;  // Proposer automatically approves
        proposal.approvals[msg.sender] = true;
        
        emit SpendingProposed(proposalId, budgetId, recipient, amount, purpose);
        
        // Auto-execute if only one approval required
        if (requiredApprovals == 1) {
            executeSpending(proposalId);
        }
        
        return proposalId;
    }
    
    /**
     * @dev Approves a spending proposal
     */
    function approveSpending(uint256 proposalId) external onlyRole(TREASURER_ROLE) nonReentrant {
        SpendingProposal storage proposal = spendingProposals[proposalId];
        
        require(proposal.proposalTime > 0, "Proposal does not exist");
        require(!proposal.executed, "Proposal already executed");
        require(!proposal.approvals[msg.sender], "Already approved");
        require(budgets[proposal.budgetId].active, "Budget not active");
        require(block.timestamp < budgets[proposal.budgetId].deadline, "Budget expired");
        
        proposal.approvals[msg.sender] = true;
        proposal.approvalCount++;
        
        emit SpendingApproved(proposalId, msg.sender, proposal.approvalCount);
        
        // Execute if threshold reached
        if (proposal.approvalCount >= requiredApprovals) {
            executeSpending(proposalId);
        }
    }
    
    /**
     * @dev Executes a spending proposal after required approvals
     */
    function executeSpending(uint256 proposalId) internal {
        SpendingProposal storage proposal = spendingProposals[proposalId];
        
        // Double-check all conditions
        require(!proposal.executed, "Proposal already executed");
        require(proposal.approvalCount >= requiredApprovals, "Not enough approvals");
        require(budgets[proposal.budgetId].active, "Budget not active");
        require(block.timestamp < budgets[proposal.budgetId].deadline, "Budget expired");
        require(block.timestamp >= proposal.proposalTime + minDelay, "Time delay not met");
        
        BudgetAllocation storage budget = budgets[proposal.budgetId];
        require(budget.spent + proposal.amount <= budget.amount, "Exceeds budget allocation");
        
        // Mark as executed and update budget spent amount
        proposal.executed = true;
        budget.spent += proposal.amount;
        
        // Transfer ETH to recipient
        (bool success, ) = proposal.recipient.call{value: proposal.amount}("");
        require(success, "ETH transfer failed");
        
        emit SpendingExecuted(proposalId, proposal.budgetId, proposal.recipient, proposal.amount);
    }
    
    /**
     * @dev Withdraws ERC20 tokens (requires governance approval)
     */
    function withdrawERC20(
        address token,
        address recipient,
        uint256 amount
    ) external onlyRole(DEFAULT_ADMIN_ROLE) {
        require(recipient != address(0), "Invalid recipient");
        
        IERC20(token).safeTransfer(recipient, amount);
    }
    
    /**
     * @dev Withdraws ERC721 tokens (requires governance approval)
     */
    function withdrawERC721(
        address nft,
        address recipient,
        uint256 tokenId
    ) external onlyRole(DEFAULT_ADMIN_ROLE) {
        require(recipient != address(0), "Invalid recipient");
        
        IERC721(nft).safeTransferFrom(address(this), recipient, tokenId);
    }
    
    /**
     * @dev Updates treasury configuration
     */
    function updateConfig(
        uint256 newRequiredApprovals,
        uint256 newMinDelay
    ) external onlyRole(DEFAULT_ADMIN_ROLE) {
        uint256 treasurerCount = getRoleMemberCount(TREASURER_ROLE);
        require(newRequiredApprovals <= treasurerCount, "Required approvals cannot exceed treasurer count");
        require(newRequiredApprovals > 0, "Required approvals must be positive");
        
        requiredApprovals = newRequiredApprovals;
        minDelay = newMinDelay;
        
        emit ConfigUpdated(newRequiredApprovals, newMinDelay);
    }
    
    /**
     * @dev Function to receive ETH
     */
    receive() external payable {
        emit TokensReceived(address(0), msg.sender, msg.value);
    }
    
    /**
     * @dev Fallback function
     */
    fallback() external payable {
        emit TokensReceived(address(0), msg.sender, msg.value);
    }
    
    /**
     * @dev Get treasury ETH balance
     */
    function getBalance() external view returns (uint256) {
        return address(this).balance;
    }
    
    /**
     * @dev Get treasury ERC20 token balance
     */
    function getTokenBalance(address token) external view returns (uint256) {
        return IERC20(token).balanceOf(address(this));
    }
} 