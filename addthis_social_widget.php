<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/* 
* +--------------------------------------------------------------------------+
* | Copyright (c) 2008-2009 Add This, LLC                                    |
* +--------------------------------------------------------------------------+
* | This program is free software; you can redistribute it and/or modify     |
* | it under the terms of the GNU General Public License as published by     |
* | the Free Software Foundation; either version 2 of the License, or        |
* | (at your option) any later version.                                      |
* |                                                                          |
* | This program is distributed in the hope that it will be useful,          |
* | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
* | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
* | GNU General Public License for more details.                             |
* |                                                                          |
* | You should have received a copy of the GNU General Public License        |
* | along with this program; if not, write to the Free Software              |
* | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA |
* +--------------------------------------------------------------------------+
*/

if (!defined('ADDTHIS_INIT')) define('ADDTHIS_INIT', 1);
else return;

/**
* Plugin Name: AddThis Social Bookmarking Widget
* Plugin URI: http://www.addthis.com
* Description: Help your visitor promote your site! The AddThis Social Bookmarking Widget allows any visitor to bookmark your site easily with many popular services. Sign up for an AddThis.com account to see how your visitors are sharing your content--which services they're using for sharing, which content is shared the most, and more. It's all free--even the pretty charts and graphs.
* Version: 1.6.0
*
* Author: The AddThis Team
* Author URI: http://www.addthis.com
*/

$addthis_settings = array(array('isdropdown', 'true'),
                          array('customization', ''), 
                          array('menu_type', 'dropdown'), 
                          array('language', 'en'), 
                          array('username', ''), 
                          array('fallback_username', ''), 
                          array('style', 'share'));

$addthis_languages = array('zh'=>'Chinese', 'da'=>'Danish', 'nl'=>'Dutch', 'en'=>'English', 'fi'=>'Finnish', 'fr'=>'French', 'de'=>'German', 'he'=>'Hebrew', 'it'=>'Italian', 'ja'=>'Japanese', 'ko'=>'Korean', 'no'=>'Norwegian', 'pl'=>'Polish', 'pt'=>'Portugese', 'ru'=>'Russian', 'es'=>'Spanish', 'sv'=>'Swedish');

$addthis_menu_types = array('static', 'dropdown', 'toolbox');

$addthis_styles = array(
                      'share' => array('img'=>'lg-share-%lang%.gif', 'w'=>125, 'h'=>16),
                      'bookmark' => array('img'=>'lg-bookmark-en.gif', 'w'=>125, 'h'=>16),
                      'addthis' => array('img'=>'lg-addthis-en.gif', 'w'=>125, 'h'=>16),
                      'share-small' => array('img'=>'sm-share-%lang%.gif', 'w'=>83, 'h'=>16),
                      'bookmark-small' => array('img'=>'sm-bookmark-en.gif', 'w'=>83, 'h'=>16),
                      'plus' => array('img'=>'sm-plus.gif', 'w'=>16, 'h'=>16)
                      /* Add your own style here, like this:
                        , 'custom' => array('img'=>'http://example.com/button.gif', 'w'=>16, 'h'=>16) */
                    );

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

/**
* Generates unique IDs
*/
function cuid()
{
    $mt  = dechex(mt_rand(0,min(0xffffffff,mt_getrandmax())));
    $now = dechex(time());
    $cuid =  $now . str_pad($mt, 8, '0', STR_PAD_LEFT);
    return $cuid;
} 

/**
* Returns major.minor WordPress version.
*/
function get_wp_version() {
    return (float)substr(get_bloginfo('version'),0,3); 
}


/**
* Adds AddThis CSS to page. Only used for admin dashboard in WP 2.7 and higher.
*/
function addthis_print_style() {
    wp_enqueue_style( 'addthis' );
}

/**
* Adds AddThis script to page. Only used for admin dashboard in WP 2.7 and higher.
*/
function addthis_print_script() {
    wp_enqueue_script( 'addthis' );
}

/**
* Our admin dashboard widget shows yesterday's top shared content and top shared-to services.
* Data is fetched via AJAX. We assume jQuery is available on any WP install supporting 
* dashboard widgets.
*
* @see js/addthis.js
* @see js/addthis.css
*/
function addthis_render_dashboard_widget() {
    global $addthis_settings;
    $username = urlencode($addthis_settings['username']);
    $password = urlencode($addthis_settings['password']);

    echo <<<ENDHTML
        <p id="addthis_header" class="sub">Loading...</p>
        <div class="sub">
            <table id="addthis_tab_table" style="display:none">
                <colgroup><col width="25%"/><col width="25%"/><col width="50%"/></colgroup>
                <tr>
                    <td><a id="addthis_posts_tab" class="addthis-tab atb-active" href="#" onclick="return addthis_toggle_tabs(false)">Top Content</a></td>
                    <td><a id="addthis_services_tab" class="addthis-tab" href="#" onclick="return addthis_toggle_tabs(true)">Top Services</a></td>
                    <td style="text-align:right;"><a href="http://addthis.com/myaccount">View all stats &raquo;</a></td>
                </tr>
            </table>
        </div>

        <div class="table">
            <table id="addthis_data_posts_table" style="display:none">
                <colgroup><col width="90%"/><col width="10%"/></colgroup>
                <tbody id="addthis_data_posts">
                </tbody>
            </table>
            <table id="addthis_data_services_table" style="display:none">
                <colgroup><col width="40%"/><col width="10%"/><col width="40%"/><col width="10%"/></colgroup>
                <tbody id="addthis_data_services">
                </tbody>
            </table>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function(jQuery) {
            addthis_populate_posts_table("{$username}","{$password}", 5 /* max rows to show in table */, '#addthis_data_posts', '#addthis_header');
            addthis_populate_services_table("{$username}","{$password}", 10 /* max rows to show in table */, '#addthis_data_services', '#addthis_header');
        });
        </script>
ENDHTML;
} 

/**
* Initialize the dashboard widget.
*/
function addthis_dashboard_init() {
    wp_add_dashboard_widget('dashboard_addthis', 'AddThis', 'addthis_render_dashboard_widget');   
} 

/**
* Formally registers AddThis settings. Only called in WP 2.7+.
*/
function register_addthis_settings() {
    register_setting('addthis', 'addthis_username');
    register_setting('addthis', 'addthis_fallback_username');
    register_setting('addthis', 'addthis_password');
    register_setting('addthis', 'addthis_show_stats');
    register_setting('addthis', 'addthis_style');
    register_setting('addthis', 'addthis_sidebar_only');
    register_setting('addthis', 'addthis_isdropdown');
    register_setting('addthis', 'addthis_menu_type');
    register_setting('addthis', 'addthis_showonpages');
    register_setting('addthis', 'addthis_showoncats');
    register_setting('addthis', 'addthis_showonhome');
    register_setting('addthis', 'addthis_showonarchives');
    register_setting('addthis', 'addthis_language');
    register_setting('addthis', 'addthis_brand');
    register_setting('addthis', 'addthis_options');
    register_setting('addthis', 'addthis_header_background');
    register_setting('addthis', 'addthis_header_color');
}

/**
* Adds WP filter so we can append the AddThis button to post content.
*/
function addthis_init($username=null, $style=null)
{
    global $addthis_settings;

    if (get_wp_version() >= 2.7) {
        if ( is_admin() ) {
            add_action( 'admin_init', 'register_addthis_settings' );
        }
    }

    if (function_exists('wp_register_style')) {
        wp_register_style( 'addthis', WP_PLUGIN_URL . '/addthis/css/addthis.css');
        wp_register_script( 'addthis', WP_PLUGIN_URL . '/addthis/js/addthis.js');
    
        add_action('admin_print_styles', 'addthis_print_style');
        add_action('admin_print_scripts', 'addthis_print_script');
    }

    add_filter('the_content', 'addthis_social_widget');
    add_filter('admin_menu', 'addthis_admin_menu');

    add_option('addthis_username');
    add_option('addthis_show_stats', 'false');
    add_option('addthis_password');
    add_option('addthis_fallback_username', 'wp-'.cuid());
    add_option('addthis_options'); // no default value, so that we can update as times change
    add_option('addthis_product', 'menu');
    add_option('addthis_isdropdown', 'true');
    add_option('addthis_menu_type', (get_option('addthis_isdropdown') !== 'true' ? 'static' : 'dropdown'));
    add_option('addthis_showonhome', 'true');
    add_option('addthis_showonpages', 'false');
    add_option('addthis_showoncats', 'false');
    add_option('addthis_showonarchives', 'false');
    add_option('addthis_style');
    add_option('addthis_header_background');
    add_option('addthis_header_color');
    add_option('addthis_sidebar_only', 'false');
    add_option('addthis_brand');
    add_option('addthis_language', 'en');

    $addthis_settings['sidebar_only'] = get_option('addthis_sidebar_only') === 'true';
    $addthis_settings['showstats'] = get_option('addthis_show_stats') === 'true';
    $addthis_settings['showonhome'] = !(get_option('addthis_showonhome') !== 'true');
    $addthis_settings['showonpages'] = get_option('addthis_showonpages') === 'true';
    $addthis_settings['showonarchives'] = get_option('addthis_showonarchives') === 'true';
    $addthis_settings['showoncats'] = get_option('addthis_showoncats') === 'true';
    
    $product = get_option('addthis_product');


    if (!isset($style)) $style = get_option('addthis_style');
    if (strlen($style) == 0) $style = 'share';
    $addthis_settings['style'] = $style;

    $addthis_settings['menu_type'] = get_option('addthis_menu_type');

    if (!isset($username)) $username = get_option('addthis_username');
    $addthis_settings['username'] = $username;
    $addthis_settings['fallback_username'] = get_option('addthis_fallback_username');

    $addthis_settings['password'] = get_option('addthis_password');

    $language = get_option('addthis_language');
    $addthis_settings['language'] = $language;

    $advopts = array('brand', 'language', 'header_background', 'header_color');
    $addthis_settings['customization'] = '';
    for ($i = 0; $i < count($advopts); $i++)
    {
        $opt = $advopts[$i];
        $val = get_option("addthis_$opt");
        if (isset($val) && strlen($val)) $addthis_settings['customization'] .= "var addthis_$opt = '$val';";
    }
    $addthis_options = get_option('addthis_options');
    if (strlen($addthis_options) == 0) $addthis_options = 'email,favorites,print,delicious,digg,google,myspace,live,facebook,stumbleupon,twitter,more';
    $addthis_settings['options'] = $addthis_options;

    add_action('widgets_init', 'addthis_widget_init');

    if (get_wp_version() >= 2.7) {
        add_action('wp_dashboard_setup', 'addthis_dashboard_init' );
    }
}

function addthis_widget_init()
{
    if ( function_exists('register_sidebar_widget') )
        register_sidebar_widget('AddThis Widget', 'addthis_sidebar_widget');
}

function addthis_sidebar_widget($args) 
{
    extract($args);
    echo $before_widget; 
    echo $before_title . $after_title . addthis_social_widget('', true);
    echo $after_widget;
}

/**
* Appends AddThis button to post content.
*/
function addthis_social_widget($content, $sidebar = false)
{
    global $addthis_settings;

    // add nothing to RSS feed or search results; control adding to static/archive/category pages
    if (!$sidebar) 
    {
        if ($addthis_settings['sidebar_only'] == 'true') return $content;
        else if (is_feed()) return $content;
        else if (is_search()) return $content;
        else if (is_home() && !$addthis_settings['showonhome']) return $content;
        else if (is_page() && !$addthis_settings['showonpages']) return $content;
        else if (is_archive() && !$addthis_settings['showonarchives']) return $content;
        else if (is_category() && !$addthis_settings['showoncats']) return $content;
    }

    $pub = urlencode($addthis_settings['username']);
    if (!isset($pub)) {
        $pub = $addthis_settings['fallback_username'];
    }
    $link  = $sidebar ? '[URL]' : urlencode(get_permalink());
    $title = $sidebar ? '[TITLE]' : urlencode(the_title());
    $addthis_options = $addthis_settings['options'];

    $content .= "\n<!-- AddThis Button BEGIN -->\n";
    if (strlen($addthis_settings['customization'])) 
    {
        $content .= '<script type="text/javascript">' . ($addthis_settings['customization']);
    }

    if ($addthis_settings['menu_type'] === 'dropdown')
    {
        $content .= "var_addthis_options = '$addthis_options';\n</script>\n";
        $content .= <<<EOF
<div class="addthis_container"><a href="http://www.addthis.com/bookmark.php?v=250&amp;pub=$pub" class="addthis_button" addthis:url="$link" addthis:title="$title">
EOF;
        $content .= addthis_get_button_img() . '</a><script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pub='.$pub.'"></script></div>';
    }
    else if ($addthis_settings['menu_type'] === 'toolbox')
    {
        $content .= "\n</script>\n";
        $content .= <<<EOF
<div class="addthis_container addthis_toolbox addthis_default_style" addthis:url="$link" addthis:title="$title"><a href="http://www.addthis.com/bookmark.php?v=250&amp;pub=$pub" class="addthis_button_compact">Share</a><span class="addthis_separator">|</span>
EOF;
        $addthis_options = split(',', $addthis_options);
        foreach ($addthis_options as $option) {
           $option = trim($option);  
            if ($option != 'more') {
                $content .= '<a class="addthis_button_'.$option.'"></a>';
            }
        }
        $content .= '<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pub='.$pub.'"></script></div>';
    }
    else
    {
        $content .= <<<EOF
<div class="addthis_container"><a href="http://www.addthis.com/bookmark.php?v=250&amp;pub=$pub" onclick="window.open('http://www.addthis.com/bookmark.php?v=250&pub=$pub&amp;url=$link&amp;title=$title', 'ext_addthis', 'scrollbars=yes,menubar=no,width=620,height=520,resizable=yes,toolbar=no,location=no,status=no'); return false;" title="Bookmark using any bookmark manager!" target="_blank">
EOF;
        $content .= addthis_get_button_img() . '</a></div>';
    }
    $content .= "\n<!-- AddThis Button END -->";
    return $content;
}

/**
* Generates img tag for share/bookmark button.
*/
function addthis_get_button_img()
{
    global $addthis_settings;
    global $addthis_styles;

    $btnStyle = $addthis_settings['style'];
    if ($addthis_settings['language'] != 'en')
    {
        // We use a translation of the word 'share' for all verbal buttons
        switch ($btnStyle)
        {   
            case 'bookmark':
            case 'addthis':
            case 'bookmark-sm':
                $btnStyle = 'share';
        }
    }

    if (!isset($addthis_styles[$btnStyle])) $btnStyle = 'share';
    $btnRecord = $addthis_styles[$btnStyle];
    $btnUrl = (strpos(trim($btnRecord['img']), 'http://') !== 0 ? "http://s7.addthis.com/static/btn/v2/" : "") . $btnRecord['img'];
         
    if (strpos($btnUrl, '%lang%') !== false)
    {
        $btnUrl = str_replace('%lang%',$addthis_settings['language'], $btnUrl);
    }
    $btnWidth = $btnRecord['w'];
    $btnHeight = $btnRecord['h'];
    return <<<EOF
<img src="$btnUrl" width="$btnWidth" height="$btnHeight" style="border:0" alt="Bookmark and Share"/>
EOF;
}

function addthis_admin_menu()
{
    add_options_page('AddThis Plugin Options', 'AddThis', 8, __FILE__, 'addthis_plugin_options_php4');
}

function addthis_plugin_options_php4() {
    global $addthis_styles;
    global $addthis_languages;
    global $addthis_settings;
    global $addthis_menu_types;

?>
    <div class="wrap">
    <h2>AddThis</h2>

    <form method="post" action="options.php">
    <?php 
        // use the old-school settings style in older versions of wordpress
        if (get_wp_version() < 2.7) {
            wp_nonce_field('update-options');
        } else {
            settings_fields('addthis'); 
        }
    ?>

    <h3>Required</h3>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e("AddThis username:", 'addthis_trans_domain' ); ?></th>
            <td><input type="text" name="addthis_username" value="<?php echo get_option('addthis_username'); ?>" /></td>
        </tr>
        <tr>
            <th scope="row"><?php _e("Menu type:", 'addthis_trans_domain' ); ?></th>
            <td>
                <select name="addthis_menu_type" onchange="document.getElementById('at_buttonstyle').style.display = (this.value !== 'toolbox');">
                <?php
                    $curmenutype = get_option('addthis_menu_type');
                    foreach ($addthis_menu_types as $type)
                    {
                        echo "<option value=\"$type\"". ($type == $curmenutype ? " selected":""). ">$type</option>";
                    }
                ?>
                </select>
        </tr>
        <tr valign="top" id="at_buttonstyle" style="display:<?php echo ($curmenutype != 'toolbox') ? 'block' : 'none'; ?> ?>">
            <th scope="row"><?php _e("Button style:", 'addthis_trans_domain' ); ?></th>
            <td>
                <select name="addthis_style">
                <?php
                    $curstyle = get_option('addthis_style');
                    foreach ($addthis_styles as $style => $info)
                    {
                        echo "<option value=\"$style\"". ($style == $curstyle ? " selected":""). ">$style</option>";
                    }
                ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e("Show in sidebar only:", 'addthis_trans_domain' ); ?></th>
            <td><input type="checkbox" name="addthis_sidebar_only" value="true" <?php echo (get_option('addthis_sidebar_only') == 'true' ? 'checked' : ''); ?>/></td>
        </tr>
    </table>
    
    <br />
    <br />
    <br />

    <h3>Advanced</h3>
    <table class="form-table">
        <?php
        // We can only support the dashboard widget for WordPress 2.7+
        if (get_wp_version() >= 2.7) {
        ?>
        <tr>
            <th scope="row"><?php _e("Show stats in admin dashboard:", 'addthis_trans_domain' ); ?></th>
            <td><input type="checkbox" name="addthis_show_stats" value="true" onchange="document.getElementById('password_row').style.display = (this.checked ? '' : 'none');" <?php echo (get_option('addthis_show_stats') !== '' ? 'checked' : ''); ?>/></td>
        </tr>
        <tr id="password_row" style="<?php echo (get_option('addthis_show_stats') !== '' ? '' : 'display:none'); ?>">
            <th scope="row"><?php _e("AddThis password:", 'addthis_trans_domain' ); ?><br/><span style="font-size:10px">(required for displaying stats)</span></th>
            <td><input type="password" name="addthis_password" value="<?php echo get_option('addthis_password'); ?>"/></td>
        </tr>
        <?php
        }
        ?>
        <tr>
            <th scope="row"><?php _e("Show on homepage:", 'addthis_trans_domain' ); ?></th>
            <td><input type="checkbox" name="addthis_showonhome" value="true" <?php echo (get_option('addthis_showonhome') == 'true' ? 'checked' : ''); ?>/></td>
        </tr>
        <tr>
            <th scope="row"><?php _e("Show on <a href=\"http://codex.wordpress.org/Pages\" target=\"blank\">pages</a>:", 'addthis_trans_domain' ); ?></th>
            <td><input type="checkbox" name="addthis_showonpages" value="true" <?php echo (get_option('addthis_showonpages') !== '' ? 'checked' : ''); ?>/></td>
        </tr>
        <tr>
            <th scope="row"><?php _e("Show on homepage:", 'addthis_trans_domain' ); ?></th>
            <td><input type="checkbox" name="addthis_showonhome" value="true" <?php echo (get_option('addthis_showonhome') == 'true' ? 'checked' : ''); ?>/></td>
        </tr>
        <tr>
            <th scope="row"><?php _e("Show in archives:", 'addthis_trans_domain' ); ?></th>
            <td><input type="checkbox" name="addthis_showonarchives" value="true" <?php echo (get_option('addthis_showonarchives') == 'true' ? 'checked' : ''); ?>/></td>
        </tr>
        <tr>
            <th scope="row"><?php _e("Show in categories:", 'addthis_trans_domain' ); ?></th>
            <td><input type="checkbox" name="addthis_showoncats" value="true" <?php echo (get_option('addthis_showoncats') == 'true' ? 'checked' : ''); ?>/></td>
        </tr>
        <tr valign="top">
            <td colspan="2"></td>
        </tr>
        <tr valign="top">
            <td colspan="2">For more details on the following options, see <a href="http://addthis.com/customization">our customization docs</a>.</td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e("Brand:", 'addthis_trans_domain' ); ?></th>
            <td><input type="text" name="addthis_brand" value="<?php echo get_option('addthis_brand'); ?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e("Dropdown/Toolbox Services:", 'addthis_trans_domain'); ?> <br/> <span style="font-size:10px">(comma-separated)</span></th>
            <td><input type="text" name="addthis_options" value="<?php echo get_option('addthis_options'); ?>" size="80"/></td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e("Language:", 'addthis_trans_domain' ); ?></th>
            <td>
                <select name="addthis_language">
                <?php
                    $curlng = get_option('addthis_language');
                    foreach ($addthis_languages as $lng=>$name)
                    {
                        echo "<option value=\"$lng\"". ($lng == $curlng ? " selected":""). ">$name</option>";
                    }
                ?>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e("Header background:", 'addthis_trans_domain' ); ?></th>
            <td><input type="text" name="addthis_header_background" value="<?php echo get_option('addthis_header_background'); ?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e("Header color:", 'addthis_trans_domain' ); ?></th>
            <td><input type="text" name="addthis_header_color" value="<?php echo get_option('addthis_header_color'); ?>" /></td>
        </tr>
    </table>

    <?php 
        // use the old-school settings style in older versions of wordpress
        if (get_wp_version() < 2.7) {
    ?>
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="addthis_username,addthis_password,addthis_show_stats,addthis_style,addthis_sidebar_only,addthis_menu_type,addthis_showonpages,addthis_showoncats,addthis_showonhome,addthis_showonarchives,addthis_language,addthis_brand,addthis_options,addthis_header_background,addthis_header_color"/>
    <?php 
        }
    ?>
    <p class="submit">
    <input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
    </p>

    </form>
    </div>
<?php
}

addthis_init();
?>
