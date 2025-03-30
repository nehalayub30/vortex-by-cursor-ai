<?php
/**
 * Database handler for scheduled events.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Vortex_Scheduler_DB
 *
 * Handles all database operations for scheduled events.
 */
class Vortex_Scheduler_DB {
    /**
     * The single instance of the class.
     *
     * @var Vortex_Scheduler_DB
     */
    protected static $instance = null;

    /**
     * The table name.
     *
     * @var string
     */
    protected $table_name;

    /**
     * Main Vortex_Scheduler_DB Instance.
     *
     * Ensures only one instance of Vortex_Scheduler_DB exists in memory at any one time.
     *
     * @return Vortex_Scheduler_DB - Main instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    protected function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_scheduled_events';
        $this->create_table();
    }

    /**
     * Create the scheduled events table.
     */
    protected function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_type varchar(20) NOT NULL,
            item_id bigint(20) NOT NULL,
            scheduled_time datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY item_id (item_id),
            KEY scheduled_time (scheduled_time),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert a new scheduled event.
     *
     * @param array $data Event data.
     * @return int|false The ID of the inserted event, or false on failure.
     */
    public function insert_event($data) {
        global $wpdb;

        $defaults = array(
            'user_id' => 0,
            'event_type' => '',
            'item_id' => 0,
            'scheduled_time' => '',
            'status' => 'pending'
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['user_id']) || empty($data['event_type']) || empty($data['item_id']) || empty($data['scheduled_time'])) {
            return false;
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $data['user_id'],
                'event_type' => $data['event_type'],
                'item_id' => $data['item_id'],
                'scheduled_time' => $data['scheduled_time'],
                'status' => $data['status']
            ),
            array('%d', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update a scheduled event.
     *
     * @param int $event_id The event ID.
     * @param array $data Event data to update.
     * @return bool Whether the update was successful.
     */
    public function update_event($event_id, $data) {
        global $wpdb;

        if (empty($event_id) || empty($data)) {
            return false;
        }

        $allowed_fields = array(
            'scheduled_time' => '%s',
            'status' => '%s'
        );

        $update_data = array_intersect_key($data, $allowed_fields);
        $update_format = array_intersect_key($allowed_fields, $data);

        if (empty($update_data)) {
            return false;
        }

        return $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $event_id),
            $update_format,
            array('%d')
        ) !== false;
    }

    /**
     * Delete a scheduled event.
     *
     * @param int $event_id The event ID.
     * @return bool Whether the deletion was successful.
     */
    public function delete_event($event_id) {
        global $wpdb;

        if (empty($event_id)) {
            return false;
        }

        return $wpdb->delete(
            $this->table_name,
            array('id' => $event_id),
            array('%d')
        ) !== false;
    }

    /**
     * Get a scheduled event by ID.
     *
     * @param int $event_id The event ID.
     * @return object|null The event object, or null if not found.
     */
    public function get_event($event_id) {
        global $wpdb;

        if (empty($event_id)) {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $event_id
            )
        );
    }

    /**
     * Get all scheduled events for a user.
     *
     * @param int $user_id The user ID.
     * @return array Array of event objects.
     */
    public function get_user_events($user_id) {
        global $wpdb;

        if (empty($user_id)) {
            return array();
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY scheduled_time ASC",
                $user_id
            )
        );
    }

    /**
     * Get all pending events that are due to be processed.
     *
     * @return array Array of event objects.
     */
    public function get_due_events() {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE status = %s AND scheduled_time <= %s ORDER BY scheduled_time ASC",
                'pending',
                current_time('mysql')
            )
        );
    }

    /**
     * Get upcoming events for a user.
     *
     * @param int $user_id The user ID.
     * @param int $limit The maximum number of events to return.
     * @return array Array of event objects.
     */
    public function get_upcoming_events($user_id, $limit = 10) {
        global $wpdb;

        if (empty($user_id)) {
            return array();
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE user_id = %d AND status = %s AND scheduled_time > %s ORDER BY scheduled_time ASC LIMIT %d",
                $user_id,
                'pending',
                current_time('mysql'),
                $limit
            )
        );
    }

    /**
     * Get events by type and status.
     *
     * @param string $event_type The event type.
     * @param string $status The event status.
     * @return array Array of event objects.
     */
    public function get_events_by_type_and_status($event_type, $status) {
        global $wpdb;

        if (empty($event_type) || empty($status)) {
            return array();
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE event_type = %s AND status = %s ORDER BY scheduled_time ASC",
                $event_type,
                $status
            )
        );
    }
}

// Initialize the database handler
Vortex_Scheduler_DB::instance(); 