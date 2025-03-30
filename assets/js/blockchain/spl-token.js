/**
 * VORTEX SPL Token Handler
 * Manages SPL token operations with AI agent integration
 */

class VortexSPLToken {
    constructor(config) {
        this.connection = null;
        this.wallet = null;
        this.aiManager = null;
        this.programId = null;
        this.mint = null;

        // Configuration and initialization
        this.config = {
            rpcUrl: config.rpcUrl || 'https://api.mainnet-beta.solana.com',
            wsUrl: config.wsUrl || 'wss://api.mainnet-beta.solana.com',
            commitment: config.commitment || 'confirmed',
            aiEndpoint: config.aiEndpoint || '/wp-admin/admin-ajax.php',
            nonce: config.nonce || '',
            debug: config.debug || false
        };

        this.init();
    }

    /**
     * Initialize SPL Token handler with AI integration
     */
    async init() {
        try {
            // Initialize Solana connection
            this.connection = new solanaWeb3.Connection(
                this.config.rpcUrl,
                this.config.commitment
            );

            // Initialize AI Manager for token operations
            this.aiManager = new VortexAIManager({
                huraii: true,
                cloe: true,
                businessStrategist: true,
                endpoint: this.config.aiEndpoint,
                nonce: this.config.nonce
            });

            // Track initialization for AI learning
            await this.aiManager.trackEvent('spl_token_init', {
                timestamp: Date.now(),
                network: this.config.rpcUrl
            });

        } catch (error) {
            this.handleError('Initialization failed', error);
        }
    }

    /**
     * Create new SPL Token with AI-optimized parameters
     */
    async createToken(params) {
        try {
            // Validate wallet connection
            if (!this.wallet) {
                throw new Error('Wallet not connected');
            }

            // Get AI recommendations for token parameters
            const aiRecommendations = await this.aiManager.getTokenRecommendations(params);

            // Merge AI recommendations with user params
            const tokenParams = {
                ...params,
                ...aiRecommendations,
                decimals: params.decimals || aiRecommendations.decimals || 9
            };

            // Create mint account
            const mintAccount = await this.createMintAccount(tokenParams);
            
            // Initialize mint
            await this.initializeMint(mintAccount, tokenParams);

            // Track token creation for AI learning
            await this.aiManager.trackEvent('token_created', {
                mint: mintAccount.publicKey.toString(),
                params: tokenParams
            });

            return mintAccount.publicKey.toString();

        } catch (error) {
            this.handleError('Token creation failed', error);
            throw error;
        }
    }

    /**
     * Create mint account with AI-verified parameters
     */
    async createMintAccount(params) {
        try {
            const mintAccount = new solanaWeb3.Keypair();
            
            // Get AI-verified rent exemption
            const rentExemption = await this.connection.getMinimumBalanceForRentExemption(
                solanaWeb3.MintLayout.span
            );

            const transaction = new solanaWeb3.Transaction().add(
                solanaWeb3.SystemProgram.createAccount({
                    fromPubkey: this.wallet.publicKey,
                    newAccountPubkey: mintAccount.publicKey,
                    lamports: rentExemption,
                    space: solanaWeb3.MintLayout.span,
                    programId: solanaWeb3.TOKEN_PROGRAM_ID
                })
            );

            // Sign and send transaction
            const signature = await this.sendAndConfirmTransaction(transaction, [
                this.wallet,
                mintAccount
            ]);

            return mintAccount;

        } catch (error) {
            this.handleError('Mint account creation failed', error);
            throw error;
        }
    }

    /**
     * Initialize mint with AI-optimized settings
     */
    async initializeMint(mintAccount, params) {
        try {
            // Get AI-optimized freeze authority settings
            const freezeAuthority = await this.aiManager.getRecommendedFreezeAuthority(params);

            const transaction = new solanaWeb3.Transaction().add(
                solanaWeb3.Token.createInitMintInstruction(
                    solanaWeb3.TOKEN_PROGRAM_ID,
                    mintAccount.publicKey,
                    params.decimals,
                    this.wallet.publicKey,
                    freezeAuthority
                )
            );

            await this.sendAndConfirmTransaction(transaction, [this.wallet]);

        } catch (error) {
            this.handleError('Mint initialization failed', error);
            throw error;
        }
    }

    /**
     * Create token account with AI verification
     */
    async createTokenAccount(owner, mint) {
        try {
            // Verify parameters with AI
            await this.aiManager.verifyTokenAccountCreation({
                owner: owner.toString(),
                mint: mint.toString()
            });

            const account = new solanaWeb3.Keypair();
            
            const transaction = new solanaWeb3.Transaction().add(
                solanaWeb3.Token.createInitAccountInstruction(
                    solanaWeb3.TOKEN_PROGRAM_ID,
                    mint,
                    account.publicKey,
                    owner
                )
            );

            await this.sendAndConfirmTransaction(transaction, [this.wallet, account]);
            
            return account.publicKey;

        } catch (error) {
            this.handleError('Token account creation failed', error);
            throw error;
        }
    }

    /**
     * Transfer tokens with AI risk assessment
     */
    async transfer(params) {
        try {
            // Perform AI risk assessment
            const riskAssessment = await this.aiManager.assessTransferRisk({
                from: params.source.toString(),
                to: params.destination.toString(),
                amount: params.amount
            });

            if (riskAssessment.risk > 0.7) {
                throw new Error('High-risk transfer blocked by AI');
            }

            const transaction = new solanaWeb3.Transaction().add(
                solanaWeb3.Token.createTransferInstruction(
                    solanaWeb3.TOKEN_PROGRAM_ID,
                    params.source,
                    params.destination,
                    params.owner,
                    [],
                    params.amount
                )
            );

            const signature = await this.sendAndConfirmTransaction(transaction, [this.wallet]);

            // Track transfer for AI learning
            await this.aiManager.trackEvent('token_transfer', {
                signature,
                params,
                risk: riskAssessment.risk
            });

            return signature;

        } catch (error) {
            this.handleError('Transfer failed', error);
            throw error;
        }
    }

    /**
     * Send and confirm transaction with AI monitoring
     */
    async sendAndConfirmTransaction(transaction, signers) {
        try {
            transaction.recentBlockhash = (
                await this.connection.getRecentBlockhash()
            ).blockhash;
            
            transaction.feePayer = this.wallet.publicKey;

            // AI verification of transaction
            await this.aiManager.verifyTransaction(transaction);

            const signature = await this.connection.sendTransaction(
                transaction,
                signers,
                { preflightCommitment: this.config.commitment }
            );

            await this.connection.confirmTransaction(signature);
            return signature;

        } catch (error) {
            this.handleError('Transaction failed', error);
            throw error;
        }
    }

    /**
     * Error handler with AI learning integration
     */
    async handleError(message, error) {
        if (this.config.debug) {
            console.error(message, error);
        }

        // Track error for AI learning
        if (this.aiManager) {
            await this.aiManager.trackError({
                message,
                error: error.message,
                stack: error.stack,
                timestamp: Date.now()
            });
        }

        // Emit error event
        this.emit('error', { message, error });
    }

    /**
     * Event emitter for AI tracking
     */
    emit(eventName, data) {
        const event = new CustomEvent('vortex_spl_token', {
            detail: { type: eventName, data }
        });
        window.dispatchEvent(event);
    }
}

// Export for use in WordPress
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VortexSPLToken;
} 