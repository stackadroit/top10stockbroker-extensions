<?php

namespace Stackadroit\Topstockbroker\Modules;

/**
 * Create CPT
 */
class CustomPostTypeModule extends AbstractModule
{
    /**
     * Name of the module.
     *
     * @var string
     */
    protected $name = 'custom-post-type';

    /**
     * Default options.
     *
     * @var array
     */
    protected $defaults = [
        /**
         * Enable this module.
         *
         * @var bool
         */
        'enabled' => true,
    ];

    /**
     *  Configuration directory path
     *
     * @since    1.0.0
     * @static
     * @access   public
     * @var      string
     */
    protected static $arg_dir_path;

    /**
     * Optional argument to override
     *
     * @since    1.0.0
     * @access   protected
     * @var      array
     */
    protected $arg_overrides = array();

    /**
     * CPT registrate arguments
     *
     * @since    1.0.0
     * @access   protected
     * @var      array
     */
    protected $cpt_args = array();

    /**
     * Pre CPT registrate arguments
     *
     * @since    1.0.0
     * @access   protected
     * @var      array
     */
    protected $pre_cpt_args = array();

    /**
     * Current active screen cpt argumnets
     *
     * @since    1.0.0
     * @access   protected
     * @var      array
     */
    protected $active_cpt_args = array();

    /**
     * Current post type.
     *
     * @since    1.0.0
     * @access   protected
     * @var      bool
     */
    protected static $c_post_type = '';

    /**
     * Runtime active cpt pointer
     *
     * @since    1.0.0
     * @access   protected
     * @var      string
     */
    protected static $active_cpt;

    /**
     * An array of each top10stockbroker_CPT object registered with this class
     *
     * @since    1.0.0
     * @access   protected
     * @var      array
     */
    protected static $custom_post_types = array();

    /**
     * Module handle.
     *
     * @return void
     */
    public function handle()
    {
       $this->filter('init', 'load');
    }

    /**
     * Condition under which the module is loaded.
     *
     * @return bool
     */
    protected function condition()
    {
        return apply_filters(
            'topstockbroker/load-module/' . $this->provides(),
            $this->options->enabled
        );
    }

    /**
     * load
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function load()
    {
       $this->add_cpts_by_filter();
       $this->load_cpt();
       $this->current_screen_cpt();
    }

    /**
     * Hooks
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function hookup() {

        add_filter( 'post_updated_messages', array( $this, 'messages' ) );
        add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_messages' ), 10, 2 );
        add_filter( 'enter_title_here', array( $this, 'title' ) );
        add_filter( 'manage_edit-' . self::$c_post_type . '_columns', array( $this, 'columns' ) );
        add_filter( 'manage_edit-' . self::$c_post_type . '_sortable_columns', array( $this, 'sortable_columns' ) );
        // Different column registration for pages/posts
        $h = isset( $this->active_cpt_args['hierarchical'] ) && $this->active_cpt_args['hierarchical'] ? 'pages' : 'posts';
        add_action( "manage_{$h}_custom_column", array( $this, 'columns_display' ), 10, 2 );
        
    }

    /**
     * Check if arguments are valid
     *
     * @since    1.0.0
     * @access   public
     * @param    array  $cpt
     * @return   void
     */
    public function error_check($cpt){
        if ( ! is_array( $cpt ) ) {
            wp_die( __( 'It is required to pass a single, plural and slug string to CPT code 101', 'top10stockbroker-ext' ) );
        }

        if ( ! isset( $cpt[0], $cpt[1], $cpt[2] ) ) {
            wp_die( __( 'It is required to pass a single, plural and slug string to CPT code 102', 'top10stockbroker-ext' ) );
        }

        if ( ! is_string( $cpt[0] ) || ! is_string( $cpt[1] ) || ! is_string( $cpt[2] ) ) {
            wp_die( __( 'It is required to pass a single, plural and slug string to CPT code 103', 'top10stockbroker-ext' ) );
        }
    }

    /**
     * Add Cpt's as using filter
     *
     * @since    1.0.0
     * @access   public
     * @return   void
     */
    public function add_cpts_by_filter(){
        foreach (apply_filters( 'top10stockbroker_cpt', array()) as $cpt) {
            $this->pre_cpt_args($cpt['cpt'], array($cpt['cpt'], $cpt['arg_overrides']));
        }   
    }

    /**
     *
     * @since    1.0.0
     * @access   public
     * @param    array  $cpt
     * @param    array  $pre_cpt_args
     * @return   void
     */
    public function pre_cpt_args($cpt, array $pre_cpt_args){
            $this->error_check($cpt);
            $this->pre_cpt_args[$cpt[2]] = $pre_cpt_args;
    }

    /**
     * Bootup CPT register.
     * 
     * @since    1.0.0
     * @access   public
     * @return   void
     */
    public function load_cpt() {
        foreach ($this->pre_cpt_args as $arg) {
            self::$active_cpt = $arg[0][2];
            $this->register_post_type();
        }
    }

    /**
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function current_screen_cpt() {

        self::$c_post_type = $this->c_post_type();
        
        if (  self::$c_post_type ) {
             $this->active_cpt_args = $this->prop(self::$c_post_type);
            if ($this->active_cpt_args) {
                $this->active_cpt_args["cpt"] = self::$c_post_type;
                $this->active_cpt_args["singular"] = $this->active_cpt_args["labels"]['singular_name'];
                $this->active_cpt_args["plural"] = $this->active_cpt_args["labels"]['name'];
            }
        }

        return;
    }

    /**
     * 
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function c_post_type() {
        global $pagenow;

        $post_type = '';
        if ( in_array( $pagenow, array( 'post.php', 'edit.php', 'post-new.php'), true ) ) {     
            $post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : $post_type;
            if (empty($post_type)) {
                $obj = isset( $_REQUEST['post'] ) ? get_post($_REQUEST['post']) : $post_type;
                return $obj->post_type;
            }
        }
            
        return $post_type;
    }

    /**
     * Gets the requested CPT argument
     *
     * @since  1.0.1
     * @access public
     * @param array $cpt
     * @param array $arg
     * @return array|false  CPT argument
     */
    public function get_arg( $cpt,$arg ) {

        $args = $this->prop($cpt);
        if ( isset( $args->{$arg} ) ) {
            return $args->{$arg};
        }
        if ( is_array( $args ) && isset( $args[ $arg ] ) ) {
            return $args[ $arg ];
        }

        return false;
    }

    /**
     * Get metabox property and optionally set a fallback
     *
     * @since  1.0.0
     * @access public
     * @param  string $property Metabox config property to retrieve
     * @param  mixed  $fallback Fallback value to set if no value found
     * @return mixed            Metabox config property value or false
     */
    public function prop( $property, $fallback = null ) {
        if ( array_key_exists( $property, $this->cpt_args ) ) {
            return $this->cpt_args[ $property ];
        } elseif ( $fallback ) {
            return $this->cpt_args[ $property ] = $fallback;
        }
    }

    /**
     * Get the passed in arguments combined with our defaults.
     * 
     * @since  1.0.0
     * @access public
     * @return array  CPT arguments array
     */
    public function get_args() {

        $cpt = $this->pre_cpt_args[self::$active_cpt][0];
        $arg_overrides = $this->pre_cpt_args[self::$active_cpt][1];

        $singular  = $cpt[0];
        $plural    = ! isset( $cpt[1] ) || ! is_string( $cpt[1] ) ? $cpt[0] . 's' : $cpt[1];
        $post_type = ! isset( $cpt[2] ) || ! is_string( $cpt[2] ) ? sanitize_title( $plural ) : $cpt[2];

        // Generate CPT labels
        $labels = array(
            'name'                  => $plural,
            'singular_name'         => $singular,
            'add_new'               => sprintf( __( 'Add New %s', 'top10stockbroker-ext' ), $singular ),
            'add_new_item'          => sprintf( __( 'Add New %s', 'top10stockbroker-ext' ), $singular ),
            'edit_item'             => sprintf( __( 'Edit %s', 'top10stockbroker-ext' ), $singular ),
            'new_item'              => sprintf( __( 'New %s', 'top10stockbroker-ext' ), $singular ),
            'all_items'             => sprintf( __( 'All %s', 'top10stockbroker-ext' ), $plural ),
            'view_item'             => sprintf( __( 'View %s', 'top10stockbroker-ext' ), $singular ),
            'search_items'          => sprintf( __( 'Search %s', 'top10stockbroker-ext' ), $plural ),
            'not_found'             => sprintf( __( 'No %s', 'top10stockbroker-ext' ), $plural ),
            'not_found_in_trash'    => sprintf( __( 'No %s found in Trash', 'top10stockbroker-ext' ), $plural ),
            'parent_item_colon'     => isset( $arg_overrides['hierarchical'] ) && $arg_overrides['hierarchical'] ? sprintf( __( 'Parent %s:', 'top10stockbroker-ext' ), $singular ) : null,
            'menu_name'             => $plural,
            'insert_into_item'      => sprintf( __( 'Insert into %s', 'top10stockbroker-ext' ), strtolower( $singular ) ),
            'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %s', 'top10stockbroker-ext' ), strtolower( $singular ) ),
            'items_list'            => sprintf( __( '%s list', 'top10stockbroker-ext' ), $plural ),
            'items_list_navigation' => sprintf( __( '%s list navigation', 'top10stockbroker-ext' ), $plural ),
            'filter_items_list'     => sprintf( __( 'Filter %s list', 'top10stockbroker-ext' ), strtolower( $plural ) )
        );

        // Set default CPT parameters
        $defaults = array(
            'labels'             => array(),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'has_archive'        => true,
            'supports'           => array( 'title', 'editor', 'excerpt' ),
        );

        $arg_overrides = apply_filters( "top10stockbroker_" . $post_type . "_cpt_arg_overrides", (array) $arg_overrides);

        $cpt_args = wp_parse_args( $arg_overrides, $defaults );
        $cpt_args['labels'] = wp_parse_args( $cpt_args['labels'], $labels );

        $this->cpt_args[self::$active_cpt] = $cpt_args;
        return $cpt_args;
    }

    /**
     * Registers CPT with the merged arguments
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function register_post_type() {
        
        // Register CPT
        $args = register_post_type( self::$active_cpt, $this->get_args() );

        // If error, yell about it.
        if ( is_wp_error( $args ) ) {
            wp_die( $args->get_error_message() );
        }

        // Add this post type to our custom_post_types array
        self::$custom_post_types[ self::$active_cpt ] = $args;
    }

    /**
     * Modifies CPT based messages to include CPT labels
     * 
     * @since  1.0.0
     * @access public
     * @param  array  $messages Array of messages
     * @return array            Modified messages array
     */
    public function messages( $messages ) {
        global $post, $post_ID;
        
        $cpt_messages = array(
            0 => '', // Unused. Messages start at index 1.
            2 => __( 'Custom field updated.' ,'top10stockbroker-ext' ),
            3 => __( 'Custom field deleted.' ,'top10stockbroker-ext'),
            4 => sprintf( __( '%1$s updated.', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"]),
            /* translators: %s: date and time of the revision */
            5 => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"] , wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            7 => sprintf( __( '%1$s saved.', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"] ),
        );

        if ( $this->active_cpt_args['public'] ) {

            $cpt_messages[1] = sprintf( __( '%1$s updated. <a href="%2$s">View %1$s</a>', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"], esc_url( get_permalink( $post_ID ) ) );
            $cpt_messages[6] = sprintf( __( '%1$s published. <a href="%2$s">View %1$s</a>', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"], esc_url( get_permalink( $post_ID ) ) );
            $cpt_messages[8] = sprintf( __( '%1$s submitted. <a target="_blank" href="%2$s">Preview %1$s</a>', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"], esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) );
            // translators: Publish box date format, see http://php.net/date
            $cpt_messages[9] = sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %1$s</a>', 'top10stockbroker-ext' ), $this->singular, date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) );
            $cpt_messages[10] = sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %1$s</a>', 'top10stockbroker-ext' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) );

        } else {

            $cpt_messages[1] = sprintf( __( '%1$s updated.', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"] );
            $cpt_messages[6] = sprintf( __( '%1$s published.', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"] );
            $cpt_messages[8] = sprintf( __( '%1$s submitted.', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"] );
                        // translators: Publish box date format, see http://php.net/date
            $cpt_messages[9] = sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>.', 'top10stockbroker-ext' ), $this->singular, date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) );
            $cpt_messages[10] = sprintf( __( '%1$s draft updated.', 'top10stockbroker-ext' ), $this->active_cpt_args["singular"] );

        }

        $messages[ $this->active_cpt_args["cpt"] ] = $cpt_messages;
        return $messages;
    }

    /**
     * Custom bulk actions messages for this post type
     * @author  Neil Lowden
     *
     * @param  array  $bulk_messages  Array of messages
     * @param  array  $bulk_counts    Array of counts under keys 'updated', 'locked', 'deleted', 'trashed' and 'untrashed'
     * @return array                  Modified array of messages
     */
    public function bulk_messages( $bulk_messages, $bulk_counts ) {
        $bulk_messages[ $this->active_cpt_args["cpt"] ] = array(
            'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'top10stockbroker-ext' ), $bulk_counts['updated'], $this->active_cpt_args["singular"], $this->active_cpt_args["plural"] ),
            'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'top10stockbroker-ext' ), $bulk_counts['locked'], $this->active_cpt_args["singular"], $this->active_cpt_args["plural"]),
            'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'top10stockbroker-ext' ), $bulk_counts['deleted'], $this->active_cpt_args["singular"], $this->active_cpt_args["plural"]),
            'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'top10stockbroker-ext' ), $bulk_counts['trashed'], $this->active_cpt_args["singular"], $this->active_cpt_args["plural"]),
            'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'top10stockbroker-ext' ), $bulk_counts['untrashed'], $this->active_cpt_args["singular"], $this->active_cpt_args["plural"] ),
        );
        return $bulk_messages;
    }

    /**
     * Registers admin columns to display. To be overridden by an extended class.
     *
     * @since  1.0.0
     * @access public
     * @param  array  $columns Array of registered column names/labels
     * @return array           Modified array
     */
    public function columns( $columns ) {
        return apply_filters( 'top10stockbroker_cpt_columns', $columns );
    }

    /**
     * Registers which columns are sortable. To be overridden by an extended class.
     *
     * @since  1.0.0
     * @access public
     * @param  array $sortable_columns Array of registered column keys => data-identifier
     * @return array           Modified array
     */
    public function sortable_columns( $sortable_columns ) {
        return apply_filters( 'top10stockbroker_cpt_sortable_columns', $sortable_columns );
    }

    /**
     * Handles admin column display. To be overridden by an extended class.
     *
     * @since  1.0.0
     * @access public
     * @param  array $column  Array of registered column names
     * @param  int   $post_id The Post ID
     * @return void
     */
    public function columns_display( $column, $post_id ) {
        // placeholder
    }

    /**
     * Filter CPT title entry placeholder text
     *
     * @since  1.0.0
     * @access public
     * @param  string $title Original placeholder text
     * @return string        Modified placeholder text
     */
    public function title( $title ) {
        $screen = get_current_screen();
        if ( isset( $screen->post_type ) && count( $this->active_cpt_args ) && ($screen->post_type == $this->active_cpt_args["cpt"]) ) {
            return sprintf( __( '%s Title', 'top10stockbroker-ext'), $this->active_cpt_args["singular"] );
        }
        return $title;
    }

    /**
     * Provides access to protected class properties.
     *
     * @since  1.0.0
     * @access public
     * @param  string $key Specific CPT parameter to return
     * @return mixed       Specific CPT parameter or array of singular, plural and registered name
     */
    public function post_type( $key = 'post_type' ) {

        return isset( $this->$key ) ? $this->$key : array(
            'singular'  => $this->singular,
            'plural'    => $this->plural,
            'post_type' => $this->post_type,
        );
    }

    /**
     * Provides access to all CPT_Core taxonomy objects registered via this class.
     *
     * @since  1.0.0
     * @param  string $post_type Specific CPT_Core object to return, or 'true' to specify only names.
     * @return mixed             Specific CPT_Core object or array of all
     */
    public static function post_types( $post_type = '' ) {
        if ( true === $post_type && ! empty( self::$custom_post_types ) ) {
            return array_keys( self::$custom_post_types );
        }
        return isset( self::$custom_post_types[ $post_type ] ) ? self::$custom_post_types[ $post_type ] : self::$custom_post_types;
    }

    /**
     * Magic getter for our object.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $field
     * @throws   Exception Throws an exception if the field is invalid.
     * @return   mixed
     */
    public function __get( $field ) {
        switch ( $field ) {
            case 'custom_post_typest':
                return self::$field;
            default:
                '';
        }
    }

}
