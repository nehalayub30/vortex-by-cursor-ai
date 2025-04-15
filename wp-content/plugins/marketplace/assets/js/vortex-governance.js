            <div class="vortex-proposal-item">
                <div class="vortex-proposal-header">
                    <h3 class="vortex-proposal-title">
                        <a href="?view=details&proposal_id=${proposal.id}">${proposal.title}</a>
                    </h3>
                    <span class="vortex-proposal-status ${statusClass}">${proposal.status.toUpperCase()}</span>
                </div>
                <div class="vortex-proposal-meta">
                    <div class="vortex-proposal-type">${proposal.proposal_type.replace('_', ' ').toUpperCase()}</div>
                    <div class="vortex-proposal-proposer">
                        <img class="vortex-proposer-avatar" src="${proposal.proposer_avatar}" alt="${proposal.proposer_name}">
                        <span>${proposal.proposer_name}</span>
                    </div>
                    <div class="vortex-proposal-timestamp">${proposal.created_at_formatted}</div>
                    ${timeInfo}
                </div>
                <div class="vortex-proposal-summary">${proposal.summary}</div>
                <div class="vortex-proposal-voting-progress">
                    <div class="vortex-voting-bar">
                        <div class="vortex-vote-for-progress" style="width: ${proposal.for_percentage}%"></div>
                        <div class="vortex-vote-against-progress" style="width: ${proposal.against_percentage}%"></div>
                    </div>
                    <div class="vortex-voting-counts">
                        <span class="vortex-vote-for">${proposal.for_votes.toLocaleString()} For</span>
                        <span class="vortex-vote-against">${proposal.against_votes.toLocaleString()} Against</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Render vote item
     */
    function renderVoteItem(vote) {
        const voteTypeClass = `vote-${vote.vote_type.toLowerCase()}`;
        
        let voteReason = '';
        if (vote.vote_reason) {
            voteReason = `<div class="vortex-vote-reason">"${vote.vote_reason}"</div>`;
        }
        
        return `
            <div class="vortex-vote-item">
                <div class="vortex-vote-header">
                    <div class="vortex-voter-info">
                        <img class="vortex-voter-avatar" src="${vote.voter_avatar}" alt="${vote.voter_name}">
                        <span class="vortex-voter-name">${vote.voter_name}</span>
                        <span class="vortex-voter-address">${VortexWallet.formatWalletAddress(vote.wallet_address)}</span>
                    </div>
                    <div class="vortex-vote-type ${voteTypeClass}">${vote.vote_type}</div>
                </div>
                <div class="vortex-vote-details">
                    <div class="vortex-vote-weight">${vote.vote_weight.toLocaleString()} votes</div>
                    <div class="vortex-vote-timestamp">${vote.created_at_formatted}</div>
                </div>
                ${voteReason}
            </div>
        `;
    }
    
    // Public API
    return {
        init: init,
        loadProposals: loadProposals,
        loadProposalDetails: loadProposalDetails
    };
})();

// Initialize on document ready
jQuery(document).ready(function() {
    VortexGovernance.init();
}); 