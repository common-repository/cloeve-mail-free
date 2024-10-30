<?php
/*
Plugin Name: Cloeve Mail Free
description: Capture, build, and manage an email list.
Version: 1.1
Author: Cloeve Tech
Author URI: https://cloeve.com/tech
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

const CLOEVE_MAIL_TABLE = 'cloeve_mail';



class Cloeve_Mail_List extends WP_List_Table {

    /** Class constructor */
    public function __construct() {

        parent::__construct( [
            'singular' => __( 'Email', 'sp' ), //singular name of the listed records
            'plural'   => __( 'Emails', 'sp' ), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ] );

    }


    /**
     * Retrieve data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public function retrieve_data( $per_page = 25, $page_number = 1 ) {

        global $wpdb;
        $table_name = $wpdb->prefix . CLOEVE_MAIL_TABLE;

        $sql = "SELECT * FROM $table_name";

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

        return $wpdb->get_results( $sql, 'ARRAY_A' );
    }


    /**
     * Delete a record.
     *
     * @param int $id ID
     */
    public function delete_record( $id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . CLOEVE_MAIL_TABLE;


        $wpdb->delete(
            $table_name,
            [ 'id' => $id ],
            [ '%d' ]
        );
    }


    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public function record_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . CLOEVE_MAIL_TABLE;

        $sql = "SELECT COUNT(*) FROM $table_name";

        return $wpdb->get_var( $sql );
    }


    /** Text displayed when no data is available */
    public function no_items() {
        _e( 'No data available.', 'sp' );
    }


    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'email':
            case 'source':
                return esc_html($item[ $column_name ]);
            case 'created_at':
                $timezone = get_option('timezone_string');
                if(empty($timezone)){
                    $created_at = (new DateTime( $item[ $column_name ]))->format('F j, Y h:i A');
                }else{
                    $created_at = (new DateTime( $item[ $column_name ]))->setTimezone(new DateTimeZone($timezone))->format('F j, Y h:i A');
                }
                return esc_html($created_at);
            default:
                return '';
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
        );
    }


    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    public function column_name( $item ) {

        $delete_nonce = wp_create_nonce( 'email_delete' );

        $title = '<strong>' . $item['email'] . '</strong>';

        $actions = [
            'delete' => sprintf( '<a href="?page=%s&action=%s&email_id=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
        ];

        return $title . $this->row_actions( $actions );
    }


    /**
     *  Associative array of columns
     *
     * @return array
     */
    public function get_columns() {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'email'    => __( 'Email', 'sp' ),
            'source' => __( 'Source Page', 'sp' ),
            'created_at' => __( 'Date', 'sp' )
        ];

        return $columns;
    }


    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'email' => array( 'email', true ),
            'source' => array( 'source', true ),
            'created_at' => array( 'created_at', true )
        );

        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = [
            'bulk-delete' => 'Delete'
        ];

        return $actions;
    }


    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = 25;
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ] );

        $this->items = $this->retrieve_data( $per_page, $current_page );
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'email_delete' ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                $this->delete_record( absint( $_GET['email_id'] ) );
            }

        }

        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {

            $delete_ids = esc_sql( $_POST['bulk-delete'] );

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                $this->delete_record( $id );

            }
        }
    }

}

class Cloeve_Mail_Plugin {

    // class instance
    static $instance;

    // table object
    public $table_obj;
    public $detail_table_obj;

    // class constructor
    public function __construct() {
        add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
        add_action( 'admin_menu', [ $this, 'plugin_menu' ] );

        // shortcode
        add_shortcode( 'cloeve_mail', [__CLASS__, 'input_shortcode_handler'] );

        // API
        add_action( 'rest_api_init', function () {
            register_rest_route( 'cloeve-tech/cloeve-mail/v1', '/subscribe_email', array(
                'methods' => 'POST',
                'callback' => [__CLASS__, 'subscribe_email'],
            ) );
            register_rest_route( 'cloeve-tech/cloeve-mail/v1', '/export_csv', array(
                'methods' => 'GET',
                'callback' => [__CLASS__, 'export_csv'],
            ) );
        } );

        // scripts
        add_action('wp_enqueue_scripts', [__CLASS__, 'load_scripts']);

        // db tables
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $main_table = $wpdb->prefix . CLOEVE_MAIL_TABLE;

        // main table
        $table_sql = "CREATE TABLE $main_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        source VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY id (id)
	    ) $charset_collate;";
        dbDelta( $table_sql );
    }

    /**
     * scripts
     */
    static function load_scripts() {
        wp_register_style( 'cloeve_tech_email_list', plugins_url( 'cloeve-mail.css', __FILE__ )  );
        wp_enqueue_style( 'cloeve_tech_email_list' );
        wp_enqueue_script( 'cloeve_tech_email_list', plugins_url( 'cloeve-mail.js', __FILE__ ), array( 'jquery' ) );
    }

    public static function set_screen( $status, $option, $value ) {
        return $value;
    }

    public function plugin_menu() {

        $icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg id="Layer_2" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 13.17"><defs><style>.cls-1{fill:#fff;}</style></defs><title>icon_email</title><g id="_7aYNXT.tif" data-name="7aYNXT.tif"><path class="cls-1" d="M0,15.11V4.89A2.23,2.23,0,0,1,2.32,3.41q7.65,0,15.31,0A2.28,2.28,0,0,1,20,4.89V15.11a2.26,2.26,0,0,1-2.37,1.48q-7.65,0-15.31,0A2.22,2.22,0,0,1,0,15.11ZM2.8,4.85a.7.7,0,0,0,.29.4l6.47,6.48c.42.42.46.42.89,0l6.46-6.47c.11-.11.27-.19.28-.4Zm.38,10.27H16.86a.88.88,0,0,0-.09-.16l-3.51-3.52c-.22-.22-.37-.17-.56,0-.48.5-1,1-1.47,1.48a1.64,1.64,0,0,1-2.45,0c-.48-.46-1-.92-1.41-1.41-.27-.29-.45-.29-.72,0-1.08,1.1-2.17,2.18-3.26,3.27C3.3,14.88,3.17,15,3.18,15.12ZM1.43,5.68c-.1.08-.06.19-.06.29v8.29c0,.13-.07.31.09.36s.22-.12.31-.21c1.31-1.31,2.61-2.62,3.93-3.92.25-.25.24-.39,0-.63-1.34-1.32-2.66-2.65-4-4C1.63,5.8,1.56,5.69,1.43,5.68Zm17.14,0-.17.13C17,7.15,15.65,8.54,14.25,9.91c-.24.23-.16.37,0,.57l3.86,3.85c.12.12.22.35.41.27s.07-.29.07-.45c0-2.68,0-5.35,0-8A.85.85,0,0,0,18.57,5.64Z" transform="translate(0 -3.41)"/></g></svg>');

        $hook = add_menu_page(
            'Cloeve Mail',
            'Cloeve Mail',
            'manage_options',
            'cloeve-mail',
            [ $this, 'plugin_settings_page' ],
            $icon
        );

        add_action( "load-$hook", [ $this, 'screen_option' ] );

    }


    /**
     * Plugin settings page
     */
    public function plugin_settings_page() {

         ?>
            <div class="wrap">
                <h2>Cloeve Mail <a href="/wp-json/cloeve-tech/cloeve-mail/v1/export_csv" class="page-title-action">Export List as CSV</a></h2>

                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <div class="meta-box-sortables ui-sortable">
                                <form method="post">
                                    <?php
                                    $this->table_obj->prepare_items();
                                    $this->table_obj->display(); ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <br class="clear">
                </div>
            </div>
        <?php
    }

    /**
     * Screen options
     */
    public function screen_option() {

        $this->table_obj = new Cloeve_Mail_List();
    }

    /**
     * layout
     * @param int $type
     * @param int $height
     * @param string $bg_color
     * @param $action_title
     * @param $action_paragraph
     * @return string
     */
    static function list_input_layout($type, $height, $bg_color, $action_title, $action_paragraph, $placeholder, $btn_label, $btn_color) {

        ob_start();
        if($type == 1){ ?>
            <form class="ctEmailListBuilderForm" onsubmit="subscribeEmail();return false;">
                <div class="cloeve-color-bar-container" style="background-color: <?php echo $bg_color;?>">
                    <div class="cloeve-email-list-input-container">
                        <input type="hidden" id="cloeveMailSource" name="cloeveMailSource" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                        <input type="text" id="cloeveMailEmail" name="cloeveMailEmail" class="cloeve-email-list-input"  style="height: <?php echo $height . 'px';?>; line-height: <?php echo $height . 'px';?>" placeholder="<?php echo $placeholder;?>">
                        <button class="cloeve-btn-subscribe" style="height: <?php echo $height . 'px';?>; line-height: <?php echo $height . 'px';?>; background-color: <?php echo $btn_color;?>" type="submit"><?php echo $btn_label;?></button>
                    </div>
                    <div class="cloeve-success-container"><h5>Success!</h5></div>
                </div>
            </form>
        <?php }else if($type == 2){ ?>
            <form class="ctEmailListBuilderForm" onsubmit="subscribeEmail();return false;">
                <div class="cloeve-subscriber-container">
                    <h2><?php echo $action_title;?></h2>
                    <p><?php echo $action_paragraph;?></p>
                    <div class="cloeve-email-list-input-container">
                        <input type="hidden" id="cloeveMailSource" name="cloeveMailSource" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                        <input type="text" id="cloeveMailEmail" name="cloeveMailEmail" class="cloeve-email-list-input"  style="height: <?php echo $height . 'px';?>; line-height: <?php echo $height . 'px';?>" placeholder="<?php echo $placeholder;?>">
                        <button class="cloeve-btn-subscribe" style="height: <?php echo $height . 'px';?>; line-height: <?php echo $height . 'px';?>; background-color: <?php echo $btn_color;?>" type="submit"><?php echo $btn_label;?></button>
                    </div>
                    <div class="cloeve-success-container"><h5>Success!</h5></div>
                </div>
            </form>
        <?php }else{ ?>
            <form class="ctEmailListBuilderForm" onsubmit="subscribeEmail();return false;">
                <div class="cloeve-email-list-input-container">
                    <input type="hidden" id="cloeveMailSource" name="cloeveMailSource" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                    <input type="text" id="cloeveMailEmail" name="cloeveMailEmail" class="cloeve-email-list-input"  style="height: <?php echo $height . 'px';?>; line-height: <?php echo $height . 'px';?>" placeholder="<?php echo $placeholder;?>">
                    <button class="cloeve-btn-subscribe" style="height: <?php echo $height . 'px'?>; line-height: <?php echo $height . 'px';?>; background-color: <?php echo $btn_color;?>" type="submit"><?php echo $btn_label;?></button>
                </div>
                <div class="cloeve-success-container"><h5>Success!</h5></div>
            </form>
        <?php }
        return ob_get_clean();
    }

    /**
     * input_shortcode_handler
     * @param $atts
     * @param $content
     * @param $tag
     * @return string
     */
    static function input_shortcode_handler( $atts, $content, $tag ){

        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$atts, CASE_LOWER);

        // args

        // sanitize
        $type = (int)self::sanitize_shortcode_tag($atts, 'type', 0);
        $height = (int)self::sanitize_shortcode_tag($atts, 'height', 40);
        $bg_color = (string)self::sanitize_shortcode_tag($atts, 'bg_color', 'transparent');
        $action_title = (string)self::sanitize_shortcode_tag($atts, 'action_title', 'Subscribe');
        $action_paragraph = (string)self::sanitize_shortcode_tag($atts, 'action_paragraph', 'Did you enjoy reading this? If so, enter your email below to receive all the latest features, updates, and news!');
        $placeholder = (string)self::sanitize_shortcode_tag($atts, 'placeholder', 'Enter your email address to receive the latest updates, features, and news');
        $btn_label = (string)self::sanitize_shortcode_tag($atts, 'btn_label', 'Subscribe');
        $btn_color = (string)self::sanitize_shortcode_tag($atts, 'btn_color', 'default');


        return self::list_input_layout($type, $height, $bg_color, $action_title, $action_paragraph, $placeholder, $btn_label, $btn_color);
    }

    static function sanitize_shortcode_tag($tags, $key, $default) {
        $value = key_exists($key, $tags) ? (string)$tags[$key] : $default;
        if (strpos($value, 'php') !== false || strpos($value, '<') !== false || strpos($value, '>') !== false) {
            $value = $default;
        }

        return $value;
    }

    /**
     *  Export CSV API
     */
    static function export_csv() {

        global $wpdb;
        $table_name = $wpdb->prefix . CLOEVE_MAIL_TABLE;

        // query
        $sql = "SELECT * FROM $table_name";
        $results = $wpdb->get_results( $sql, 'ARRAY_A' );;

        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=cloeve_mail_list.csv');

        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');

        // output the column headings
        fputcsv($output, array('Email', 'Source', 'Date'));

        // loop over the rows, outputting them
        foreach ($results AS $result) {

            $timezone = get_option('timezone_string');
            if(empty($timezone)){
                $created_at = (new DateTime($result['created_at']))->format('F j, Y h:i A');
            }else{
                $created_at = (new DateTime($result['created_at']))->setTimezone(new DateTimeZone($timezone))->format('F j, Y h:i A');
            }


            if(key_exists('email', $result) && !empty($result['email']) && $result['email'] != null && $result['email'] != 'null'){
                fputcsv($output, [esc_html($result['email']), esc_html($result['source']), esc_html($created_at)]);
            }
        }

        // Close the output stream
        //fclose($output);
    }

    /**
     *  Subscribe email API
     */
    static function subscribe_email() {

        // get args
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $source = isset($_POST['source']) ? $_POST['source'] : 'UNKNOWN';

        // sanitize args
        $email = sanitize_email($email);
        $email = esc_html($email);
        $source = sanitize_text_field($source);
        $source = esc_html($source);

        // double check & validate
        if(!is_email($email) || empty($email)){
            return new WP_Error( 'no_email', 'No valid email found, please try again.', array( 'status' => 400 ) );
        }

        // double check & validate
        if (strpos($email, 'php') !== false || strpos($email, '<') !== false || strpos($email, '>') !== false) {
            return new WP_Error( 'no_email', 'Invalid email found, please try again.', array( 'status' => 400 ) );
        }

        // double check & validate
        if (strpos($source, 'php') !== false || strpos($source, '<') !== false || strpos($source, '>') !== false) {
            $source = 'UNKNOWN';
        }

        // db
        global $wpdb;
        $cloeve_email_subscriber = $wpdb->prefix . CLOEVE_MAIL_TABLE;

        // insert new
        $wpdb->insert($cloeve_email_subscriber, ['email' => $email, 'source' => $source, 'created_at' => gmdate('Y-m-d H:i:s')]);
        $insert_id = $wpdb->insert_id;


        return json_encode(['message' => 'Successfully subscribe!']);
    }

    /** Singleton instance */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}


// load plugin
add_action( 'plugins_loaded', function () {
    Cloeve_Mail_Plugin::get_instance();
} );