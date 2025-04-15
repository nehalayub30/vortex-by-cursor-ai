<?php
/**
 * VORTEX DAO Admin Settings
 *
 * @package VORTEX
 */

class VORTEX_DAO_Admin {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-dashboard', 
            'DAO Integration', 
            'DAO Integration',
            'manage_options',
            'vortex-dao-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        // General settings
        register_setting('vortex_dao_settings', 'vortex_web3_provider');
        
        // Contract addresses
        register_setting('vortex_dao_settings', 'vortex_token_address');
        register_setting('vortex_dao_settings', 'vortex_achievement_address');
        register_setting('vortex_dao_settings', 'vortex_reputation_address');
        register_setting('vortex_dao_settings', 'vortex_governance_address');
        register_setting('vortex_dao_settings', 'vortex_treasury_address');
        register_setting('vortex_dao_settings', 'vortex_rewards_address');
        
        // Feature toggles
        register_setting('vortex_dao_settings', 'vortex_enable_achievements');
        register_setting('vortex_dao_settings', 'vortex_enable_reputation');
        register_setting('vortex_dao_settings', 'vortex_enable_governance');
        register_setting('vortex_dao_settings', 'vortex_enable_rewards');
        
        // Reward settings
        register_setting('vortex_dao_settings', 'vortex_artwork_creation_points');
        register_setting('vortex_dao_settings', 'vortex_artwork_purchase_points');
        register_setting('vortex_dao_settings', 'vortex_artwork_curation_points');
    }

    /**
     * Enqueue admin scripts.
     */
    public function enqueue_admin_scripts($hook) {
        if ('vortex-dashboard_page_vortex-dao-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_style('vortex-dao-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/css/vortex-dao-admin.css', [], VORTEX_VERSION);
        wp_enqueue_script('vortex-dao-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/js/vortex-dao-admin.js', ['jquery'], VORTEX_VERSION, true);
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap vortex-dao-settings">
            <h1><?php echo esc_html__('DAO Integration Settings', 'vortex'); ?></h1>
            
            <div class="vortex-admin-tabs">
                <div class="nav-tab-wrapper">
                    <a href="#general-settings" class="nav-tab nav-tab-active">General Settings</a>
                    <a href="#contract-addresses" class="nav-tab">Contract Addresses</a>
                    <a href="#reward-settings" class="nav-tab">Reward Settings</a>
                    <a href="#shortcodes" class="nav-tab">Shortcodes</a>
                </div>
                
                <div class="vortex-admin-tab-content">
                    <form method="post" action="options.php">
                        <?php settings_fields('vortex_dao_settings'); ?>
                        
                        <div id="general-settings" class="tab-pane active">
                            <h2><?php echo esc_html__('General Settings', 'vortex'); ?></h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_web3_provider"><?php echo esc_html__('Web3 Provider URL', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="vortex_web3_provider" name="vortex_web3_provider" class="regular-text" value="<?php echo esc_attr(get_option('vortex_web3_provider', 'https://rpc.tola-testnet.io')); ?>" />
                                        <p class="description"><?php echo esc_html__('Enter the URL for your Web3 provider (e.g., Infura, TOLA RPC endpoint, etc.)', 'vortex'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php echo esc_html__('Enable Features', 'vortex'); ?></th>
                                    <td>
                                        <fieldset>
                                            <label for="vortex_enable_achievements">
                                                <input type="checkbox" id="vortex_enable_achievements" name="vortex_enable_achievements" value="1" <?php checked(get_option('vortex_enable_achievements', '1'), '1'); ?> />
                                                <?php echo esc_html__('Enable Achievements', 'vortex'); ?>
                                            </label>
                                            <br>
                                            
                                            <label for="vortex_enable_reputation">
                                                <input type="checkbox" id="vortex_enable_reputation" name="vortex_enable_reputation" value="1" <?php checked(get_option('vortex_enable_reputation', '1'), '1'); ?> />
                                                <?php echo esc_html__('Enable Reputation System', 'vortex'); ?>
                                            </label>
                                            <br>
                                            
                                            <label for="vortex_enable_governance">
                                                <input type="checkbox" id="vortex_enable_governance" name="vortex_enable_governance" value="1" <?php checked(get_option('vortex_enable_governance', '1'), '1'); ?> />
                                                <?php echo esc_html__('Enable Governance', 'vortex'); ?>
                                            </label>
                                            <br>
                                            
                                            <label for="vortex_enable_rewards">
                                                <input type="checkbox" id="vortex_enable_rewards" name="vortex_enable_rewards" value="1" <?php checked(get_option('vortex_enable_rewards', '1'), '1'); ?> />
                                                <?php echo esc_html__('Enable Rewards', 'vortex'); ?>
                                            </label>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div id="contract-addresses" class="tab-pane">
                            <h2><?php echo esc_html__('Contract Addresses', 'vortex'); ?></h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_token_address"><?php echo esc_html__('TOLA Token Contract', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="vortex_token_address" name="vortex_token_address" class="regular-text" value="<?php echo esc_attr(get_option('vortex_token_address', '')); ?>" />
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_achievement_address"><?php echo esc_html__('Achievement Contract', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="vortex_achievement_address" name="vortex_achievement_address" class="regular-text" value="<?php echo esc_attr(get_option('vortex_achievement_address', '')); ?>" />
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_reputation_address"><?php echo esc_html__('Reputation Contract', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="vortex_reputation_address" name="vortex_reputation_address" class="regular-text" value="<?php echo esc_attr(get_option('vortex_reputation_address', '')); ?>" />
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_governance_address"><?php echo esc_html__('Governance Contract', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="vortex_governance_address" name="vortex_governance_address" class="regular-text" value="<?php echo esc_attr(get_option('vortex_governance_address', '')); ?>" />
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_treasury_address"><?php echo esc_html__('Treasury Contract', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="vortex_treasury_address" name="vortex_treasury_address" class="regular-text" value="<?php echo esc_attr(get_option('vortex_treasury_address', '')); ?>" />
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_rewards_address"><?php echo esc_html__('Rewards Contract', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="vortex_rewards_address" name="vortex_rewards_address" class="regular-text" value="<?php echo esc_attr(get_option('vortex_rewards_address', '')); ?>" />
                                    </td>
                                </tr>
                            </table>
                            
                            <div class="contract-deployment-section">
                                <h3><?php echo esc_html__('Deploy Contracts', 'vortex'); ?></h3>
                                <p><?php echo esc_html__('If you have not yet deployed the DAO contracts, you can use the deployment wizard to deploy them to the TOLA blockchain.', 'vortex'); ?></p>
                                <button type="button" class="button button-primary" id="vortex-deploy-contracts"><?php echo esc_html__('Launch Deployment Wizard', 'vortex'); ?></button>
                                <div id="deployment-status"></div>
                            </div>
                        </div>
                        
                        <div id="reward-settings" class="tab-pane">
                            <h2><?php echo esc_html__('Reward Settings', 'vortex'); ?></h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_artwork_creation_points"><?php echo esc_html__('Artwork Creation Points', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="vortex_artwork_creation_points" name="vortex_artwork_creation_points" class="small-text" value="<?php echo esc_attr(get_option('vortex_artwork_creation_points', '50')); ?>" min="0" />
                                        <p class="description"><?php echo esc_html__('Points awarded for creating new artwork', 'vortex'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_artwork_purchase_points"><?php echo esc_html__('Artwork Purchase Points', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="vortex_artwork_purchase_points" name="vortex_artwork_purchase_points" class="small-text" value="<?php echo esc_attr(get_option('vortex_artwork_purchase_points', '25')); ?>" min="0" />
                                        <p class="description"><?php echo esc_html__('Points awarded for purchasing artwork', 'vortex'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="vortex_artwork_curation_points"><?php echo esc_html__('Artwork Curation Points', 'vortex'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="vortex_artwork_curation_points" name="vortex_artwork_curation_points" class="small-text" value="<?php echo esc_attr(get_option('vortex_artwork_curation_points', '10')); ?>" min="0" />
                                        <p class="description"><?php echo esc_html__('Points awarded for curating artwork', 'vortex'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div id="shortcodes" class="tab-pane">
                            <h2><?php echo esc_html__('DAO Integration Shortcodes', 'vortex'); ?></h2>
                            
                            <div class="shortcode-list">
                                <div class="shortcode-item">
                                    <h3><?php echo esc_html__('Connect Wallet', 'vortex'); ?></h3>
                                    <code>[vortex_connect_wallet]</code>
                                    <p><?php echo esc_html__('Displays a button for users to connect their Web3 wallet.', 'vortex'); ?></p>
                                    <p><strong><?php echo esc_html__('Parameters:', 'vortex'); ?></strong> title, description, class</p>
                                </div>
                                
                                <div class="shortcode-item">
                                    <h3><?php echo esc_html__('Achievements Gallery', 'vortex'); ?></h3>
                                    <code>[vortex_achievements]</code>
                                    <p><?php echo esc_html__('Displays the user\'s earned achievements.', 'vortex'); ?></p>
                                    <p><strong><?php echo esc_html__('Parameters:', 'vortex'); ?></strong> title, description, class</p>
                                </div>
                                
                                <div class="shortcode-item">
                                    <h3><?php echo esc_html__('Reputation Dashboard', 'vortex'); ?></h3>
                                    <code>[vortex_reputation]</code>
                                    <p><?php echo esc_html__('Shows the user\'s reputation stats and level.', 'vortex'); ?></p>
                                    <p><strong><?php echo esc_html__('Parameters:', 'vortex'); ?></strong> title, description, class</p>
                                </div>
                                
                                <div class="shortcode-item">
                                    <h3><?php echo esc_html__('Governance Interface', 'vortex'); ?></h3>
                                    <code>[vortex_governance]</code>
                                    <p><?php echo esc_html__('Provides access to DAO governance features.', 'vortex'); ?></p>
                                    <p><strong><?php echo esc_html__('Parameters:', 'vortex'); ?></strong> title, description, class</p>
                                </div>
                                
                                <div class="shortcode-item">
                                    <h3><?php echo esc_html__('Rewards Section', 'vortex'); ?></h3>
                                    <code>[vortex_rewards]</code>
                                    <p><?php echo esc_html__('Shows the user\'s earned rewards and actions.', 'vortex'); ?></p>
                                    <p><strong><?php echo esc_html__('Parameters:', 'vortex'); ?></strong> title, description, class</p>
                                </div>
                                
                                <div class="shortcode-item">
                                    <h3><?php echo esc_html__('Complete Dashboard', 'vortex'); ?></h3>
                                    <code>[vortex_dashboard]</code>
                                    <p><?php echo esc_html__('Displays a tabbed interface with all DAO features.', 'vortex'); ?></p>
                                    <p><strong><?php echo esc_html__('Parameters:', 'vortex'); ?></strong> title, description, class</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get an instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Initialize admin
function vortex_dao_admin() {
    return VORTEX_DAO_Admin::get_instance();
}
vortex_dao_admin(); 