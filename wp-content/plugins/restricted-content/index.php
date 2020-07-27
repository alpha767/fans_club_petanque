<?php

/**
* Plugin Name: Restrict
* Plugin URI: https://restrict.io/
* Description: Easily restrict the access to the content on your website to logged in users, users with a specific role or capability, to it's author, Tickera users or WooCommerce users who made any purchases or purchased a specific item.
* Author: Restrict
* Author URI: https://restrict.io/
* Version: 2.1.1
* Text Domain: rsc
* Domain Path: languages
* Copyright 2020 Tickera (https://tickera.com/)
*/
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !function_exists( 'restrict_fs' ) ) {
    // Create a helper function for easy SDK access.
    function restrict_fs()
    {
        global  $restrict_fs ;
        
        if ( !isset( $restrict_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_6013_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_6013_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $is_whitelabel = defined( 'RSC_PLUGIN_TITLE' ) && RSC_PLUGIN_TITLE !== 'Restrict';
            $restrict_fs = fs_dynamic_init( array(
                'id'             => '6013',
                'slug'           => 'restricted-content',
                'premium_slug'   => 'restricted-content-pro',
                'type'           => 'plugin',
                'public_key'     => 'pk_850e333579e4ba2ac0eb27a9f33a6',
                'is_premium'     => false,
                'premium_suffix' => 'PRO',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                'days'               => 7,
                'is_require_payment' => true,
            ),
                'menu'           => array(
                'slug'    => 'restricted_content_settings',
                'contact' => false,
                'support' => false,
                'pricing' => ( !$is_whitelabel ? true : false ),
                'account' => ( !$is_whitelabel ? true : false ),
            ),
                'is_live'        => true,
            ) );
        }
        
        return $restrict_fs;
    }
    
    // Init Freemius.
    restrict_fs();
    // Signal that SDK was initiated.
    do_action( 'restrict_fs_loaded' );
}


if ( !class_exists( 'Restricted_Content' ) ) {
    class Restricted_Content
    {
        var  $version = '2.1.1' ;
        var  $title = 'Restrict' ;
        var  $name = 'rsc' ;
        var  $dir_name = '' ;
        var  $location = 'plugins' ;
        var  $plugin_dir = '' ;
        var  $plugin_url = '' ;
        function __construct()
        {
            $this->set_plugin_dir();
            $this->init_vars();
            add_action( 'plugins_loaded', array( $this, 'localization' ), 9 );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_header' ) );
            add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
            add_action( 'save_post', array( $this, 'save_metabox_values' ) );
            add_filter( 'the_content', array( $this, 'maybe_block_content' ) );
            add_filter( 'rsc_the_content', array( $this, 'maybe_block_content' ) );
            add_filter(
                'plugin_action_links_' . plugin_basename( __FILE__ ),
                array( $this, 'plugin_action_links' ),
                10,
                2
            );
            add_shortcode( 'RSC', array( $this, 'rsc_shortcode' ) );
            add_action( 'admin_menu', array( $this, 'rc_add_admin_menu' ) );
            add_filter(
                'first_rc_menu_handler',
                array( $this, 'first_rc_menu_handler' ),
                10,
                1
            );
            add_action( 'admin_enqueue_scripts', array( $this, 'rsc_admin_header' ) );
            $this->name = apply_filters( 'rsc_plugin_name', $this->name );
            //load general settings class
            require_once $this->plugin_dir . 'includes/classes/class-settings-general.php';
            require_once $this->plugin_dir . 'includes/classes/class-fields.php';
            require_once $this->plugin_dir . 'includes/admin-functions.php';
            require_once $this->plugin_dir . 'includes/freeaddons/comments.php';
            require_once $this->plugin_dir . 'includes/freeaddons/woocommerce-shop-page.php';
            require_once $this->plugin_dir . 'includes/freeaddons/simple-urls.php';
        }
        
        function set_plugin_dir()
        {
            $dir = plugin_basename( __FILE__ );
            $this->dir_name = str_replace( array( '/index.php', '\\index.php' ), '', $dir );
        }
        
        /**
         * setup proper directories
         * @return [type]
         */
        function init_vars()
        {
            
            if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . $this->dir_name . '/' . basename( __FILE__ ) ) ) {
                $this->location = 'subfolder-plugins';
                $this->plugin_dir = WP_PLUGIN_DIR . '/' . $this->dir_name . '/';
                $this->plugin_url = plugins_url( '/', __FILE__ );
            } else {
                
                if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
                    $this->location = 'plugins';
                    $this->plugin_dir = WP_PLUGIN_DIR . '/';
                    $this->plugin_url = plugins_url( '/', __FILE__ );
                } else {
                    
                    if ( is_multisite() && defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
                        $this->location = 'mu-plugins';
                        $this->plugin_dir = WPMU_PLUGIN_DIR;
                        $this->plugin_url = WPMU_PLUGIN_URL;
                    } else {
                        wp_die( sprintf( __( 'There was an issue determining where %s is installed. Please reinstall it.', 'rsc' ), $this->title ) );
                    }
                
                }
            
            }
        
        }
        
        /**
         * Add link to Settings page on the plugins screen
         * @param  [type] $links
         * @param  [type] $file
         * @return [type]
         */
        function plugin_action_links( $links, $file )
        {
            $settings_link = '<a href = "' . admin_url( 'admin.php?page=restricted_content_settings' ) . '">' . __( 'Settings', 'rsc' ) . '</a>';
            array_unshift( $links, $settings_link );
            return $links;
        }
        
        /**
         * Plugin localization
         * @return [type]
         */
        function localization()
        {
            
            if ( $this->location == 'mu-plugins' ) {
                load_muplugin_textdomain( 'rsc', 'languages/' );
            } else {
                
                if ( $this->location == 'subfolder-plugins' ) {
                    load_plugin_textdomain( 'rsc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
                } else {
                    
                    if ( $this->location == 'plugins' ) {
                        load_plugin_textdomain( 'rsc', false, 'languages/' );
                    } else {
                    }
                
                }
            
            }
            
            $temp_locales = explode( '_', get_locale() );
            $this->language = ( $temp_locales[0] ? $temp_locales[0] : 'en' );
        }
        
        /**
         * Blocks POST content if needed
         * Calls RSC shortcode to check if the block is needed (check rsc_shortcode method)
         * @param  [type] $content content of a post
         * @return [type]
         */
        function maybe_block_content( $content )
        {
            global  $post ;
            if ( !is_admin() ) {
                //make sure that we restrict the content only on the front-end
                
                if ( isset( $post ) ) {
                    $content = apply_filters( 'rsc_maybe_block_content_post_before', $content, $post );
                    $rsc_content_availability = get_post_meta( $post->ID, '_rsc_content_availability', true );
                    if ( empty($rsc_content_availability) ) {
                        $rsc_content_availability = 'everyone';
                    }
                    $rsc_content_availability = apply_filters( 'rsc_content_availability', $rsc_content_availability, $post->ID );
                    
                    if ( $rsc_content_availability !== 'everyone' ) {
                        //Content shouldn't be available to everyone so we need to restrict it
                        $message = do_shortcode( '[RSC id="' . $post->ID . '" type="' . $rsc_content_availability . '"]' );
                        if ( $message ) {
                            $content = $message;
                        }
                    }
                    
                    $content = apply_filters( 'rsc_maybe_block_content_post_after', $content, $post );
                }
            
            }
            return $content;
        }
        
        /**
         * Get a user role from current user
         * @return [type]
         */
        public static function get_current_user_role()
        {
            
            if ( is_user_logged_in() ) {
                global  $current_user ;
                $user_role = $current_user->roles[0];
                return $user_role;
            }
            
            return false;
        }
        
        public static function admin_settings()
        {
            require_once plugin_dir_path( __FILE__ ) . 'includes/settings/settings.php';
        }
        
        function first_rc_menu_handler( $handler )
        {
            $handler = 'admin.php';
            return $handler;
        }
        
        public static function rsc_get_message( $type, $additional_arg = false )
        {
            $rsc_settings = get_option( 'rsc_settings' );
            switch ( $type ) {
                case 'logged_in':
                    //only logged in users should have access to the content
                    return apply_filters( 'rsc_logged_in_message', ( isset( $rsc_settings['logged_in_message'] ) ? esc_html( $rsc_settings['logged_in_message'] ) : esc_html( __( 'You must log in to view this content', 'rsc' ) ) ) );
                    break;
                case 'role':
                    return $user_role_message = apply_filters( 'rsc_role_message', ( isset( $rsc_settings['user_role_message'] ) ? esc_html( $rsc_settings['user_role_message'] ) : esc_html( __( 'You don\'t have required permissions to view this content.', 'rsc' ) ) ) );
                    break;
                case 'capability':
                    return apply_filters( 'rsc_capability_message', ( isset( $rsc_settings['capability_message'] ) ? esc_html( $rsc_settings['capability_message'] ) : esc_html( __( 'You don\'t have required permissions to view this content.', 'rsc' ) ) ) );
                    break;
                case 'author':
                    //only author of the post and the administrator should have access to the content
                    return apply_filters( 'rsc_author_message', ( isset( $rsc_settings['author_message'] ) ? esc_html( $rsc_settings['author_message'] ) : esc_html( __( 'This content is available only to it\'s author.', 'rsc' ) ) ) );
                    break;
                case 'tickera_anything':
                    return apply_filters( 'rsc_tickera_any_ticket_type_message', ( isset( $rsc_settings['tickera_any_ticket_type_message'] ) ? esc_html( $rsc_settings['tickera_any_ticket_type_message'] ) : esc_html( __( 'This content is restricted to the attendees only. Please purchase ticket(s) in order to access this content.', 'rsc' ) ) ) );
                    break;
                case 'tickera_event':
                    $rsc_tickera_users_event = $additional_arg;
                    $message = apply_filters( 'rsc_tickera_specific_event_message', ( isset( $rsc_settings['tickera_specific_event_message'] ) ? esc_html( $rsc_settings['tickera_specific_event_message'] ) : esc_html( __( 'Only attendees who purchased ticket(s) for following event(s): [rsc_tc_event] can access this content.', 'rsc' ) ) ) );
                    
                    if ( preg_match( '/[rsc_tc_event]/', $message ) ) {
                        //show event titles only if [rsc_tc_event] is used
                        $events_titles = array();
                        foreach ( $rsc_tickera_users_event as $rsc_tickera_users_event_key => $rsc_tickera_users_event_value ) {
                            $events_titles[] = get_the_title( $rsc_tickera_users_event_value );
                        }
                        $message = str_replace( '[rsc_tc_event]', implode( ', ', $events_titles ), $message );
                    }
                    
                    
                    if ( preg_match( '/[rsc_tc_event_links]/', $message ) ) {
                        //show event titles with links only if [rsc_tc_event_links] is used
                        $events_titles_links = array();
                        foreach ( $rsc_tickera_users_event as $rsc_tickera_users_event_key => $rsc_tickera_users_event_value ) {
                            $events_titles_links[] = '<a href="' . get_permalink( (int) $rsc_tickera_users_event_value ) . '">' . get_the_title( $rsc_tickera_users_event_value ) . '</a>';
                        }
                        $message = str_replace( '[rsc_tc_event_links]', implode( ', ', $events_titles_links ), $message );
                    }
                    
                    return $message;
                    break;
                case 'tickera_ticket_type':
                    $rsc_tickera_users_ticket_type = $additional_arg;
                    $message = apply_filters( 'rsc_tickera_specific_ticket_type_message', ( isset( $rsc_settings['tickera_specific_ticket_type_message'] ) ? esc_html( $rsc_settings['tickera_specific_ticket_type_message'] ) : esc_html( __( 'Only attendees who purchased following ticket type(s): [rsc_tc_ticket_type] can access this content.', 'rsc' ) ) ) );
                    
                    if ( preg_match( '/[rsc_tc_ticket_type]/', $message ) ) {
                        //show event titles only if [rsc_tc_event] is used
                        $ticket_types_titles = array();
                        foreach ( $rsc_tickera_users_ticket_type as $rsc_tickera_users_ticket_type_key => $rsc_tickera_users_ticket_type_value ) {
                            
                            if ( apply_filters( 'rsc_append_event_title_to_ticket_types_placeholder', true ) == true ) {
                                $event_id = Restricted_Content::get_meta_value(
                                    $rsc_tickera_users_ticket_type_value,
                                    'event_name',
                                    true,
                                    'post'
                                );
                                if ( empty($event_id) ) {
                                    $event_id = Restricted_Content::get_meta_value(
                                        $rsc_tickera_users_ticket_type_value,
                                        '_event_name',
                                        true,
                                        'post'
                                    );
                                }
                                $event_title = apply_filters(
                                    'rsc_event_title_ticket_types_placeholder',
                                    ' (' . get_the_title( $event_id ) . ' ' . __( 'event', 'rsc' ) . ')',
                                    $event_id,
                                    $rsc_tickera_users_ticket_type_value
                                );
                            } else {
                                $event_title = '';
                            }
                            
                            $ticket_types_titles[] = apply_filters( 'rsc_ticket_type_title_placeholder', get_the_title( $rsc_tickera_users_ticket_type_value ) . $event_title, $rsc_tickera_users_ticket_type_value );
                        }
                        $message = str_replace( '[rsc_tc_ticket_type]', implode( ', ', $ticket_types_titles ), $message );
                    }
                    
                    return $message;
                    break;
                case 'woo_anything':
                    $message = apply_filters( 'rsc_woo_any_product_message', ( isset( $rsc_settings['woo_any_product_message'] ) ? esc_html( $rsc_settings['woo_any_product_message'] ) : esc_html( __( 'This content is restricted to the clients only. Please purchase any product in order to access this content.', 'rsc' ) ) ) );
                    return $message;
                    break;
                case 'woo_product':
                    $rsc_woo_users_product = $additional_arg;
                    $message = apply_filters( 'rsc_woo_specific_product_message', ( isset( $rsc_settings['woo_specific_product_message'] ) ? esc_html( $rsc_settings['woo_specific_product_message'] ) : esc_html( __( 'Only clients who purchased following product(s): [rsc_woo_product] can access this content.', 'rsc' ) ) ) );
                    
                    if ( preg_match( '/[rsc_woo_product]/', $message ) ) {
                        //show product titles only if [rsc_woo_product] is used
                        $product_titles = array();
                        foreach ( $rsc_woo_users_product as $rsc_woo_users_product_key => $rsc_woo_users_product_value ) {
                            $product_titles[] = apply_filters( 'rsc_woo_product_title_title_placeholder', get_the_title( $rsc_woo_users_product_value ), $rsc_woo_users_product_value );
                        }
                        $message = str_replace( '[rsc_woo_product]', implode( ', ', $product_titles ), $message );
                    }
                    
                    
                    if ( preg_match( '/[rsc_woo_product_links]/', $message ) ) {
                        //show product title and links only if [rsc_woo_product_links] is used
                        $product_titles_links = array();
                        foreach ( $rsc_woo_users_product as $rsc_woo_users_product_key => $rsc_woo_users_product_value ) {
                            $product_titles_links[] = '<a href="' . get_permalink( (int) $rsc_woo_users_product_value ) . '">' . apply_filters( 'rsc_woo_product_title_title_placeholder', get_the_title( $rsc_woo_users_product_value ), $rsc_woo_users_product_value ) . '</a>';
                        }
                        $message = str_replace( '[rsc_woo_product_links]', implode( ', ', $product_titles_links ), $message );
                    }
                    
                    return $message;
                    break;
            }
        }
        
        /**
         * Restriction shortcode
         * Shows different messages based on restriction rule set
         * @param  [type] $atts [description]
         * @return [type]       [description]
         */
        function rsc_shortcode( $atts )
        {
            extract( shortcode_atts( array(
                'id'     => false,
                'cat_id' => false,
                'type'   => 'everyone',
            ), $atts ) );
            $widget = false;
            $widget_instance = false;
            $message = false;
            $allowed_to_admins_capability = apply_filters( 'rsc_allowed_to_admins_capability', 'manage_options' );
            
            if ( ($id || $cat_id) && ($type !== 'everyone' && !current_user_can( $allowed_to_admins_capability )) ) {
                $rsc_settings = get_option( 'rsc_settings' );
                
                if ( $cat_id ) {
                    $id = $cat_id;
                    $metabox_type = 'taxonomy';
                    $value_array = get_term_meta( $id );
                } else {
                    $metabox_type = 'post';
                    $value_array = apply_filters( 'rsc_get_post_value_array', get_post_meta( $id ), $id );
                }
                
                $value_array['id'] = $id;
                $altered_value = ( isset( $value_array['altered_value'] ) && $value_array['altered_value'] == true ? true : false );
                $can_access = Restricted_Content::can_access( $value_array );
                $type = ( isset( $value_array['_rsc_content_availability'] ) ? Restricted_Content::fix_value( $value_array['_rsc_content_availability'] ) : 'everyone' );
                switch ( $type ) {
                    case 'logged_in':
                        //only logged in users should have access to the content
                        
                        if ( !$can_access ) {
                            $message = Restricted_Content::rsc_get_message( $type );
                        } else {
                            $message = false;
                        }
                        
                        break;
                    case 'role':
                        //only specific user roles should have access to the content
                        
                        if ( $can_access ) {
                            $message = false;
                        } else {
                            $message = Restricted_Content::rsc_get_message( $type );
                        }
                        
                        break;
                    case 'capability':
                        //only users with specific capability should have access to the content
                        
                        if ( !$can_access ) {
                            $message = Restricted_Content::rsc_get_message( $type );
                        } else {
                            $message = false;
                        }
                        
                        break;
                    case 'author':
                        //content is available only to it's author and the administrators
                        
                        if ( !$can_access ) {
                            $message = Restricted_Content::rsc_get_message( $type );
                        } else {
                            $message = false;
                        }
                        
                        break;
                    case 'tickera':
                        //only Tickera users should have access to the content
                        $rsc_tickera_users = ( $altered_value ? $value_array['_rsc_tickera_users'] : Restricted_Content::get_meta_value(
                            $id,
                            '_rsc_tickera_users',
                            true,
                            $metabox_type,
                            $widget,
                            $widget_instance
                        ) );
                        switch ( $rsc_tickera_users ) {
                            case 'anything':
                                //at least one purchase of Tickera ticket is required for accessing the content
                                
                                if ( $can_access ) {
                                    $message = false;
                                } else {
                                    $message = Restricted_Content::rsc_get_message( $type . '_' . $rsc_tickera_users );
                                    //tickera_anything
                                }
                                
                                break;
                            case 'event':
                                //a purchase of at least one Tickera ticket type for a specific event is required to access the content
                                $rsc_tickera_users_event = ( $altered_value ? $value_array['_rsc_tickera_users_event'] : Restricted_Content::get_meta_value(
                                    $id,
                                    '_rsc_tickera_users_event',
                                    true,
                                    $metabox_type,
                                    $widget,
                                    $widget_instance
                                ) );
                                
                                if ( $can_access ) {
                                    $message = false;
                                } else {
                                    $message = Restricted_Content::rsc_get_message( $type . '_' . $rsc_tickera_users, $rsc_tickera_users_event );
                                }
                                
                                break;
                            case 'ticket_type':
                                //a purchase of a specific ticket type is required for accessing the content
                                $rsc_tickera_users_ticket_type = ( $altered_value ? $value_array['_rsc_tickera_users_ticket_type'] : Restricted_Content::get_meta_value(
                                    $id,
                                    '_rsc_tickera_users_ticket_type',
                                    true,
                                    $metabox_type,
                                    $widget,
                                    $widget_instance
                                ) );
                                
                                if ( $can_access ) {
                                    $message = false;
                                } else {
                                    $message = Restricted_Content::rsc_get_message( $type . '_' . $rsc_tickera_users, $rsc_tickera_users_ticket_type );
                                }
                                
                                break;
                        }
                        break;
                    case 'woo':
                        //only WooCommerce users should have access to the content
                        $rsc_woo_users = ( $altered_value ? $value_array['_rsc_woo_users'] : Restricted_Content::get_meta_value(
                            $id,
                            '_rsc_woo_users',
                            true,
                            $metabox_type,
                            $widget,
                            $widget_instance
                        ) );
                        switch ( $rsc_woo_users ) {
                            case 'anything':
                                //at least one purchase of any product is required for accessing the content
                                
                                if ( $can_access ) {
                                    $message = false;
                                } else {
                                    $message = Restricted_Content::rsc_get_message( $type . '_' . $rsc_woo_users );
                                }
                                
                                break;
                            case 'product':
                                //a purchase of a specific product is required for accessing the content
                                $rsc_woo_users_product = ( $altered_value ? $value_array['_rsc_woo_users_product'] : Restricted_Content::get_meta_value(
                                    $id,
                                    '_rsc_woo_users_product',
                                    true,
                                    $metabox_type,
                                    $widget,
                                    $widget_instance
                                ) );
                                
                                if ( $can_access ) {
                                    $message = false;
                                } else {
                                    $message = Restricted_Content::rsc_get_message( $type . '_' . $rsc_woo_users, $rsc_woo_users_product );
                                }
                                
                                break;
                        }
                        break;
                    default:
                        $message = false;
                }
            }
            
            if ( $message !== false && empty($message) ) {
                $message = ' ';
            }
            return ( !$message ? html_entity_decode( $message ) : '<div class="rsc_message">' . html_entity_decode( stripslashes( $message ) ) . '</div>' );
            //false means that user CAN access the content, otherwise a message will be shown (a reason why user can access content or who can access the content)
        }
        
        /**
         * Fixed value of a meta value since sometimes is a string
         * and sometimes first element of an array
         * @param  [type] $value [description]
         * @return [type]        [description]
         */
        public static function fix_value( $value )
        {
            return ( is_array( $value ) && isset( $value[0] ) ? $value[0] : $value );
        }
        
        public static function maybe_unserialize( $value )
        {
            $data = @unserialize( $value );
            
            if ( $data !== false ) {
                return $data;
            } else {
                return $value;
            }
        
        }
        
        /**
         * The "main" method - determin if the access is allowed or not based on restrictions / rules set
         * @param  [type] $value_array [description]
         * @return [type]              [description]
         */
        public static function can_access( $value_array )
        {
            
            if ( isset( $value_array['id'] ) ) {
                $id = $value_array['id'];
                $value_array = apply_filters( 'rsc_get_post_value_array', get_post_meta( $id ), $id );
            }
            
            $type = ( isset( $value_array['_rsc_content_availability'] ) ? Restricted_Content::fix_value( $value_array['_rsc_content_availability'] ) : 'everyone' );
            $rsc_settings = get_option( 'rsc_settings' );
            switch ( $type ) {
                case 'logged_in':
                    //only logged in users should have access to the content
                    
                    if ( !is_user_logged_in() ) {
                        return false;
                    } else {
                        return true;
                    }
                    
                    break;
                case 'logged_out':
                    //only logged out users / visitors - useful only for widgets and similar things
                    
                    if ( is_user_logged_in() ) {
                        return false;
                    } else {
                        return true;
                    }
                    
                    break;
                case 'author':
                    //only authors (and administrators) can access the content
                    $current_user_id = get_current_user_id();
                    
                    if ( isset( $value_array['id'] ) ) {
                        $post_author_id = get_post_field( 'post_author', (int) $value_array['id'] );
                        
                        if ( $post_author_id == $current_user_id ) {
                            return true;
                        } else {
                            return false;
                        }
                    
                    } else {
                        return false;
                    }
                    
                    break;
                case 'role':
                    //only specific user roles should have access to the content
                    $current_user_role = Restricted_Content::get_current_user_role();
                    
                    if ( $current_user_role ) {
                        $rsc_user_role = $value_array['_rsc_user_role'];
                        foreach ( $rsc_user_role as $key => $value ) {
                            $rsc_user_role[$key] = Restricted_Content::maybe_unserialize( $value );
                        }
                        if ( is_array( $rsc_user_role[0] ) ) {
                            $rsc_user_role = $rsc_user_role[0];
                        }
                        
                        if ( is_array( $rsc_user_role ) && in_array( $current_user_role, $rsc_user_role ) ) {
                            return true;
                        } else {
                            return false;
                        }
                    
                    } else {
                        return false;
                    }
                    
                    break;
                case 'capability':
                    //only users with specific capability should have access to the content
                    $required_capability = Restricted_Content::fix_value( $value_array['_rsc_capability'] );
                    
                    if ( !current_user_can( $required_capability ) ) {
                        return false;
                    } else {
                        return true;
                    }
                    
                    break;
                case 'tickera':
                    //only Tickera users should have access to the content
                    $rsc_tickera_users = Restricted_Content::fix_value( $value_array['_rsc_tickera_users'] );
                    switch ( $rsc_tickera_users ) {
                        case 'anything':
                            //at least one purchase of Tickera ticket is required for accessing the content
                            
                            if ( Restricted_Content::get_tickera_paid_user_orders_count() > 0 ) {
                                return true;
                            } else {
                                return false;
                            }
                            
                            break;
                        case 'event':
                            //a purchase of at least one Tickera ticket type for a specific event is required to access the content
                            $rsc_tickera_users_event = $value_array['_rsc_tickera_users_event'];
                            foreach ( $rsc_tickera_users_event as $key => $value ) {
                                $rsc_tickera_users_event[$key] = Restricted_Content::maybe_unserialize( $value );
                            }
                            if ( is_array( $rsc_tickera_users_event[0] ) ) {
                                $rsc_tickera_users_event = $rsc_tickera_users_event[0];
                            }
                            
                            if ( Restricted_Content::get_tickera_paid_user_orders_count( $rsc_tickera_users_event ) > 0 ) {
                                return true;
                            } else {
                                return false;
                            }
                            
                            break;
                        case 'ticket_type':
                            //a purchase of a specific ticket type is required for accessing the content
                            $rsc_tickera_users_ticket_type = $value_array['_rsc_tickera_users_ticket_type'];
                            foreach ( $rsc_tickera_users_ticket_type as $key => $value ) {
                                $rsc_tickera_users_ticket_type[$key] = Restricted_Content::maybe_unserialize( $value );
                            }
                            if ( is_array( $rsc_tickera_users_ticket_type[0] ) ) {
                                $rsc_tickera_users_ticket_type = $rsc_tickera_users_ticket_type[0];
                            }
                            
                            if ( Restricted_Content::get_tickera_paid_user_orders_count( false, $rsc_tickera_users_ticket_type ) > 0 ) {
                                return true;
                            } else {
                                return false;
                            }
                            
                            break;
                    }
                    break;
                case 'woo':
                    //only WooCommerce users should have access to the content
                    $rsc_woo_users = Restricted_Content::fix_value( $value_array['_rsc_woo_users'] );
                    switch ( $rsc_woo_users ) {
                        case 'anything':
                            //at least one purchase of any product is required for accessing the content
                            
                            if ( Restricted_Content::get_woo_paid_user_orders_count() > 0 ) {
                                return true;
                            } else {
                                return false;
                            }
                            
                            break;
                        case 'product':
                            //a purchase of a specific product is required for accessing the content
                            $rsc_woo_users_product = $value_array['_rsc_woo_users_product'];
                            foreach ( $rsc_woo_users_product as $key => $value ) {
                                $rsc_woo_users_product[$key] = Restricted_Content::maybe_unserialize( $value );
                            }
                            if ( is_array( $rsc_woo_users_product[0] ) ) {
                                $rsc_woo_users_product = $rsc_woo_users_product[0];
                            }
                            
                            if ( Restricted_Content::get_woo_paid_user_orders_count( false, $rsc_woo_users_product ) > 0 ) {
                                return true;
                            } else {
                                return false;
                            }
                            
                            break;
                    }
                    break;
                default:
                    //  echo($type);
                    return true;
            }
        }
        
        function rsc_admin_header()
        {
            //admin settings
            wp_enqueue_style(
                $this->name . '-chosen',
                $this->plugin_url . 'css/chosen.min.css',
                array(),
                $this->version
            );
            wp_enqueue_script(
                $this->name . '-chosen',
                $this->plugin_url . 'js/chosen.jquery.min.js',
                array( $this->name . '-admin' ),
                false,
                false
            );
            
            if ( isset( $_GET['page'] ) && $_GET['page'] == 'restricted_content_settings' ) {
                wp_enqueue_script(
                    'rsc-sticky',
                    $this->plugin_url . 'js/jquery.sticky.js',
                    array( 'jquery' ),
                    $this->version
                );
                wp_localize_script( $this->name . '-admin', 'rsc_vars', array(
                    'tc_check_page' => __( $_GET['page'] ),
                ) );
            }
        
        }
        
        //Add plugin admin menu items
        function rc_add_admin_menu()
        {
            global  $first_rsc_menu_handler ;
            $plugin_admin_menu_items = array(
                'settings' => __( 'Settings', 'rsc' ),
            );
            add_menu_page(
                $this->title,
                $this->title,
                'manage_options',
                'restricted_content_settings',
                'Restricted_Content::admin_settings',
                'dashicons-restrict',
                6
            );
            
            if ( $this->title == 'Restrict' ) {
                //Do not show addons for (assumed) white-labeled plugin
                //    $plugin_admin_menu_items['addons'] = __('Add-ons', 'rsc');
                //add_filter('rsc_fs_show_addons', '__return_true');
            } else {
                //add_filter('rsc_fs_show_addons', '__return_true');
            }
            
            $plugin_admin_menu_items = apply_filters( 'rc_plugin_admin_menu_items', $plugin_admin_menu_items );
            // Add the sub menu items
            $number_of_sub_menu_items = 0;
            $first_rsc_menu_handler = '';
            foreach ( $plugin_admin_menu_items as $handler => $value ) {
                
                if ( $number_of_sub_menu_items == 0 ) {
                    $first_rsc_menu_handler = apply_filters( 'first_rc_menu_handler', $this->name . '_' . $handler );
                    do_action( $this->name . '_add_menu_items_up' );
                } else {
                    
                    if ( $handler == 'addons' ) {
                        $capability = 'manage_options';
                    } else {
                        $capability = 'manage_' . $handler . '_cap';
                    }
                    
                    add_submenu_page(
                        $first_rsc_menu_handler,
                        $value,
                        $value,
                        $capability,
                        $this->name . '_' . $handler,
                        $this->name . '_' . $handler . '_admin'
                    );
                    do_action( $this->name . '_add_menu_items_after_' . $handler );
                }
                
                $number_of_sub_menu_items++;
            }
            do_action( $this->name . '_add_menu_items_down' );
        }
        
        public static function get_woo_paid_user_orders_count( $event_id = false, $product_id = false )
        {
            global  $wpdb ;
            $user_id = get_current_user_id();
            if ( $user_id == 0 ) {
                return 0;
            }
            
            if ( !$event_id && !$product_id ) {
                //overall paid orders
                $paid_orders_count = $wpdb->get_var( "SELECT COUNT(p.ID) FROM {$wpdb->posts} p, {$wpdb->postmeta} pm1, {$wpdb->postmeta} pm2 " . "                                         WHERE p.ID = pm1.post_id AND p.ID = pm2.post_id" . "                                         AND (p.post_status = 'wc-completed' OR p.post_status = 'wc-processing') " . "                                         AND p.post_type = 'shop_order'" . "                                         AND pm1.meta_key = '_customer_user' AND pm2.meta_value = '" . (int) $user_id . "'" );
                return (int) $paid_orders_count;
            }
            
            
            if ( !$event_id && $product_id ) {
                //paid orders for specific ticket type
                $current_user = wp_get_current_user();
                $user_email = $current_user->user_email;
                if ( is_array( $product_id ) ) {
                    //ticket type id is actually a list of ids / array (so we need to build a bit complicated query)
                    
                    if ( count( $product_id ) > 1 ) {
                        foreach ( $product_id as $product_id_key => $product_id_value ) {
                            if ( wc_customer_bought_product( $user_email, $user_id, $product_id_value ) ) {
                                return 1;
                            }
                        }
                        return 0;
                    } else {
                        //array contains only one element / ticket type id
                        if ( wc_customer_bought_product( $user_email, $user_id, $product_id[0] ) ) {
                            return 1;
                        }
                    }
                
                }
                return 0;
            }
        
        }
        
        /**
         * Retrieves count of paid orders
         * Overall, for a specific event, for a specific ticket type
         * @global type $wpdb
         * @param type $event_id
         * @param type $ticket_type_id
         * @return type
         */
        public static function get_tickera_paid_user_orders_count( $event_id = false, $ticket_type_id = false )
        {
            global  $wpdb ;
            $user_id = get_current_user_id();
            if ( $user_id == 0 ) {
                return 0;
            }
            
            if ( !$event_id && !$ticket_type_id ) {
                //overall paid orders
                
                if ( apply_filters( 'tc_is_woo', false ) == true ) {
                    //Tickera is in the Bridge mode
                    $paid_orders_count = $wpdb->get_var( "SELECT COUNT(p.ID) FROM {$wpdb->posts} p, {$wpdb->postmeta} pm1, {$wpdb->postmeta} pm2 " . "                                         WHERE p.ID = pm1.post_id AND p.ID = pm2.post_id" . "                                         AND (p.post_status = 'wc-completed' OR p.post_status = 'wc-processing') " . "                                         AND p.post_type = 'shop_order'" . "                                         AND pm1.meta_key = '_customer_user'" . "                                         AND pm1.meta_value = {$user_id}" . "                                         AND pm2.meta_key = 'tc_cart_info'" );
                } else {
                    $paid_orders_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_author = %d AND post_status = 'order_paid' AND post_type = 'tc_orders'", $user_id ) );
                }
                
                return $paid_orders_count;
            }
            
            
            if ( !$event_id && $ticket_type_id ) {
                //paid orders for specific ticket type
                $ticket_type_id_query_part = '';
                
                if ( is_array( $ticket_type_id ) ) {
                    //ticket type id is actually a list of ids / array (so we need to build a bit complicated query)
                    
                    if ( count( $ticket_type_id ) > 1 ) {
                        $ticket_type_ids_count = count( $ticket_type_id );
                        $ticket_type_id_query_part .= ' AND (';
                        $foreach_count = 1;
                        $extension = '';
                        foreach ( $ticket_type_id as $ticket_type_id_key => $ticket_type_id_value ) {
                            
                            if ( $ticket_type_ids_count == $foreach_count ) {
                                $extension = '';
                            } else {
                                $extension = ' OR ';
                            }
                            
                            $ticket_type_id_query_part .= " pm.meta_value  LIKE '%i:" . (int) $ticket_type_id_value . ";%' {$extension}";
                            $foreach_count++;
                        }
                        $ticket_type_id_query_part .= ') ';
                    } else {
                        //array contains only one element / ticket type id
                        $ticket_type_id_query_part = " AND pm.meta_value LIKE '%i:" . (int) $ticket_type_id[0] . ";%'";
                    }
                
                } else {
                    //argument is an integer (only one ticket type id)
                    $ticket_type_id_query_part = " AND pm.meta_value LIKE '%i:" . (int) $ticket_type_id . ";%'";
                }
                
                
                if ( apply_filters( 'tc_is_woo', false ) == false ) {
                    $paid_orders_count = $wpdb->get_var( "SELECT COUNT(p.ID) FROM {$wpdb->posts} p, {$wpdb->postmeta} pm\n          WHERE p.ID = pm.post_id\n          AND p.post_author = {$user_id}\n          AND p.post_status = 'order_paid'\n          AND p.post_type = 'tc_orders'\n          AND pm.meta_key = 'tc_cart_contents'\n          {$ticket_type_id_query_part}\n          " );
                    return $paid_orders_count;
                } else {
                    //Query for the Bridge for WooCommerce
                    $paid_orders_count = $wpdb->get_var( "SELECT COUNT(p.ID) FROM {$wpdb->posts} p, {$wpdb->postmeta} pm, {$wpdb->postmeta} pm2\n          WHERE p.ID = pm.post_id\n          AND p.ID = pm2.post_id\n\n          AND pm2.meta_key = '_customer_user'\n          AND pm2.meta_value = {$user_id}\n\n          AND (p.post_status = 'wc-completed' OR p.post_status = 'wc-processing')\n\n          AND p.post_type = 'shop_order'\n          AND pm.meta_key = 'tc_cart_contents'\n          {$ticket_type_id_query_part}\n          " );
                    return $paid_orders_count;
                }
            
            }
            
            
            if ( apply_filters( 'tc_is_woo', false ) == false ) {
                //This check doesn't work with the Bridge for WooCommerce because it would be very expensive task for the database server
                
                if ( $event_id && !$ticket_type_id ) {
                    //paid orders for specific event
                    $event_id_query_part = '';
                    
                    if ( is_array( $event_id ) ) {
                        //event id is actually a list of ids / array (so we need to build a bit complicated query)
                        
                        if ( count( $event_id ) > 1 ) {
                            $event_ids_count = count( $event_id );
                            $event_id_query_part .= ' AND (';
                            $foreach_count = 1;
                            $extension = '';
                            foreach ( $event_id as $event_id_key => $event_id_value ) {
                                
                                if ( $event_ids_count == $foreach_count ) {
                                    $extension = '';
                                } else {
                                    $extension = ' OR ';
                                }
                                
                                $event_id_query_part .= " pm.meta_value  LIKE '%\"" . (int) $event_id_value . "\"%' {$extension}";
                                $foreach_count++;
                            }
                            $event_id_query_part .= ') ';
                        } else {
                            //array contains only one element / event id
                            $event_id_query_part = " AND pm.meta_value LIKE '%\"" . (int) $event_id[0] . "\"%'";
                        }
                    
                    } else {
                        //argument is an integer (only one event id)
                        $event_id_query_part = " AND pm.meta_value LIKE '%\"" . (int) $event_id . "\"%'";
                    }
                    
                    $paid_orders_count = $wpdb->get_var( "SELECT COUNT(p.ID) FROM {$wpdb->posts} p, {$wpdb->postmeta} pm\n            WHERE p.ID = pm.post_id\n            AND p.post_author = {$user_id}\n            AND p.post_status = 'order_paid'\n            AND p.post_type = 'tc_orders'\n            AND pm.meta_key = 'tc_parent_event'\n            {$event_id_query_part}\n            " );
                }
            
            } else {
                $paid_orders_count = 0;
            }
            
            return (int) $paid_orders_count;
        }
        
        function rsc_show_tabs( $tab )
        {
            do_action( 'rc_show_page_tab_' . $tab );
            require_once $this->plugin_dir . 'includes/settings/settings-general.php';
        }
        
        public static function get_tickera_user_orders()
        {
            $user_id = get_current_user_id();
            $args = array(
                'author'         => $user_id,
                'posts_per_page' => -1,
                'post_type'      => 'tc_orders',
                'post_status'    => 'order_paid',
            );
            return get_posts( $args );
        }
        
        /**
         * Call admin scripts and styles
         * @global type $wp_version
         * @global type $post_type
         */
        function admin_header()
        {
            global  $wp_version, $post_type ;
            //Fix for Tickera builder editor button (because it can't work with multiple WP Editors)
            if ( isset( $_GET['page'] ) && $_GET['page'] == 'restricted_content_settings' ) {
                echo  '<style>
          .tc-shortcode-builder-button{
            display: none !important;
          }
          </style>' ;
            }
            //wp_enqueue_script($this->name . '-font-awesome', 'https://use.fontawesome.com/bec919b88b.js', array(), $this->version);
            wp_enqueue_style(
                $this->name . '-admin',
                $this->plugin_url . 'css/admin.css',
                array(),
                $this->version
            );
            wp_enqueue_style(
                $this->name . '-admin-jquery-ui',
                '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css',
                array(),
                $this->version
            );
            wp_enqueue_style(
                $this->name . '-chosen',
                $this->plugin_url . 'css/chosen.min.css',
                array(),
                $this->version
            );
            wp_enqueue_script(
                $this->name . '-admin',
                $this->plugin_url . 'js/admin.js',
                array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-core' ),
                $this->version,
                false
            );
            wp_localize_script( $this->name . '-admin', 'rsc_vars', array(
                'ajaxUrl' => apply_filters( 'rsc_ajaxurl', admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ) ),
            ) );
            wp_enqueue_script(
                $this->name . '-chosen',
                $this->plugin_url . 'js/chosen.jquery.min.js',
                array( $this->name . '-admin' ),
                false,
                false
            );
            wp_enqueue_style( 'rsc-roboto', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap' );
            wp_register_style( 'restrict_dashicons', $this->plugin_url . '/css/restrict.css' );
            wp_enqueue_style( 'restrict_dashicons' );
        }
        
        /**
         * Save metabox values on post save
         * @param type $post_id
         */
        function save_metabox_values( $post_id )
        {
            $metas = array();
            foreach ( $_POST as $field_name => $field_value ) {
                if ( preg_match( '/_rsc_post_meta/', $field_name ) ) {
                    $metas[sanitize_key( str_replace( '_rsc_post_meta', '', $field_name ) )] = rsc_sanitize_string( $field_value );
                }
                $metas = apply_filters( 'rsc_post_metas', $metas );
                if ( isset( $metas ) ) {
                    foreach ( $metas as $key => $value ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                }
            }
        }
        
        /**
         * Adds metabox for content availability
         */
        function add_metabox()
        {
            global  $post_type ;
            $is_comment = ( isset( $_GET['action'] ) && $_GET['action'] == 'editcomment' ? true : false );
            $rsc_skip_post_types = rsc_skip_post_types();
            //do not show restricted content meta fields for post types in the array
            
            if ( !in_array( $post_type, $rsc_skip_post_types ) && !$is_comment ) {
                $can_check_global_post_types = false;
                
                if ( $can_check_global_post_types == true ) {
                    $rsc_settings = get_option( 'rsc_settings' );
                    
                    if ( isset( $rsc_settings['post_type_' . $post_type . '_restricted'] ) && $rsc_settings['post_type_' . $post_type . '_restricted'] == 'yes' ) {
                        add_meta_box(
                            'rsc_metabox',
                            __( 'Content Restrictions', 'rsc' ),
                            array( $this, 'show_global_message_metabox' ),
                            null,
                            'normal',
                            'low'
                        );
                    } else {
                        add_meta_box(
                            'rsc_metabox',
                            __( 'Content Available To', 'rsc' ),
                            array( $this, 'show_metabox' ),
                            null,
                            'normal',
                            'low'
                        );
                    }
                
                } else {
                    add_meta_box(
                        'rsc_metabox',
                        __( 'Content Available To', 'rsc' ),
                        array( $this, 'show_metabox' ),
                        null,
                        'normal',
                        'low'
                    );
                }
            
            }
        
        }
        
        public static function fix_menu_item_name( $name = '' )
        {
            
            if ( strpos( $name, '[]' ) !== false ) {
                return "[" . str_replace( "[]", "", $name ) . "][]";
            } else {
                return "[" . $name . "]";
            }
        
        }
        
        public static function get_menu_item_field_name( $name, $widget, $widget_instance )
        {
            return "menu_item[" . $widget_instance . "]" . Restricted_Content::fix_menu_item_name( $name );
        }
        
        public static function get_post_type_field_name( $name, $widget, $widget_instance )
        {
            return "rsc_settings[" . $widget . "][" . $widget_instance . "]" . Restricted_Content::fix_menu_item_name( $name );
        }
        
        public static function get_field_name( $name = '', $widget = false, $widget_instance = false )
        {
            if ( $widget !== false ) {
                //return $name;
                
                if ( method_exists( $widget, 'get_field_name' ) ) {
                    $name = $widget->get_field_name( $name );
                } else {
                    
                    if ( $widget == 'post_type' ) {
                        $name = Restricted_Content::get_post_type_field_name( $name, $widget, $widget_instance );
                    } else {
                        $name = Restricted_Content::get_menu_item_field_name( $name, $widget, $widget_instance );
                    }
                
                }
            
            }
            return $name;
        }
        
        public static function get_meta_value(
            $id = false,
            $key = '',
            $single = true,
            $metabox_type = 'post',
            $widget = false,
            $widget_instance = false
        )
        {
            if ( $metabox_type == 'widget' ) {
                
                if ( is_array( $widget_instance ) || is_object( $widget_instance ) ) {
                    //widget
                    return ( isset( $widget_instance[$key] ) ? $widget_instance[$key] : '' );
                } else {
                    //menu item
                    return get_post_meta( $id, $key, $single );
                }
            
            }
            
            if ( $metabox_type == 'post_type' ) {
                $rsc_settings = get_option( 'rsc_settings', false );
                return ( isset( $rsc_settings[$metabox_type][$widget_instance][$key . '_rsc_post_meta'] ) ? $rsc_settings[$metabox_type][$widget_instance][$key . '_rsc_post_meta'] : '' );
            }
            
            if ( !is_string( $metabox_type ) ) {
                $metabox_type = 'post';
            }
            if ( isset( $_GET['tag_ID'] ) ) {
                $metabox_type = 'taxonomy';
            }
            if ( $metabox_type == 'post' ) {
                return get_post_meta( $id, $key, $single );
            }
            if ( $metabox_type == 'taxonomy' ) {
                return get_term_meta( $id, $key, $single );
            }
        }
        
        /**
         * Get all restriction options
         * @global type $tc
         * @return type
         */
        function get_restriction_options( $metabox_type, $widget = false, $widget_instance = false )
        {
            $restriction_options = array(
                'everyone'   => array( __( 'Everyone', 'rsc' ), false ),
                'logged_in'  => array( __( 'Logged in users', 'rsc' ), false ),
                'role'       => array( __( 'Users with specific role', 'rsc' ), array( 'Restricted_Content::get_sub_metabox', array(
                'role',
                $metabox_type,
                $widget,
                $widget_instance
            ) ) ),
                'capability' => array( __( 'Users with specific capability', 'rsc' ), array( 'Restricted_Content::get_sub_metabox', array(
                'capability',
                $metabox_type,
                $widget,
                $widget_instance
            ) ) ),
                'author'     => array( __( 'Author', 'rsc' ), array( 'Restricted_Content::get_sub_metabox', array(
                'author',
                $metabox_type,
                $widget,
                $widget_instance
            ) ) ),
            );
            global  $pagenow ;
            
            if ( $metabox_type == 'post' || isset( $_GET['post'] ) || isset( $_GET['post_type'] ) || isset( $pagenow ) && $pagenow == 'post-new.php' || isset( $_GET['tab'] ) && $_GET['tab'] == 'post_types' ) {
                //it's post / page / custom post type so we'll keep Author
            } else {
                unset( $restriction_options['author'] );
            }
            
            
            if ( class_exists( 'TC' ) ) {
                global  $tc ;
                $restriction_options['tickera'] = array( sprintf( __( '%s Users', 'rsc' ), $tc->title ), array( 'Restricted_Content::get_sub_metabox', array(
                    'tickera',
                    $metabox_type,
                    $widget,
                    $widget_instance
                ) ) );
            }
            
            
            if ( class_exists( 'WooCommerce' ) ) {
                global  $tc ;
                $restriction_options['woo'] = array( __( 'WooCommerce Users', 'rsc' ), array( 'Restricted_Content::get_sub_metabox', array(
                    'woo',
                    $metabox_type,
                    $widget,
                    $widget_instance
                ) ) );
            }
            
            return apply_filters(
                'rsc_restriction_options',
                $restriction_options,
                $widget,
                $widget_instance
            );
        }
        
        function show_global_message_metabox( $post, $metabox_type = 'post' )
        {
            echo  sprintf( __( 'The content is restricted by the global rules set %shere%s' ), '<a target="_blank" href="' . admin_url( 'admin.php?page=restricted_content_settings&tab=post_types' ) . '">', '</a>' ) ;
        }
        
        /**
         * Shows metabox
         * @param type $post
         */
        function show_metabox(
            $post,
            $metabox_type = 'post',
            $widget = false,
            $widget_instance = false
        )
        {
            //possible values: 'post' or 'taxonomy'
            $is_menu_item = false;
            $restriction_options = $this->get_restriction_options( $metabox_type, $widget, $widget_instance );
            $sub_metaboxes_functions = array();
            
            if ( is_array( $metabox_type ) || is_string( $metabox_type ) && $metabox_type == 'post' ) {
                $id = $post->ID;
                $metabox_type = 'post';
            } else {
                $id = ( isset( $_GET['tag_ID'] ) ? $_GET['tag_ID'] : false );
            }
            
            
            if ( $id === false ) {
                //for menu item
                $id = (int) $widget_instance;
                $is_menu_item = true;
            }
            
            
            if ( isset( $post ) || $is_menu_item ) {
                $rsc_content_availability = Restricted_Content::get_meta_value(
                    $id,
                    '_rsc_content_availability',
                    true,
                    $metabox_type,
                    $widget,
                    $widget_instance
                );
                if ( empty($rsc_content_availability) ) {
                    $rsc_content_availability = 'everyone';
                }
            }
            
            $restriction_options_select = '<select name="' . Restricted_Content::get_field_name( '_rsc_content_availability_rsc_post_meta', $widget, $widget_instance ) . '" class="rsc_content_availability">';
            foreach ( $restriction_options as $restriction_option_key => $restriction_option_values ) {
                $selected = ( $rsc_content_availability == $restriction_option_key ? 'selected' : '' );
                $restriction_options_select .= '<option value="' . esc_attr( $restriction_option_key ) . '" ' . $selected . '>' . $restriction_option_values[0] . '</option>';
                if ( $restriction_option_values[1][0] ) {
                    $sub_metaboxes_functions[] = array( $restriction_option_values[1][0], $restriction_option_values[1][1] );
                }
            }
            $restriction_options_select .= '</select>';
            echo  $restriction_options_select ;
            foreach ( $sub_metaboxes_functions as $sub_metaboxes_function_key => $sub_metaboxes_function_args ) {
                Restricted_Content::execute_function( $sub_metaboxes_function_args[0], $sub_metaboxes_function_args[1] );
            }
        }
        
        /**
         * Gets content for sub metaboxes
         * @global type $post
         * @global type $tc
         * @param type $type
         * @return type
         */
        public static function get_sub_metabox(
            $type = false,
            $metabox_type = 'post',
            $widget = false,
            $widget_instance = false
        )
        {
            if ( !$type ) {
                return;
            }
            global  $post ;
            
            if ( is_array( $metabox_type ) || is_string( $metabox_type ) && $metabox_type == 'post' ) {
                $metabox_type == 'post';
                $id = $post->ID;
            } else {
                $id = ( isset( $_GET['tag_ID'] ) ? $_GET['tag_ID'] : false );
            }
            
            
            if ( $id === false ) {
                //for menu item
                $id = (int) $widget_instance;
                $is_menu_item = true;
            }
            
            ?>
        <div class="rsc_sub_metabox rsc_sub_metabox_<?php 
            echo  esc_attr( $type ) ;
            ?> rsc_hide">
        <?php 
            switch ( $type ) {
                case 'role':
                    
                    if ( isset( $id ) ) {
                        $rsc_user_role = Restricted_Content::get_meta_value(
                            $id,
                            '_rsc_user_role',
                            true,
                            $metabox_type,
                            $widget,
                            $widget_instance
                        );
                        
                        if ( empty($rsc_user_role) ) {
                            $rsc_user_role_selected = 'administrator';
                        } else {
                            $rsc_user_role_selected = $rsc_user_role;
                        }
                    
                    }
                    
                    ?>
          <label><?php 
                    _e( 'Select a User Role', 'rsc' );
                    ?></label>
          <select name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_user_role_rsc_post_meta[]', $widget, $widget_instance ) ;
                    ?>" multiple="true">
            <?php 
                    $editable_roles = array_reverse( get_editable_roles() );
                    foreach ( $editable_roles as $role => $details ) {
                        $name = translate_user_role( $details['name'] );
                        ?>
              <option <?php 
                        echo  ( isset( $rsc_user_role_selected ) && is_array( $rsc_user_role_selected ) && in_array( $role, $rsc_user_role_selected ) ? 'selected' : '' ) ;
                        ?> value="<?php 
                        echo  esc_attr( $role ) ;
                        ?>"><?php 
                        echo  $name ;
                        ?></option>
              <?php 
                    }
                    ?>
          </select>
          <?php 
                    break;
                case 'capability':
                    $rsc_capability_rsc = Restricted_Content::get_meta_value(
                        $id,
                        '_rsc_capability',
                        true,
                        $metabox_type,
                        $widget,
                        $widget_instance
                    );
                    ?>
          <label><?php 
                    _e( 'User Capability', 'rsc' );
                    ?></label>
          <input type="text" name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_capability_rsc_post_meta', $widget, $widget_instance ) ;
                    ?>" value="<?php 
                    echo  ( isset( $rsc_capability_rsc ) ? esc_attr( $rsc_capability_rsc ) : '' ) ;
                    ?>" placeholder="manage_options" />
          <?php 
                    break;
                case 'tickera':
                    global  $tc ;
                    $rsc_tickera_users = Restricted_Content::get_meta_value(
                        $id,
                        '_rsc_tickera_users',
                        true,
                        $metabox_type,
                        $widget,
                        $widget_instance
                    );
                    if ( !isset( $rsc_tickera_users ) || empty($rsc_tickera_users) ) {
                        $rsc_tickera_users = 'anything';
                    }
                    ?>
          <label><?php 
                    _e( 'Who Purchased', 'rsc' );
                    ?></label>
          <input type="radio" name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_tickera_users_rsc_post_meta', $widget, $widget_instance ) ;
                    ?>" class="rsc_tickera_radio" value="anything" <?php 
                    checked( $rsc_tickera_users, 'anything', true );
                    ?> /> <?php 
                    _e( 'Any ticket type', 'rsc' );
                    ?><br />

          <?php 
                    
                    if ( apply_filters( 'tc_is_woo', false ) == false ) {
                        //Tickera is in the Bridge mode
                        ?>
            <input type="radio" name="<?php 
                        echo  Restricted_Content::get_field_name( '_rsc_tickera_users_rsc_post_meta', $widget, $widget_instance ) ;
                        ?>" class="rsc_tickera_radio" value="event" <?php 
                        checked( $rsc_tickera_users, 'event', true );
                        ?> /> <?php 
                        _e( 'Any ticket type for a specific event', 'rsc' );
                        ?><br />
            <?php 
                    }
                    
                    ?>
          <input type="radio" name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_tickera_users_rsc_post_meta', $widget, $widget_instance ) ;
                    ?>" class="rsc_tickera_radio" value="ticket_type" <?php 
                    checked( $rsc_tickera_users, 'ticket_type', true );
                    ?> /> <?php 
                    _e( 'Specific ticket type', 'rsc' );
                    ?><br />

          <div class="rsc_sub_sub rsc_tickera_event rsc_sub_hide rsc_sub_sub_metabox_event">
            <select name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_tickera_users_event_rsc_post_meta[]', $widget, $widget_instance ) ;
                    ?>" multiple>
              <?php 
                    $rsc_tickera_users_event = Restricted_Content::get_meta_value(
                        $id,
                        '_rsc_tickera_users_event',
                        true,
                        $metabox_type,
                        $widget,
                        $widget_instance
                    );
                    if ( !isset( $rsc_tickera_users_event ) || empty($rsc_tickera_users_event) ) {
                        $rsc_tickera_users_event = '';
                    }
                    $rsc_events = get_posts( array(
                        'post_type'      => 'tc_events',
                        'posts_per_page' => -1,
                    ) );
                    foreach ( $rsc_events as $event ) {
                        ?>
                <option value="<?php 
                        echo  (int) $event->ID ;
                        ?>" <?php 
                        echo  ( is_array( $rsc_tickera_users_event ) && in_array( $event->ID, $rsc_tickera_users_event ) ? 'selected' : '' ) ;
                        ?>><?php 
                        echo  $event->post_title ;
                        ?></option>
                <?php 
                    }
                    ?>
            </select>
          </div>

          <div class="rsc_sub_sub rsc_tickera_ticket_type rsc_sub_hide rsc_sub_sub_metabox_ticket_type">
            <select name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_tickera_users_ticket_type_rsc_post_meta[]', $widget, $widget_instance ) ;
                    ?>" multiple>
              <?php 
                    $rsc_tickera_users_ticket_type = Restricted_Content::get_meta_value(
                        $id,
                        '_rsc_tickera_users_ticket_type',
                        true,
                        $metabox_type,
                        $widget,
                        $widget_instance
                    );
                    if ( !isset( $rsc_tickera_users_ticket_type ) || empty($rsc_tickera_users_ticket_type) ) {
                        $rsc_tickera_users_ticket_type = '';
                    }
                    
                    if ( apply_filters( 'tc_is_woo', false ) == false ) {
                        //Tickera is in the Bridge mode
                        $rsc_ticket_types = get_posts( array(
                            'post_type'      => 'tc_tickets',
                            'posts_per_page' => -1,
                        ) );
                    } else {
                        $rsc_ticket_types = get_posts( array(
                            'post_type'      => 'product',
                            'posts_per_page' => -1,
                            'meta_key'       => '_event_name',
                        ) );
                    }
                    
                    foreach ( $rsc_ticket_types as $ticket_type ) {
                        $event_id = get_post_meta( $ticket_type->ID, apply_filters( 'tc_event_name_field_name', 'event_name' ), true );
                        $event_title = get_the_title( $event_id );
                        if ( empty($event_title) ) {
                            $event_title = sprintf( __( 'Event ID: %s', 'rsc' ), $event_id );
                        }
                        ?>
                <option value="<?php 
                        echo  (int) $ticket_type->ID ;
                        ?>" <?php 
                        echo  ( is_array( $rsc_tickera_users_ticket_type ) && in_array( $ticket_type->ID, $rsc_tickera_users_ticket_type ) ? 'selected' : '' ) ;
                        ?>><?php 
                        echo  $ticket_type->post_title . ' (' . $event_title . ')' ;
                        ?></option>
                <?php 
                    }
                    ?>
            </select>
          </div>
          <?php 
                    break;
                case 'woo':
                    global  $tc ;
                    $rsc_woo_users = Restricted_Content::get_meta_value(
                        $id,
                        '_rsc_woo_users',
                        true,
                        $metabox_type,
                        $widget,
                        $widget_instance
                    );
                    if ( !isset( $rsc_woo_users ) || empty($rsc_woo_users) ) {
                        $rsc_woo_users = 'anything';
                    }
                    ?>
          <label><?php 
                    _e( 'Who Purchased', 'rsc' );
                    ?></label>
          <input type="radio" name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_woo_users_rsc_post_meta', $widget, $widget_instance ) ;
                    ?>" class="rsc_woo_radio" value="anything" <?php 
                    checked( $rsc_woo_users, 'anything', true );
                    ?> /> <?php 
                    _e( 'Any product', 'rsc' );
                    ?><br />

          <input type="radio" name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_woo_users_rsc_post_meta', $widget, $widget_instance ) ;
                    ?>" class="rsc_woo_radio" value="product" <?php 
                    checked( $rsc_woo_users, 'product', true );
                    ?> /> <?php 
                    _e( 'Specific product', 'rsc' );
                    ?><br />

          <div class="rsc_sub_sub rsc_woo_product rsc_sub_hide rsc_sub_sub_metabox_product">
            <select name="<?php 
                    echo  Restricted_Content::get_field_name( '_rsc_woo_users_product_rsc_post_meta[]', $widget, $widget_instance ) ;
                    ?>" multiple>
              <?php 
                    $rsc_woo_users_product = Restricted_Content::get_meta_value(
                        $id,
                        '_rsc_woo_users_product',
                        true,
                        $metabox_type,
                        $widget,
                        $widget_instance
                    );
                    if ( !isset( $rsc_woo_users_product ) || empty($rsc_woo_users_product) ) {
                        $rsc_woo_users_product = '';
                    }
                    $woo_products = get_posts( array(
                        'post_type'      => 'product',
                        'posts_per_page' => -1,
                    ) );
                    foreach ( $woo_products as $product ) {
                        ?>
                <option value="<?php 
                        echo  (int) $product->ID ;
                        ?>" <?php 
                        echo  ( is_array( $rsc_woo_users_product ) && in_array( $product->ID, $rsc_woo_users_product ) ? 'selected' : '' ) ;
                        ?>><?php 
                        echo  $product->post_title ;
                        ?></option>
                <?php 
                    }
                    ?>
            </select>
          </div>
          <?php 
                    break;
            }
            ?>
      </div>
      <?php 
        }
        
        /**
         * Execute functions
         * Used in show_metabox method
         * @param type $function_name
         * @param type $args
         */
        public static function execute_function( $function_name = false, $args = array() )
        {
            call_user_func_array( $function_name, $args );
        }
    
    }
    $rsc = new Restricted_Content();
}
