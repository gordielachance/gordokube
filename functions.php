<?php
/**
 * The main function responsible for returning the one true Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return The one true Instance
 */

class Gordokube{
    
    static $calendar_page_id = 67;
    static $coworker_post_type = 'coworker';
    
    /** Version ***************************************************************/
    /**
    * @public string plugin version
    */
    public $version = '1.0.0';
    /**
    * @public string plugin DB version
    */
    public $db_version = '100';
    /** Paths *****************************************************************/
    
	/**
	 * @var The one true Instance
	 */
	private static $instance;

	/**
	 * Main Instance
	 *
	 * Insures that only one instance of the plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @staticvar array $instance
	 * @return The instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Gordokube;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}
    
    function __construct(){ 
    }
    
    function setup_globals(){
    }
    
    function includes(){
    }
    
    function setup_actions(){
        add_action('init', array($this,'register_coworkers_post_type') );
        add_action('pre_get_posts', array($this,'include_blog_coworkers') );
        
        add_action( 'wp_enqueue_scripts', array($this,'scripts_styles') );
        add_action( 'admin_enqueue_scripts', array($this,'admin_scripts_styles') );
        //add_filter( 'the_content', array($this,'page_calendar_content')); //TO FIX TO REMOVE? no more used
        add_filter('gordo_get_hentry_icon', array($this,'get_hentry_event_icon'), 10, 2 );
        add_filter('gordo_get_hentry_icon', array($this,'get_hentry_coworker_icon'), 10, 2 );
        add_filter('gordo_get_sidebar', array($this,'single_event_sidebar'));
        add_filter('get_the_time', array($this,'single_event_hentry_time'), 10, 3);
        
        add_filter('body_class', array($this,'calendar_view_class') );
        add_filter('post_class', array($this,'past_event_post_class') );
        add_filter('the_content', array($this,'past_single_event_notice') );
        add_filter('the_excerpt', array($this,'single_event_excerpt_schedule') );
        add_action( 'parse_query', array($this,'events_parse_query'), 99 );
        
        
    }
	function scripts_styles() {
        /*
        Scripts
        */

        /*
        Styles
        */
        wp_register_style( 'gordokube_style', get_stylesheet_directory_uri() . '/_inc/css/gordokube.css',null,$this->version );
        wp_enqueue_style( 'gordokube_style' );
	}
    function admin_scripts_styles(){
        
    }
    
    function register_coworkers_post_type(){
        $labels = array(
            'name'               => _x( 'Co-workers', 'post type general name', 'gordokube' ),
            'singular_name'      => _x( 'Co-worker', 'post type singular name', 'gordokube' ),
            'menu_name'          => _x( 'Co-workers', 'admin menu', 'gordokube' ),
            'name_admin_bar'     => _x( 'Co-worker', 'add new on admin bar', 'gordokube' ),
            'add_new'            => _x( 'Add New', 'co-worker', 'gordokube' ),
            'add_new_item'       => __( 'Add New Co-worker', 'gordokube' ),
            'new_item'           => __( 'New Co-worker', 'gordokube' ),
            'edit_item'          => __( 'Edit Co-worker', 'gordokube' ),
            'view_item'          => __( 'View Co-worker', 'gordokube' ),
            'all_items'          => __( 'All Co-workers', 'gordokube' ),
            'search_items'       => __( 'Search Co-workers', 'gordokube' ),
            'parent_item_colon'  => __( 'Parent Co-workers:', 'gordokube' ),
            'not_found'          => __( 'No co-workers found.', 'gordokube' ),
            'not_found_in_trash' => __( 'No co-workers found in Trash.', 'gordokube' )
        );

        register_post_type(self::$coworker_post_type, array(
            'public' => true,
            'labels' => $labels,
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-admin-users',
            'supports' => array(
                'title',
                'editor',
                'thumbnail'
            ),
            'taxonomies' => array('post_tag' ),
        ));
    }
    
    function include_blog_coworkers( $query ) {
        if ( $query->is_main_query() && is_home() ) {
            $query->query_vars['post_type'][] = self::$coworker_post_type;
        }
        return $query;
    }
    
    function events_ignore_pre_get_posts(){
        remove_action( 'pre_get_posts', array( 'Tribe__Events__Query', 'pre_get_posts' ), 50 );
    }

    function get_hentry_event_icon($icon,$post_id){
        if ( !class_exists( 'Tribe__Events__Main' ) ) return $icon;
        $post_type = get_post_type($post_id);
        if ($post_type == 'tribe_events'){
            $icon = '<i class="fa fa-calendar-check-o" aria-hidden="true"></i>';
        }
        return $icon;
        
    }
    function get_hentry_coworker_icon($icon,$post_id){
        $post_type = get_post_type($post_id);
        if ($post_type == self::$coworker_post_type){
            $icon = '<i class="fa fa-user-circle" aria-hidden="true"></i>';
        }
        return $icon;
    }

    /*
    The Events calendar do a lot of weird stuff regarding template, loop, etc etc.
    We want to ignore its behaviour at certain time.  
    So define the events query as false; which will abord most of its functions.
    */
    
    function events_parse_query($query){
        
        if( !$query->is_main_query() ) return;
        
        if ($query->get('post_type') == 'tribe_events') {

            if ( $query->is_singular() ){
                // will abord most of the plugin's function when displaying the single event; which do way too much stuff when displaying the template
                $query->tribe_is_event = false;
                $query->tribe_is_event_query = false;
            }else{ //archive
                $display = ( isset($_REQUEST['tribe_event_display']) ) ? $_REQUEST['tribe_event_display'] : 'list';
                //if no display defined OR list, load regular templates and query
                if ($display=='list'){
                    $query->tribe_is_event = false;
                    $query->tribe_is_event_query = false;
                }
            }
            
            
            //$query->tribe_is_event = false;
            //$query->tribe_is_event_query = false;
        }

    }

    /*
    Embed the month view on the calendar page
    */
    function page_calendar_content($content){
        if ( is_page(self::$calendar_page_id) ){
            if ( class_exists( 'Tribe__Events__Main' ) ) {
                
                /*
                Events query
                */
                
                $args = array('post_type'=>Tribe__Events__Main::POSTTYPE);
                query_posts($args);

                
                ob_start();
                tribe_show_month();
                $calendar = ob_get_clean();
                
                //reset query
                wp_reset_query();
                
                $content.= $calendar;
            }
        }
        return $content;
    }
    
    function single_event_sidebar($sidebar_name){
        if ( is_singular('tribe_events') ){
            $sidebar_name = 'tribe_events';
        }
        return $sidebar_name;
    }
    
    /*
    For events, we don't want that to display the time the hentry was posted, as it might be confusing: use the event start time instead.
    */
    function single_event_hentry_time($the_time, $d, $post){
        if ( class_exists( 'Tribe__Events__Main' ) ) {
            $post_type = get_post_type($post);
            if ($post_type == 'tribe_events'){
                $the_time = tribe_get_start_date( $post, false, $d );
            }
        }
        return $the_time;
    }
    
    function is_past_event($post = null){
        global $post;
        
        if ( class_exists( 'Tribe__Events__Main' ) ) {
            $post_type = get_post_type($post);
            if ($post_type == 'tribe_events'){
                return tribe_is_past_event();
            }
        }
        return false;
    }
    
    function calendar_view_class($classes){
        return $classes;
    }
    
    function past_event_post_class($classes){
        $post_type = get_post_type();
        
        if ( ($post_type == 'tribe_events') && $this->is_past_event() ){
            $classes[] = 'tribe-events-past';
        }

        return $classes;
    }
    
    function past_single_event_notice($content){
        if ( is_singular('tribe_events') && $this->is_past_event() ){
            $notice = '<p class="gordo-notice">Cet évènement est passé.</p>';
            return $notice . $content;
        }

        return $content;
    }
    function single_event_excerpt_schedule($excerpt){
        $post_type = get_post_type();
        if ($post_type == 'tribe_events'){
            $excerpt = sprintf('<span class="tribe-event-duration gordo-notice">%s</span>',tribe_events_event_schedule_details()) . $excerpt;
        }
        return $excerpt;
    }
}

function gordokube() {
	return Gordokube::instance();
}

gordokube();