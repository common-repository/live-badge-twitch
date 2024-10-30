<?php
/*
Plugin Name: Live Stream Badge
Plugin URI:
Description: Plugin to display a badge that shows if you are/not live in Twitch.
Author: Dan Cedric Arevalo
Author URI: https://www.dancedric.com
Version: 1.0
*/

add_action("admin_menu", "live_stream_badge_addMenu");
add_action( 'admin_init', 'live_stream_badge_register_settings' );
add_action( 'wp_enqueue_scripts', 'live_stream_badge_load_css' );

function live_stream_badge_addMenu() {
  add_menu_page("Live Stream Badge Settings", "Live Stream Badge Settings", 4, "twitch-live-badge-settings", "live_stream_badge_settings");
}

function live_stream_badge_register_settings() {
  register_setting('twitch-live-badge-setting-group', 'username');
  register_setting('twitch-live-badge-setting-group', 'client_id');
  register_setting('twitch-live-badge-setting-group', 'badge_online_text');
  register_setting('twitch-live-badge-setting-group', 'badge_online_color');
  register_setting('twitch-live-badge-setting-group', 'badge_offline_text');
  register_setting('twitch-live-badge-setting-group', 'badge_offline_color');
  register_setting('twitch-live-badge-setting-group', 'badge_width', array("default" => 'full'));
  register_setting('twitch-live-badge-setting-group', 'badge_text_alignment', array("default" => 'center'));
}

function live_stream_badge_settings() {
?>
    <h1>Live Stream Badge Settings</h1>
    <h2>Instructions</h2>
    <ol>
      <li>Go to <a href="https://dev.twitch.tv/" target="blank">https://dev.twitch.tv/</a> and log in.</li>
      <li>Go to your dashboard.</li>
      <li>Register Your Application.</li>
      <li>Under <b>Name</b>, call it: "WP Live Badge"</li>
      <li>For the <b>OAuth Redirect URLs</b>, provide the url of your website.</li>
      <li>For <b>Category</b>, choose <b>Website Integration</b>.</li>
      <li>Once you finish, you will be provided with a <b>Client ID</b> in that application. Provide that ID below.</li>
    </ol>

    <h2>Configuration</h2>
    <form method="post" action="options.php">
      <?php settings_fields( 'twitch-live-badge-setting-group' ); ?>
      <?php do_settings_sections( 'twitch-live-badge-setting-group' ); ?>
      <h3>Username (required)</h3>
      <input type="text" size="30" name="username" value="<?php echo esc_attr( get_option('username') ); ?>" required />
      <h3>Client ID (required)</h3>
      <input type="text" size="30" name="client_id" value="<?php echo esc_attr( get_option('client_id') ); ?>" required />
      <h3>Badge Online Text</h3>
      <input type="text" size="30" name="badge_online_text" placeholder="Live!" value="<?php echo esc_attr( get_option('badge_online_text','Live!') ); ?>" required/>
      <h3>Badge Online Color</h3>
      <input type="text" size="30" name="badge_online_color" value="<?php echo esc_attr( get_option('badge_online_color') ); ?>" placeholder="#FFFFFF or white" />
      <h3>Badge Offline Text</h3>
      <input type="text" size="30" name="badge_offline_text" placeholder="offline" value="<?php echo esc_attr( get_option('badge_offline_text','Offline') ); ?>" required />
      <h3>Badge Offline Color</h3>
      <input type="text" size="30" name="badge_offline_color" value="<?php echo esc_attr( get_option('badge_offline_color') ); ?>" placeholder="#FFFFFF or white" />
      <h3>Badge Width</h3>
      <select name="badge_width">
        <option value="wrap"  >Wrap</option>
        <option value="full"  >Full</option>
      </select>
      <h3>Badge Text Alignment</h3>
      <select name="badge_text_alignment">
        <option value="left" <?php if( esc_attr( get_option('badge_text_alignment') ) == 'left') echo 'selected="selected"'; ?> >Left</option>
        <option value="center" <?php if( esc_attr( get_option('badge_text_alignment') ) == 'center') echo 'selected="selected"'; ?> >Center</option>
        <option value="right" <?php if( esc_attr( get_option('badge_text_alignment') ) == 'right') echo 'selected="selected"'; ?> >Right</option>
      </select>
      <?php submit_button(); ?>
    </form>
<?php
}

// Register and load the widget
function live_stream_badge__load_widget() {
    register_widget( 'live_stream_badge_widget' );
}
add_action( 'widgets_init', 'live_stream_badge__load_widget' );

// Creating the widget
class live_stream_badge_widget extends WP_Widget {
  function __construct() {
  parent::__construct(

  // Base ID of your widget
  'live_stream_badge_widget',

  // Widget name will appear in UI
  __('Live Stream Badge', 'live_stream_badge_widget_domain'),

  // Widget description
  array( 'description' => __( 'Display a Twitch Live badge', 'live_stream_badge_widget_domain' ), )
  );
  }

  // Creating widget front-end

  public function widget( $args, $instance ) {
    $title = apply_filters( 'widget_title', $instance['title'] );

    // before and after widget arguments are defined by themes
    echo $args['before_widget'];
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];

    // This is where you run the code and display the output
    $username = esc_attr( get_option('username') );
    $channelsApi = 'https://api.twitch.tv/helix/streams?user_login='.$username;
    $clientId = esc_attr( get_option('client_id') );

    $response = wp_remote_get($channelsApi, array('headers' => array("Client-ID" => $clientId)) );

    $status = count( json_decode($response['body'])->data ) > 0 ? 'online' : 'offline';
    $href = "https://twitch.tv/$username";
    $badge_width = ( get_option('badge_width') !== '' ) ? get_option('badge_width') : 'full';
    $badge_text_alignment = ( get_option('badge_text_alignment') !== '' ) ? get_option('badge_text_alignment') : 'center';
    $badge_color = ($status == 'online') ? get_option('badge_online_color') : get_option('badge_offline_color');
    $badge_text = ($status == 'online' && get_option('badge_online_text') !== '' ) ? get_option('badge_online_text') : "Live!";
    $badge_text = ($status == 'offline' && get_option('badge_offline_text') !== '' ) ? get_option('badge_offline_text') : "Offline";
    if( $status == 'online' ) {
      if( get_option('badge_online_text') !== '' ) {
        $badge_text = get_option('badge_online_text');
      } else {
        $badge_text = "Live!";
      }
    }
    if( $status == 'offline' ) {
      if( get_option('badge_offline_text') !== '' ) {
        $badge_text = get_option('badge_offline_text');
      } else {
        $badge_text = "Offline";
      }
    }


    echo "<a href='$href' class='twitch-live-badge $status $badge_width' style='background-color: $badge_color; text-align: $badge_text_alignment; '>$badge_text</a>";

    echo $args['after_widget'];
  }

  // Widget Backend
  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) ) {
      $title = $instance[ 'title' ];
    }
    else {
      $title = __( 'New title', 'live_stream_badge_widget_domain' );
    }
    // Widget admin form
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
  }

  // Updating widget replacing old instances with new
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
  }
} // Class live_stream_badge_widget ends here


function live_stream_badge_load_css() {
  $plugin_url = plugin_dir_url( __FILE__ );
  wp_enqueue_style( 'style', $plugin_url . 'assets/css/style.css' );
}
?>
