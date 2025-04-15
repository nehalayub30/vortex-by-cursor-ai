<?php
/**
 * VORTEX DAO Investor Agreement Template
 *
 * @package VORTEX
 */

/**
 * Generate an investor agreement.
 *
 * @param array $investor_data Investor data.
 * @param array $config DAO configuration.
 * @return string HTML content of the agreement.
 */
function vortex_generate_investor_agreement($investor_data, $config) {
    ob_start();
    ?>
    <div class="vortex-investor-agreement">
        <div class="agreement-header">
            <h1>VORTEX AI Marketplace Investment Agreement</h1>
            <p class="agreement-date">Date: <?php echo date_i18n(get_option('date_format')); ?></p>
            <p class="agreement-ref">Reference ID: <?php echo esc_html($investor_data['reference_id']); ?></p>
        </div>
        
        <div class="agreement-parties">
            <p><strong>Between:</strong></p>
            <p><strong>VORTEX AI Marketplace</strong> ("Company")</p>
            <p><strong>AND</strong></p>
            <p><strong><?php echo esc_html($investor_data['name']); ?></strong> ("Investor")</p>
            <p>Wallet Address: <?php echo esc_html($investor_data['wallet_address']); ?></p>
        </div>
        
        <div class="agreement-terms">
            <h2>1. Investment Terms</h2>
            <p>1.1 The Investor agrees to invest the sum of $<?php echo number_format($investor_data['investment_amount'], 2); ?> USD in the Company.</p>
            <p>1.2 In exchange, the Investor will receive <?php echo number_format($investor_data['token_amount']); ?> TOLA-Equity tokens at a price of $<?php echo number_format($config['token_price'], 2); ?> per token.</p>
            <p>1.3 This investment represents approximately <?php echo number_format(($investor_data['token_amount'] / 10000000) * 100, 2); ?>% of the total supply of TOLA-Equity tokens.</p>
            <p>1.4 The investment will be allocated according to the following tranche schedule:</p>
            <ul>
                <?php foreach ($config['investment_tranches'] as $tranche): ?>
                <li>$<?php echo number_format($tranche['amount'], 2); ?> upon <?php echo esc_html($tranche['milestone']); ?>, resulting in the issuance of <?php echo number_format($tranche['equity']); ?> TOLA-Equity tokens</li>
                <?php endforeach; ?>
            </ul>
            
            <h2>2. Vesting Schedule</h2>
            <p>2.1 The TOLA-Equity tokens allocated to the Investor will be subject to a vesting period of <?php echo $config['investor_vesting_months']; ?> months, with a cliff period of <?php echo $config['investor_cliff_months']; ?> months.</p>
            <p>2.2 After the cliff period, tokens will vest linearly on a monthly basis until fully vested at the end of the vesting period.</p>
            <p>2.3 Unvested tokens cannot be transferred or sold.</p>
            
            <h2>3. Investor Rights and Protections</h2>
            <?php if ($config['investor_pro_rata_rights']): ?>
            <p>3.1 <strong>Pro-Rata Rights:</strong> The Investor will have the right to participate in future funding rounds to maintain their percentage ownership in the Company.</p>
            <?php endif; ?>
            
            <?php if ($config['anti_dilution_protection']): ?>
            <p>3.2 <strong>Anti-Dilution Protection:</strong> The Investor will receive weighted-average anti-dilution protection in the event of a down round.</p>
            <?php endif; ?>
            
            <p>3.3 <strong>Liquidation Preference:</strong> In the event of a liquidation, acquisition, or sale of the Company, the Investor will receive a <?php echo $config['liquidation_preference']; ?>x liquidation preference on their investment amount before any proceeds are distributed to common token holders.</p>
            
            <p>3.4 <strong>Information Rights:</strong> The Investor will receive quarterly financial reports and updates on the Company's operations.</p>
            
            <h2>4. Governance Rights</h2>
            <p>4.1 Each TOLA-Equity token represents one unit of governance power multiplied by the applicable vote multiplier:</p>
            <ul>
                <li>Founder tokens: <?php echo $config['founder_vote_multiplier']; ?>x voting power</li>
                <li>Investor tokens: <?php echo $config['investor_vote_multiplier']; ?>x voting power</li>
                <li>Team tokens: <?php echo $config['team_vote_multiplier']; ?>x voting power</li>
            </ul>
            
            <p>4.2 Governance proposals require at least <?php echo number_format(($config['governance_threshold'] / 10000000) * 100, 0); ?>% of the total token supply voting in favor to pass.</p>
            
            <p>4.3 During the '<?php echo $config['governance_phase']; ?>' governance phase, certain decisions may be subject to founder approval or veto.</p>
            
            <h2>5. Revenue Sharing</h2>
            <p>5.1 <?php echo $config['revenue_investor_allocation']; ?>% of marketplace revenue will be allocated to investors on a quarterly basis, distributed proportionally based on TOLA-Equity token holdings.</p>
            
            <h2>6. Transfer Restrictions</h2>
            <p>6.1 TOLA-Equity tokens may not be transferred to any person or entity that has not completed KYC/AML verification.</p>
            
            <p>6.2 Any transfer of TOLA-Equity tokens must comply with all applicable securities laws and regulations.</p>
            
            <h2>7. Representations and Warranties</h2>
            <p>7.1 The Investor represents that they are an accredited investor as defined by applicable securities laws.</p>
            
            <p>7.2 The Investor acknowledges that they have received all information necessary to make an informed investment decision.</p>
            
            <p>7.3 The Investor understands the risks associated with this investment, including the potential loss of the entire investment amount.</p>
            
            <h2>8. Confidentiality</h2>
            <p>8.1 The Investor agrees to maintain the confidentiality of all non-public information regarding the Company and its operations.</p>
            
            <h2>9. Term and Termination</h2>
            <p>9.1 This Agreement shall remain in effect until terminated by mutual agreement or as otherwise provided by law.</p>
            
            <h2>10. Governing Law</h2>
            <p>10.1 This Agreement shall be governed by and construed in accordance with the laws of [Jurisdiction], without regard to its conflict of law principles.</p>
        </div>
        
        <div class="agreement-signatures">
            <div class="signature-block">
                <p>VORTEX AI Marketplace</p>
                <div class="signature-line">____________________________</div>
                <p>Name: [Founder Name]<br>
                Title: Founder & CEO<br>
                Date: <?php echo date_i18n(get_option('date_format')); ?></p>
            </div>
            
            <div class="signature-block">
                <p><?php echo esc_html($investor_data['name']); ?></p>
                <div class="signature-line">____________________________</div>
                <p>Date: ___________________</p>
            </div>
        </div>
        
        <div class="agreement-footer">
            <p>This agreement was generated by the VORTEX DAO system and is digitally signed and verified on the blockchain.</p>
            <p>Transaction hash: <?php echo isset($investor_data['blockchain_tx']) ? esc_html($investor_data['blockchain_tx']) : '[To be added upon execution]'; ?></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
} 