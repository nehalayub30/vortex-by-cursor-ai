<?php
/**
 * VORTEX DAO Investment Security
 * 
 * Handles secure investment transactions, founder rights protection,
 * and anti-dilution mechanisms.
 *
 * @package VORTEX
 */

class VORTEX_DAO_Investment_Security {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;
    
    /**
     * DAO configuration settings.
     *
     * @var array
     */
    private $config;
    
    /**
     * Constructor.
     */
    private function __construct() {
        $this->config = VORTEX_DAO_Core::get_instance()->get_config();
        $this->init_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @return object Instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Protection mechanisms
        add_filter('vortex_dao_can_transfer_equity', [$this, 'check_equity_transfer_allowed'], 10, 4);
        add_filter('vortex_dao_can_change_governance', [$this, 'check_governance_change_allowed'], 10, 2);
        add_filter('vortex_dao_validate_proposal', [$this, 'validate_governance_proposal'], 10, 2);
        
        // Investor security hooks
        add_action('vortex_dao_before_investor_add', [$this, 'validate_investment_terms'], 10, 2);
        add_action('vortex_dao_after_investor_add', [$this, 'setup_investor_protections'], 10, 2);
        
        // Founder protection hooks
        add_action('vortex_dao_founder_shares_action', [$this, 'validate_founder_share_action'], 10, 3);
    }
    
    /**
     * Validate if an equity token transfer is allowed.
     * 
     * @param bool   $allowed      Whether the transfer is currently allowed.
     * @param string $from_address The sending address.
     * @param string $to_address   The receiving address.
     * @param int    $amount       The token amount.
     * @return bool Whether the transfer should be allowed.
     */
    public function check_equity_transfer_allowed($allowed, $from_address, $to_address, $amount) {
        global $wpdb;
        
        // If it's already not allowed, don't override that decision
        if (!$allowed) {
            return false;
        }
        
        // Check if sender is founder
        $founder_address = $this->config['founder_address'];
        if ($from_address === $founder_address) {
            // Get founder's current balance
            $founder_balance = VORTEX_DAO_Tokens::get_instance()->get_equity_token_balance_by_address($founder_address);
            
            // Calculate minimum balance founder must keep (51%)
            $minimum_control_balance = ($this->config['governance_threshold'] + 1); // 51%+1 token
            
            // If transfer would reduce founder balance below control threshold, block it
            if (($founder_balance - $amount) < $minimum_control_balance) {
                // Log this security event
                $this->log_security_event(
                    'transfer_blocked',
                    sprintf(
                        'Blocked founder equity transfer that would reduce control below 51%%: %s tokens from %s to %s',
                        number_format($amount),
                        $from_address,
                        $to_address
                    )
                );
                return false;
            }
        }
        
        // Check vesting status for all participants
        $user_id = $this->get_user_id_by_wallet($from_address);
        if ($user_id) {
            // Get vesting info
            $vesting_info = $this->get_vesting_info($user_id);
            
            // If tokens are still vesting and trying to transfer more than vested amount
            if ($vesting_info && isset($vesting_info['tokens_vested']) && $amount > $vesting_info['tokens_vested']) {
                // Log this security event
                $this->log_security_event(
                    'vesting_violation',
                    sprintf(
                        'Attempted transfer of %s tokens by user ID %d exceeds vested amount of %s tokens',
                        number_format($amount),
                        $user_id,
                        number_format($vesting_info['tokens_vested'])
                    )
                );
                return false;
            }
        }
        
        // Allow the transfer if no security rules were triggered
        return true;
    }
    
    /**
     * Check if a governance change is allowed.
     *
     * @param bool   $allowed Whether the change is currently allowed.
     * @param string $change  The type of governance change.
     * @return bool Whether the change should be allowed.
     */
    public function check_governance_change_allowed($allowed, $change) {
        // Certain governance changes require founder approval in 'founder' phase
        if ($this->config['governance_phase'] === 'founder') {
            $founder_only_changes = [
                'change_vote_multiplier',
                'change_governance_threshold',
                'change_governance_phase',
                'disable_founder_veto',
                'increase_investor_allocation'
            ];
            
            if (in_array($change, $founder_only_changes)) {
                // Must be performed by founder
                if (!$this->is_founder_action()) {
                    // Log this security event
                    $this->log_security_event(
                        'governance_change_blocked',
                        sprintf('Blocked non-founder attempt to make restricted governance change: %s', $change)
                    );
                    return false;
                }
            }
        }
        
        return $allowed;
    }
    
    /**
     * Validate a governance proposal before it's accepted.
     *
     * @param bool  $valid   Whether the proposal is currently considered valid.
     * @param array $proposal The proposal data.
     * @return bool Whether the proposal should be considered valid.
     */
    public function validate_governance_proposal($valid, $proposal) {
        // If it's already invalid, don't override that decision
        if (!$valid) {
            return false;
        }
        
        // Founder can veto certain types of proposals
        if ($this->config['founder_veto_enabled'] && isset($proposal['veto']) && $proposal['veto']) {
            // Verify the veto comes from founder
            if ($this->is_founder_action()) {
                // Log this security event
                $this->log_security_event(
                    'proposal_vetoed',
                    sprintf('Founder vetoed proposal ID %d: %s', $proposal['id'], $proposal['title'])
                );
                return false;
            }
        }
        
        // Check if proposal affects founder rights and needs special handling
        $sensitive_actions = [
            'change_founder_allocation',
            'change_founder_veto',
            'change_vote_multiplier',
            'change_governance_phase'
        ];
        
        if (isset($proposal['action']) && in_array($proposal['action'], $sensitive_actions)) {
            // These proposals need super-majority (75% of all tokens)
            $required_votes = (10000000 * 0.75); // 75% of 10M tokens
            
            if (!isset($proposal['vote_count']) || $proposal['vote_count'] < $required_votes) {
                // Log this security event
                $this->log_security_event(
                    'proposal_rejected',
                    sprintf(
                        'Rejected proposal ID %d affecting founder rights with insufficient votes: %s < %s required',
                        $proposal['id'],
                        isset($proposal['vote_count']) ? number_format($proposal['vote_count']) : 0,
                        number_format($required_votes)
                    )
                );
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate investment terms before adding an investor.
     *
     * @param array $investor_data Investor data.
     * @param array $terms Investment terms.
     * @return bool Whether the investment terms are valid.
     */
    public function validate_investment_terms($investor_data, $terms) {
        // Check if investment amount meets minimum
        if ($terms['amount'] < $this->config['min_investment']) {
            $this->log_security_event(
                'investment_rejected',
                sprintf(
                    'Rejected investment of $%s from %s - below minimum of $%s',
                    number_format($terms['amount']),
                    $investor_data['wallet_address'],
                    number_format($this->config['min_investment'])
                )
            );
            return false;
        }
        
        // Check if investment would exceed the round cap
        $current_investment = $this->get_total_investment();
        if (($current_investment + $terms['amount']) > $this->config['investment_round_cap']) {
            $this->log_security_event(
                'investment_rejected',
                sprintf(
                    'Rejected investment of $%s from %s - would exceed round cap of $%s',
                    number_format($terms['amount']),
                    $investor_data['wallet_address'],
                    number_format($this->config['investment_round_cap'])
                )
            );
            return false;
        }
        
        // Check if token allocation would violate founder control
        $token_amount = $terms['amount'] / $this->config['token_price'];
        $equity_tokens = VORTEX_DAO_Tokens::get_instance();
        $founder_balance = $equity_tokens->get_equity_token_balance_by_address($this->config['founder_address']);
        $total_supply = 10000000; // Total supply of 10M tokens
        
        // Ensure founder maintains control (founder balance > governance threshold)
        if ($founder_balance <= $this->config['governance_threshold']) {
            $this->log_security_event(
                'investment_rejected',
                sprintf(
                    'Rejected investment from %s - would reduce founder control below governance threshold',
                    $investor_data['wallet_address']
                )
            );
            return false;
        }
        
        // Investment terms are valid
        return true;
    }
    
    /**
     * Setup investor protections after adding an investor.
     *
     * @param int   $investor_id   Investor ID.
     * @param array $investor_data Investor data.
     */
    public function setup_investor_protections($investor_id, $investor_data) {
        global $wpdb;
        
        // Setup pro-rata rights if enabled
        if ($this->config['investor_pro_rata_rights']) {
            $wpdb->update(
                $wpdb->prefix . 'vortex_dao_investors',
                ['pro_rata_rights' => 1],
                ['id' => $investor_id],
                ['%d'],
                ['%d']
            );
        }
        
        // Setup anti-dilution protection if enabled
        if ($this->config['anti_dilution_protection']) {
            $wpdb->update(
                $wpdb->prefix . 'vortex_dao_investors',
                ['anti_dilution_protection' => 1],
                ['id' => $investor_id],
                ['%d'],
                ['%d']
            );
        }
        
        // Add liquidation preference
        $wpdb->update(
            $wpdb->prefix . 'vortex_dao_investors',
            ['liquidation_preference' => $this->config['liquidation_preference']],
            ['id' => $investor_id],
            ['%f'],
            ['%d']
        );
        
        // Log security event
        $this->log_security_event(
            'investor_protections_added',
            sprintf(
                'Added investor protections for %s (ID: %d): pro-rata rights: %s, anti-dilution: %s, liquidation preference: %sx',
                $investor_data['wallet_address'],
                $investor_id,
                $this->config['investor_pro_rata_rights'] ? 'Yes' : 'No',
                $this->config['anti_dilution_protection'] ? 'Yes' : 'No',
                $this->config['liquidation_preference']
            )
        );
    }
    
    /**
     * Validate founder share actions.
     *
     * @param string $action Action type.
     * @param int    $amount Amount of shares affected.
     * @param array  $data   Additional action data.
     * @return bool Whether the action is allowed.
     */
    public function validate_founder_share_action($action, $amount, $data) {
        // Get current founder balance
        $equity_tokens = VORTEX_DAO_Tokens::get_instance();
        $founder_balance = $equity_tokens->get_equity_token_balance_by_address($this->config['founder_address']);
        
        // Actions that would reduce founder control need special validation
        if ($action === 'transfer' || $action === 'burn') {
            // Calculate minimum balance founder must keep (51%)
            $minimum_control_balance = ($this->config['governance_threshold'] + 1); // 51%+1 token
            
            // If action would reduce founder balance below control threshold, block it
            if (($founder_balance - $amount) < $minimum_control_balance) {
                // Log this security event
                $this->log_security_event(
                    'founder_action_blocked',
                    sprintf(
                        'Blocked founder share %s of %s tokens that would reduce control below governance threshold',
                        $action,
                        number_format($amount)
                    )
                );
                return false;
            }
        }
        
        // Check if founder is still in vesting period
        $vesting_info = $this->get_vesting_info_by_address($this->config['founder_address']);
        
        if ($vesting_info && isset($vesting_info['tokens_vested'])) {
            // If trying to affect more tokens than vested
            if ($amount > $vesting_info['tokens_vested']) {
                // Log this security event
                $this->log_security_event(
                    'founder_vesting_violation',
                    sprintf(
                        'Blocked founder share %s of %s tokens that exceeds vested amount of %s tokens',
                        $action,
                        number_format($amount),
                        number_format($vesting_info['tokens_vested'])
                    )
                );
                return false;
            }
        }
        
        // Action is allowed
        return true;
    }
    
    /**
     * Log a security event.
     *
     * @param string $event_type Event type.
     * @param string $message    Event message.
     * @param array  $data       Optional additional data.
     */
    private function log_security_event($event_type, $message, $data = []) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_security_logs',
            [
                'event_type' => $event_type,
                'message' => $message,
                'data' => !empty($data) ? json_encode($data) : null,
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
    }
    
    /**
     * Get vesting information for a user.
     *
     * @param int $user_id User ID.
     * @return array|null Vesting information.
     */
    private function get_vesting_info($user_id) {
        global $wpdb;
        
        // Check if user is an investor
        $investor = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_investors WHERE user_id = %d",
                $user_id
            )
        );
        
        if ($investor) {
            $now = new DateTime(current_time('mysql'));
            $vesting_end = new DateTime($investor->vesting_end_date);
            $investment_date = new DateTime($investor->investment_date);
            
            // Calculate days remaining
            $days_remaining = 0;
            if ($vesting_end > $now) {
                $interval = $now->diff($vesting_end);
                $days_remaining = $interval->days;
            }
            
            // Calculate total vesting period in days
            $total_vesting_days = $investment_date->diff($vesting_end)->days;
            
            // Calculate days vested
            $days_vested = $total_vesting_days - $days_remaining;
            
            // Calculate percentage vested
            $percentage_vested = 0;
            if ($total_vesting_days > 0) {
                $percentage_vested = min(100, ($days_vested / $total_vesting_days) * 100);
            }
            
            // Calculate tokens vested and locked
            $tokens_vested = ($investor->token_amount * $percentage_vested) / 100;
            $tokens_locked = $investor->token_amount - $tokens_vested;
            
            return [
                'vesting_end' => $vesting_end,
                'days_remaining' => $days_remaining,
                'percentage_vested' => $percentage_vested,
                'tokens_vested' => $tokens_vested,
                'tokens_locked' => $tokens_locked,
            ];
        }
        
        // Check if user is the founder
        $user_email = get_userdata($user_id)->user_email;
        if ($user_email === get_option('admin_email')) {
            // Calculate founder vesting
            $founder_allocation = $this->config['founder_allocation'];
            $vesting_months = $this->config['founder_vesting_months'];
            $cliff_months = $this->config['founder_cliff_months'];
            
            // Calculate vesting based on project start date
            $start_date = new DateTime(get_option('vortex_dao_start_date', date('Y-m-d H:i:s', strtotime('-1 month'))));
            $now = new DateTime(current_time('mysql'));
            $diff_months = $this->get_months_between($start_date, $now);
            
            // Before cliff, nothing is vested
            if ($diff_months < $cliff_months) {
                return [
                    'vesting_end' => (clone $start_date)->add(new DateInterval("P{$vesting_months}M")),
                    'days_remaining' => ($vesting_months * 30) - ($diff_months * 30),
                    'percentage_vested' => 0,
                    'tokens_vested' => 0,
                    'tokens_locked' => $founder_allocation,
                ];
            }
            
            // Calculate percentage vested (linear vesting after cliff)
            $percentage_vested = min(100, ($diff_months / $vesting_months) * 100);
            $tokens_vested = ($founder_allocation * $percentage_vested) / 100;
            $tokens_locked = $founder_allocation - $tokens_vested;
            
            return [
                'vesting_end' => (clone $start_date)->add(new DateInterval("P{$vesting_months}M")),
                'days_remaining' => max(0, ($vesting_months * 30) - ($diff_months * 30)),
                'percentage_vested' => $percentage_vested,
                'tokens_vested' => $tokens_vested,
                'tokens_locked' => $tokens_locked,
            ];
        }
        
        return null;
    }
    
    /**
     * Get vesting information by wallet address.
     *
     * @param string $wallet_address Wallet address.
     * @return array|null Vesting information.
     */
    private function get_vesting_info_by_address($wallet_address) {
        $user_id = $this->get_user_id_by_wallet($wallet_address);
        
        if ($user_id) {
            return $this->get_vesting_info($user_id);
        }
        
        return null;
    }
    
    /**
     * Get user ID by wallet address.
     *
     * @param string $wallet_address Wallet address.
     * @return int|null User ID.
     */
    private function get_user_id_by_wallet($wallet_address) {
        global $wpdb;
        
        // Try exact match first
        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'vortex_wallet_address' AND meta_value = %s",
                $wallet_address
            )
        );
        
        if ($user_id) {
            return intval($user_id);
        }
        
        // Try finding in investors table
        $investor = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}vortex_dao_investors WHERE wallet_address = %s",
                $wallet_address
            )
        );
        
        if ($investor) {
            return intval($investor->user_id);
        }
        
        return null;
    }
    
    /**
     * Get total investment amount.
     *
     * @return float Total investment amount.
     */
    private function get_total_investment() {
        global $wpdb;
        
        $total = $wpdb->get_var(
            "SELECT SUM(investment_amount) FROM {$wpdb->prefix}vortex_dao_investors"
        );
        
        return $total ? floatval($total) : 0;
    }
    
    /**
     * Check if current action is performed by the founder.
     *
     * @return bool Whether the action is performed by the founder.
     */
    private function is_founder_action() {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        // Get user wallet address
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        // Compare with founder address
        return $wallet_address === $this->config['founder_address'];
    }
    
    /**
     * Get client IP address.
     *
     * @return string Client IP address.
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    /**
     * Get the number of months between two dates.
     *
     * @param DateTime $start_date Start date.
     * @param DateTime $end_date   End date.
     * @return float Number of months.
     */
    private function get_months_between($start_date, $end_date) {
        $diff = $start_date->diff($end_date);
        return $diff->y * 12 + $diff->m + $diff->d / 30;
    }
}

// Initialize the class
VORTEX_DAO_Investment_Security::get_instance(); 