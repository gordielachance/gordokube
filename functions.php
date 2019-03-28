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
    static $kubist_restrict_metaname = '_kubist_restrict';
    
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

        
        add_action( 'wp_enqueue_scripts', array($this,'scripts_styles') );
        add_action( 'admin_enqueue_scripts', array($this,'admin_scripts_styles') );

        /*
        Coworkers
        */
        add_action('init', array($this,'register_coworkers_post_type') );
        add_action('init', array($this,'add_kubist_role') );
        add_action('pre_get_posts', array($this,'home_include_coworkers') );
        add_filter('gordo_get_hentry_icon', array($this,'get_hentry_coworker_icon'), 10, 2 );
        add_filter('get_the_excerpt', array($this,'coworkers_excerpt_more') );
        add_action('post_submitbox_misc_actions', array($this,'kubist_restrict_checkbox') );
        add_action('save_post', array($this,'kubist_restrict_save') );
        add_action('pre_get_posts', array($this,'kubist_restrict_query') );
        add_filter('body_class', array($this,'kubist_restrict_body_class') );
        add_filter('post_class', array($this,'kubist_restrict_post_class') );

        /*
        Events
        */
        
        //TOFIX TESTING add_filter( 'tribe_events_template', array($this,'events_template'), 10, 2 );
        add_action( 'parse_query', array($this,'events_parse_query'), 99 );

        add_filter('gordo_get_hentry_icon', array($this,'get_hentry_event_icon'), 10, 2 );
        //add_filter( 'the_content', array($this,'page_calendar_content')); //TO FIX TO REMOVE? no more used
        add_filter('gordo_get_sidebar', array($this,'single_event_sidebar'));
        
        add_filter('body_class', array($this,'events_body_classes') );
        add_filter('post_class', array($this,'past_event_post_class') );
        add_filter('the_content', array($this,'past_single_event_notice') );
        add_filter('the_excerpt', array($this,'single_event_excerpt_schedule') );
        
        add_action( 'loop_start', array($this,'remove_jetpack_share') );
        
        //time
        //TO FIX sort events by start date?
        add_action('pre_get_posts', array($this,'events_sort_by_start_date') ); //when events query, sort by start date
        /*
        add_filter('get_the_time', array($this,'single_event_hentry_time'), 10, 3); //update post date = event start date so less confusing
        TOFIX TESTING 
        */
        
        //open price
        add_action( 'tribe_events_cost_table', array($this,'event_backend_open_price'), 9 );
        add_action( 'tribe_events_event_save', array($this,'event_save_open_price'), 10, 3 );
        add_filter( 'tribe_get_cost',array($this,"event_get_open_price"),10,3);

    }

    function events_template($file, $template){
        if ($template=='list.php'){
            $template = get_index_template();
        }
        return $template;
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
                'author',
                'thumbnail'
            ),
            'taxonomies' => array('post_tag' ),
        ));
    }
    
    /*
    This does not add a new role but renames "contributor" to "kubist".
    */
    function add_kubist_role(){
        global $wp_roles;

        if ( ! isset( $wp_roles ) )
            $wp_roles = new WP_Roles();
        
        $wp_roles->roles['contributor']['name'] = $wp_roles->role_names['contributor'] = __('Kubist','gordokube');  
    }
    
    function kubist_restrict_checkbox(){
        $post_id = get_the_ID();
        $cannot_restrict_types = array();

        if ( in_array(get_post_type($post_id),$cannot_restrict_types) ) {
            return;
        }

        $restricted = get_post_meta($post_id, self::$kubist_restrict_metaname, true);
        wp_nonce_field('kubist_limit_nonce_'.$post_id, 'kubist_limit_nonce');
        ?>
        <div class="misc-pub-section misc-pub-section-last">
            <label><input type="checkbox" value="1" <?php checked($restricted, true, true); ?> name="_kubist_restrict" /><i class="fa fa-lock" aria-hidden="true"></i> <?php _e('Restrict to Kubists!', 'gordokube'); ?></label>
        </div>
        <?php
    }
    
    function kubist_restrict_save($post_id){
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if ( !isset($_POST['kubist_limit_nonce']) || !wp_verify_nonce($_POST['kubist_limit_nonce'], 'kubist_limit_nonce_'.$post_id) ) {
            return;
        }


        if (isset($_POST['_kubist_restrict'])) {
            update_post_meta($post_id, self::$kubist_restrict_metaname, $_POST['_kubist_restrict']);
        } else {
            delete_post_meta($post_id, self::$kubist_restrict_metaname);
        }
    }
    
    static function is_kubist(){
        $is_kubist = is_user_logged_in() && ( current_user_can( 'contributor' ) || current_user_can( 'author' ) || current_user_can( 'editor' ) || current_user_can( 'administrator' ) );
        return $is_kubist;
    }
    
    function kubist_restrict_query($query){
        
        if (!is_admin() && $query->is_main_query()) {
            
            $user = wp_get_current_user();

            if ( !self::is_kubist() ){
                $meta_query = $query->get('meta_query') ? $query->get('meta_query') : array();
                //Add our meta query to the original meta queries
                $meta_query[] = array(
                    'key'=>         self::$kubist_restrict_metaname,
                    'compare' =>    'NOT EXISTS'
                );
                $query->set('meta_query',$meta_query);
                
            }
        }
        
    }
    
    function kubist_restrict_body_class($classes){
        $restricted = get_post_meta(get_the_ID(), self::$kubist_restrict_metaname, true);
        
        if ( self::is_kubist() ){
            $classes[] = 'is-kubist';
        }

        return $classes;
    }
    
    function kubist_restrict_post_class($classes){
        $restricted = get_post_meta(get_the_ID(), self::$kubist_restrict_metaname, true);
        
        if ( $restricted ){
            $classes[] = 'kubist-restrict';
        }

        return $classes;
    }
    
    function home_include_coworkers( $query ) {
        if ( $query->is_main_query() && is_home() ) {
            $query->query_vars['post_type']   = isset( $query->query_vars['post_type'] ) ? ( array ) $query->query_vars['post_type'] : array( 'post' );
            $query->query_vars['post_type'][] = self::$coworker_post_type;
        }
        return $query;
    }

    function get_hentry_event_icon($icon,$post_id){
        if ( !class_exists( 'Tribe__Events__Main' ) ) return $icon;
        $post_type = get_post_type($post_id);
        if ($post_type == Tribe__Events__Main::POSTTYPE){
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
    if there is a "more" tag, 
    add a "continue reading" link.
    */
    function coworkers_excerpt_more($excerpt){
        global $post;
        $post_type = get_post_type();
        if ( $post_type == self::$coworker_post_type ){
            $has_more_tag = strpos( $post->post_content, '<!--more-->' );
            if ($has_more_tag){
                $excerpt .= gordo()->excerpt_more_text();
            }
        }
        return $excerpt;
    }

    /*
    The Events calendar do a lot of weird stuff regarding template, loop, etc etc.
    We want to ignore its behaviour at certain time.  
    So define the events query as false; which will abord most of its functions.
    */
    
    function events_parse_query($query){
        
        if( !$query->is_main_query() ) return;
        
        if ($query->get('post_type') == Tribe__Events__Main::POSTTYPE) {

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
  
    
    function event_backend_open_price($event_id){
        $isOpenPrice = ($event_id) ? get_post_meta( $event_id, '_EventOpenPrice', true ) : false;
        ?>
        <tr>
            <td><?php esc_html_e( 'Open Price:', 'labokube' ); ?></td>
            <td>
                <input
                    tabindex="<?php tribe_events_tab_index(); ?>"
                    type="checkbox"
                    id="openPriceCheckbox"
                    name="EventOpenPrice"
                    value="1"
                    <?php checked( $isOpenPrice ); ?>
                />
            </td>
        </tr>
			<tr>
				<td></td>
				<td>
					<small><?php echo esc_html__( 'Use the price field above as a suggested price.', 'labokube' ); ?></small>
				</td>
			</tr>
        <?php
    }
    
    function event_save_open_price($event_id, $data, $event){
        $isOpenPrice = isset($_POST['EventOpenPrice']);
        
        if ($isOpenPrice){
            update_post_meta($event_id,'_EventOpenPrice',true);
        }else{
            delete_post_meta($event_id,'_EventOpenPrice');
        }
    }
    
    function event_get_open_price($cost, $event_id, $with_currency_symbol){
        $isOpenPrice = get_post_meta( $event_id, '_EventOpenPrice', true );
        if ($isOpenPrice){

            if ( $cost == esc_html__( 'Free', 'the-events-calendar' ) ) $cost = null; //free
            
            if ($cost){
                $suggested_txt = sprintf( '<small>' . __("suggested: %s","labokube") . '</small>',$cost );
                $cost = sprintf(__("Open Price - %s","labokube"),$suggested_txt);
            }else{
                $cost = __("Open Price","labokube");
            }
            
            
        }
        return $cost;
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
        if ( is_singular(Tribe__Events__Main::POSTTYPE) ){
            $sidebar_name = 'tribe_events';
        }
        return $sidebar_name;
    }
    
    /*
    Sort events by event start date, not by post date
    //TO FIX possible to do even on regular queries where multiple post types are queried ?
    */
    
    function events_sort_by_start_date($query){
        
        if( !$query->is_main_query() ) return;
        
        if ($query->get('post_type') == Tribe__Events__Main::POSTTYPE) {
            //order by startdate from newest to oldest
            $query->set( 'meta_key', '_EventStartDate' );
            $query->set( 'orderby', '_EventStartDate' );
            $query->set( 'order', 'DESC' );
        }

    }
    
    /*
    For events, we don't want that to display the time the hentry was posted, as it might be confusing: use the event start time instead.
    */
    function single_event_hentry_time($the_time, $d, $post){
        if ( class_exists( 'Tribe__Events__Main' ) ) {
            $post_type = get_post_type($post);
            if ($post_type == Tribe__Events__Main::POSTTYPE){
                $the_time = tribe_get_start_date( $post, false, $d );
            }
        }
        return $the_time;
    }
    
    function is_past_event($post = null){
        global $post;
        
        if ( class_exists( 'Tribe__Events__Main' ) ) {
            $post_type = get_post_type($post);
            if ($post_type == Tribe__Events__Main::POSTTYPE){
                return tribe_is_past_event();
            }
        }
        return false;
    }
    
    function events_body_classes($classes){
        $post_type = get_post_type();
        if ( $post_type == Tribe__Events__Main::POSTTYPE ){
            if ( !is_single() ){
                $classes[] = "archive";
            }
        }

        return $classes;
    }
    
    function past_event_post_class($classes){
        $post_type = get_post_type();
        
        if ( ($post_type == Tribe__Events__Main::POSTTYPE) && $this->is_past_event() ){
            $classes[] = 'tribe-events-past';
        }

        return $classes;
    }
    
    function past_single_event_notice($content){
        if ( is_singular(Tribe__Events__Main::POSTTYPE) && $this->is_past_event() ){
            $notice = '<p class="gordo-notice">Cet évènement est passé.</p>';
            return $notice . $content;
        }

        return $content;
    }
    function single_event_excerpt_schedule($excerpt){
        $post_type = get_post_type();
        if ($post_type == Tribe__Events__Main::POSTTYPE){
            $excerpt = sprintf('<span class="tribe-event-duration gordo-notice">%s</span>',tribe_events_event_schedule_details()) . $excerpt;
        }
        return $excerpt;
    }
    
    //remove default sharing buttons for single events
    function remove_jetpack_share(){
        if ( !is_singular(Tribe__Events__Main::POSTTYPE)  ) return;
        
        remove_filter( 'the_content', 'sharing_display', 19 );
        remove_filter( 'the_excerpt', 'sharing_display', 19 );
        if ( class_exists( 'Jetpack_Likes' ) ) {
            remove_filter( 'the_content', array( Jetpack_Likes::init(), 'post_likes' ), 30, 1 );
        }
    }
}

function gordokube() {
	return Gordokube::instance();
}

gordokube();

/*
Temporary hack to fix description displayed in og:meta with the plugin Open Graph for Facebook, Google+ and Twitter Card Tags
*/

function hackfix_no_pwd_desc($desc){
    if ( post_password_required() ){
	$desc = __("This content is password protected.");
    }
    return $desc;
}

add_filter('fb_og_desc','hackfix_no_pwd_desc');
