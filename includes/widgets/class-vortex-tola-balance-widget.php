<?php
/**
 * The TOLA Balance Widget functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 */

/**
 * The TOLA Balance Widget functionality.
 *
 * Displays TOLA balance for users and provides functionality to buy more tokens.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_TOLA_Balance_Widget extends WP_Widget {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct(
            'vortex_tola_balance_widget', // Base ID
            __( 'VORTEX TOLA Balance', 'vortex-ai-marketplace' ), // Name
            array(
                'description' => __( 'Display TOLA balance and purchase options.', 'vortex-ai-marketplace' ),
                'classname'   => 'vortex-tola-balance-widget',
            )
        );

        // Register widget
        add_action( 'widgets_init', array( $this, 'register_widget' ) );
        
        // Load widget specific scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Register the widget with WordPress.
     *
     * @since    1.0.0
     */
    public function register_widget() {
        register_widget( 'Vortex_TOLA_Balance_Widget' );
    }

    /**
     * Enqueue widget specific scripts and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only load if widget is active
        if ( is_active_widget( false, false, $this->id_base, true ) ) {
            wp_enqueue_style(
                'vortex-tola-balance-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/css/vortex-tola-balance.css',
                array(),
                VORTEX_VERSION,
                'all'
            );
            
            wp_enqueue_script(
                'vortex-tola-balance-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/js/vortex-tola-balance.js',
                array( 'jquery' ),
                VORTEX_VERSION,
                true
            );
            
            wp_localize_script(
                'vortex-tola-balance-widget',
                'vortexTOLA',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'vortex_tola_nonce' ),
                )
            );
        }
    }

    /**
     * Front-end display of widget.
     *
     * @since    1.0.0
     * @param    array    $args        Widget arguments.
     * @param    array    $instance    Saved values from database.
     */
    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        // Get widget settings
        $show_guest_message = ! empty( $instance['show_guest_message'] ) ? (bool) $instance['show_guest_message'] : true;
        $show_purchase_button = ! empty( $instance['show_purchase_button'] ) ? (bool) $instance['show_purchase_button'] : true;
        $show_balance_history = ! empty( $instance['show_balance_history'] ) ? (bool) $instance['show_balance_history'] : false;
        $guest_message = ! empty( $instance['guest_message'] ) ? $instance['guest_message'] : __( 'Login to view your TOLA balance', 'vortex-ai-marketplace' );
        $purchase_url = ! empty( $instance['purchase_url'] ) ? $instance['purchase_url'] : home_url( '/tola-purchase/' );
        $history_url = ! empty( $instance['history_url'] ) ? $instance['history_url'] : home_url( '/my-account/tola-history/' );
        
        // Container start
        echo '<div class="vortex-tola-balance-container">';
        
        // Check if user is logged in
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $balance = $this->get_user_tola_balance( $user_id );
            
            // Display balance
            echo '<div class="vortex-tola-balance">';
            echo '<div class="vortex-tola-icon"></div>';
            echo '<div class="vortex-tola-amount">' . esc_html( number_format( $balance ) ) . ' ' . esc_html__( 'TOLA', 'vortex-ai-marketplace' ) . '</div>';
            echo '</div>';
            
            // Display recent transactions if enabled
            if ( $show_balance_history ) {
                $transactions = $this->get_recent_transactions( $user_id, 3 );
                
                if ( ! empty( $transactions ) ) {
                    echo '<div class="vortex-tola-transactions">';
                    echo '<h4>' . esc_html__( 'Recent Activity', 'vortex-ai-marketplace' ) . '</h4>';
                    echo '<ul>';
                    
                    foreach ( $transactions as $transaction ) {
                        $amount_class = $transaction['type'] === 'credit' ? 'tola-credit' : 'tola-debit';
                        $amount_prefix = $transaction['type'] === 'credit' ? '+' : '-';
                        
                        echo '<li class="vortex-tola-transaction">';
                        echo '<div class="transaction-description">' . esc_html( $transaction['description'] ) . '</div>';
                        echo '<div class="transaction-amount ' . esc_attr( $amount_class ) . '">' . esc_html( $amount_prefix . number_format( $transaction['amount'] ) ) . '</div>';
                        echo '<div class="transaction-date">' . esc_html( $transaction['date'] ) . '</div>';
                        echo '</li>';
                    }
                    
                    echo '</ul>';
                    echo '<div class="vortex-tola-history-link">';
                    echo '<a href="' . esc_url( $history_url ) . '">' . esc_html__( 'View Full History', 'vortex-ai-marketplace' ) . '</a>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            
            // Purchase button
            if ( $show_purchase_button ) {
                echo '<div class="vortex-tola-purchase">';
                echo '<a href="' . esc_url( $purchase_url ) . '" class="vortex-button vortex-tola-button">';
                echo esc_html__( 'Buy TOLA', 'vortex-ai-marketplace' );
                echo '</a>';
                echo '</div>';
            }
            
        } else {
            // Show message for guests
            if ( $show_guest_message ) {
                echo '<div class="vortex-tola-guest-message">';
                echo esc_html( $guest_message );
                echo '</div>';
                
                // Login/register buttons
                echo '<div class="vortex-tola-auth-buttons">';
                echo '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="vortex-button vortex-login-button">';
                echo esc_html__( 'Login', 'vortex-ai-marketplace' );
                echo '</a>';
                
                if ( get_option( 'users_can_register' ) ) {
                    echo '<a href="' . esc_url( wp_registration_url() ) . '" class="vortex-button vortex-register-button">';
                    echo esc_html__( 'Register', 'vortex-ai-marketplace' );
                    echo '</a>';
                }
                
                echo '</div>';
            }
        }
        
        echo '</div>'; // End container
        
        echo $args['after_widget'];
    }

    /**
     * Get user's TOLA balance.
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @return   float                TOLA balance.
     */
    private function get_user_tola_balance( $user_id ) {
        global $wpdb;
        
        // Check for table existence
        $table_name = $wpdb->prefix . 'vortex_tola';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            // Fallback to user meta if table doesn't exist
            $balance = get_user_meta( $user_id, '_vortex_tola_balance', true );
            return floatval( $balance );
        }
        
        // Get balance from table
        $balance = $wpdb->get_var( $wpdb->prepare(
            "SELECT balance FROM $table_name WHERE user_id = %d",
            $user_id
        ) );
        
        if ( $balance === null ) {
            return 0;
        }
        
        return floatval( $balance );
    }

    /**
     * Get recent TOLA transactions for a user.
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @param    int      $limit      Number of transactions to get.
     * @return   array                Recent transactions.
     */
    private function get_recent_transactions( $user_id, $limit ) {
        global $wpdb;
        
        $transactions = array();
        
        // Check for table existence
        $table_name = $wpdb->prefix . 'vortex_tola_transactions';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            return $transactions;
        }
        
        // Get transactions from table
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT amount, type, description, created_at 
            FROM $table_name 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A );
        
        if ( empty( $results ) ) {
            return $transactions;
        }
        
        foreach ( $results as $result ) {
            $transactions[] = array(
                'amount'      => floatval( $result['amount'] ),
                'type'        => $result['type'],
                'description' => $result['description'],
                'date'        => date_i18n( get_option( 'date_format' ), strtotime( $result['created_at'] ) ),
            );
        }
        
        return $transactions;
    }

    /**
     * Back-end widget form.
     *
     * @since    1.0.0
     * @param    array    $instance    Previously saved values from database.
     * @return   void
     */
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Your TOLA Balance', 'vortex-ai-marketplace' );
        $show_guest_message = isset( $instance['show_guest_message'] ) ? (bool) $instance['show_guest_message'] : true;
        $show_purchase_button = isset( $instance['show_purchase_button'] ) ? (bool) $instance['show_purchase_button'] : true;
        $show_balance_history = isset( $instance['show_balance_history'] ) ? (bool) $instance['show_balance_history'] : false;
        $guest_message = ! empty( $instance['guest_message'] ) ? $instance['guest_message'] : __( 'Login to view your TOLA balance', 'vortex-ai-marketplace' );
        $purchase_url = ! empty( $instance['purchase_url'] ) ? $instance['purchase_url'] : home_url( '/tola-purchase/' );
        $history_url = ! empty( $instance['history_url'] ) ? $instance['history_url'] : home_url( '/my-account/tola-history/' );
        ?>
        
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'vortex-ai-marketplace' ); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
                type="text" 
                value="<?php echo esc_attr( $title ); ?>"
            >
        </p>
        
        <p>
            <input 
                type="checkbox" 
                class="checkbox" 
                id="<?php echo esc_attr( $this->get_field_id( 'show_guest_message' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'show_guest_message' ) ); ?>"
                <?php checked( $show_guest_message ); ?> 
            >
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_guest_message' ) ); ?>">
                <?php esc_html_e( 'Show message for guests', 'vortex-ai-marketplace' ); ?>
            </label>
        </p>
        
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'guest_message' ) ); ?>">
                <?php esc_html_e( 'Guest Message:', 'vortex-ai-marketplace' ); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr( $this->get_field_id( 'guest_message' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'guest_message' ) ); ?>" 
                type="text" 
                value="<?php echo esc_attr( $guest_message ); ?>"
            >
        </p>
        
        <p>
            <input 
                type="checkbox" 
                class="checkbox" 
                id="<?php echo esc_attr( $this->get_field_id( 'show_purchase_button' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'show_purchase_button' ) ); ?>"
                <?php checked( $show_purchase_button ); ?> 
            >
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_purchase_button' ) ); ?>">
                <?php esc_html_e( 'Show purchase button', 'vortex-ai-marketplace' ); ?>
            </label>
        </p>
        
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'purchase_url' ) ); ?>">
                <?php esc_html_e( 'Purchase Page URL:', 'vortex-ai-marketplace' ); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr( $this->get_field_id( 'purchase_url' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'purchase_url' ) ); ?>" 
                type="text" 
                value="<?php echo esc_url( $purchase_url ); ?>"
            >
        </p>
        
        <p>
            <input 
                type="checkbox" 
                class="checkbox" 
                id="<?php echo esc_attr( $this->get_field_id( 'show_balance_history' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'show_balance_history' ) ); ?>"
                <?php checked( $show_balance_history ); ?> 
            >
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_balance_history' ) ); ?>">
                <?php esc_html_e( 'Show balance history', 'vortex-ai-marketplace' ); ?>
            </label>
        </p>
        
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'history_url' ) ); ?>">
                <?php esc_html_e( 'History Page URL:', 'vortex-ai-marketplace' ); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr( $this->get_field_id( 'history_url' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'history_url' ) ); ?>" 
                type="text" 
                value="<?php echo esc_url( $history_url ); ?>"
            >
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @since    1.0.0
     * @param    array    $new_instance    Values just sent to be saved.
     * @param    array    $old_instance    Previously saved values from database.
     * @return   array                     Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        
        $instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['show_guest_message'] = ! empty( $new_instance['show_guest_message'] ) ? 1 : 0;
        $instance['show_purchase_button'] = ! empty( $new_instance['show_purchase_button'] ) ? 1 : 0;
        $instance['show_balance_history'] = ! empty( $new_instance['show_balance_history'] ) ? 1 : 0;
        $instance['guest_message'] = ! empty( $new_instance['guest_message'] ) ? sanitize_text_field( $new_instance['guest_message'] ) : '';
        $instance['purchase_url'] = ! empty( $new_instance['purchase_url'] ) ? esc_url_raw( $new_instance['purchase_url'] ) : '';
        $instance['history_url'] = ! empty( $new_instance['history_url'] ) ? esc_url_raw( $new_instance['history_url'] ) : '';
        
        return $instance;
    }

    /**
     * Get user's TOLA balance (static method for external use).
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @return   float                TOLA balance.
     */
    public static function get_balance( $user_id = null ) {
        if ( $user_id === null && is_user_logged_in() ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return 0;
        }
        
        global $wpdb;
        
        // Check for table existence
        $table_name = $wpdb->prefix . 'vortex_tola';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            // Fallback to user meta if table doesn't exist
            $balance = get_user_meta( $user_id, '_vortex_tola_balance', true );
            return floatval( $balance );
        }
        
        // Get balance from table
        $balance = $wpdb->get_var( $wpdb->prepare(
            "SELECT balance FROM $table_name WHERE user_id = %d",
            $user_id
        ) );
        
        if ( $balance === null ) {
            return 0;
        }
        
        return floatval( $balance );
    }
} 