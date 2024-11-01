<?php

/* wp-content/plugins/wp-ban-user/wp-ban-user.php */

/*
Plugin Name: WP-Ban-User
Plugin URI: http://wpban.cz.cc/demo/
Description: Ban user from making comment.
Version: 1.0
Author: blueskyy
Author URI: http://wpban.cz.cc/
License: GPLv3
*/

register_activation_hook(__FILE__, 'wpbu_install');

function wpbu_install() 
{
    global $wpdb;
    $table_name = $wpdb->prefix . "users_banned";
   
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) 
    {
        $sql = "CREATE TABLE " . $table_name . " (
                    id bigint(20) unsigned not null auto_increment,
                    user_type varchar(10) not null default 'public',
                    user_name varchar(60) not null,
                    admin_name varchar(60) not null,
	                admin_notes text null,
                    PRIMARY KEY (id),
	                UNIQUE (user_name, user_type)
	           );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// load language file
function wpbu_setup() 
{
	$domain = "wpbu";
	$plugin_dir = str_replace( basename(__FILE__) , "" , plugin_basename(__FILE__) );
	load_plugin_textdomain( $domain, "wp-content/plugins/" . $plugin_dir , $plugin_dir );
    
    if (function_exists("wp_enqueue_script")) 
    {
        wp_enqueue_script("sortable", get_bloginfo("wpurl") . "/wp-content/plugins/wp-ban-user/js/sortable.js");
    }  
}

function wpbu_html_header()
{
    wpbu_add_style();
    wpbu_add_script();
}

function wpbu_add_script()
{
?>
<script type="text/javascript">
<!--
    var image_path = "<?php echo get_bloginfo("wpurl") . "/wp-content/plugins/wp-ban-user/js/" ?>";
-->
</script>
<?php
}

function wpbu_add_style() 
{
?>
<style type="text/css">
table.wpbu-nt td.user-name {
    vertical-align: middle;
    width: 15%;
    min-width: 100px;    
}

table.wpbu-nt td.admin-name {
    vertical-align: middle;
    width: 15%;
    min-width: 100px;
}

table.wpbu-nt td.user-ip {
    vertical-align: middle;
    width: 20%;
}

table.wpbu-nt td.admin-notes {
    width: auto;
}

table.wpbu-nt td.lastcol {
    vertical-align: middle;
    width: 10%;
    border-left-width: 0;
}

table.wpbu-nt td {
    width: 10%;
    border-right: 1px solid #DFDFDF;
}

table.wpbu-nt span.sortheader {
    color: #21759B;
}

table.wpbu-ft th {
    padding: 5px 10px;
}

table.wpbu-ft td {
    padding: 5px 10px;
}

h3.wpbu {
    margin: 1em 0 0.5em 0;
}

fieldset.wpbu {
    -moz-border-radius: 4px 4px 4px 4px;
    border: 1px solid #DFDFDF;
    padding-bottom: 15px;
    width: 80%;    
}
</style>
<?php
}

// set up the option page
function wpbu_add_menu() 
{
	add_options_page( __("Ban User", "wpbu"), __("Ban User", "wpbu"), 8, __FILE__, "wpbu_config_page");
}

// for now admin has to input the name manually
// in later version, admin can "ban" user from the comment page or the admin comment panel 
// the configuration page
function wpbu_config_page() 
{
    if (isset($_POST["submit"]))
    {
        wpbu_process_form();
    }
    
    global $wpdb;
    
    $table_banned = $wpdb->prefix . "users_banned";
    $table_users = $wpdb->prefix . "users";
        
?>      <div class="wrap">
        <h2>Users banned from making comment.</h2>
        <br />                
<?php
        $sql = "select banned.id as id, banned.user_type as user_type, banned.user_name as user_name, 
            banned.admin_name as admin_name, banned.admin_notes as admin_notes, users.id as alien_entry 
            from " . $table_banned . " as banned left join " . $table_users . " as users 
            on banned.user_name=users.user_login where banned.user_type='private';";
             
        $registered_users = $wpdb->get_results($sql);

        $sql = "select banned.id as id, banned.user_type as user_type, banned.user_name as user_name, 
            banned.admin_name as admin_name, banned.admin_notes as admin_notes, users.id as alien_entry 
            from " . $table_banned . " as banned left join " . $table_users . " as users 
            on banned.user_name=users.user_login where banned.user_type='public';";
            
        $general_users = $wpdb->get_results($sql);
      
        if (((!isset($registered_users)) || (count($registered_users) == 0)) && ((!isset($general_users)) || (count($general_users) == 0)))
        {
?>          <table class="widefat wpbu-nt">
                <tbody>
                    <tr><th>List is empty, no one has been banned.</th><tr>
                </tbody>
            </table>             
<?php   }

        else
        {
            if (count($registered_users) > 0)
            {
?>              <h3 class="wpbu">Registered Users:</h3>
                <table border="1" id="sortable-t1" class="widefat wpbu-nt sortable">
                    <thead>                
                        <tr><th id="default">User Name</th><th class="unsortable">User IP</th><th>Admin Name</th><th class="unsortable">Admin Remarks</th><th class="unsortable"></th></tr>  
                    </thead>
                    
                    <tbody>                        
<?php                   foreach ($registered_users as $registered_user) 
                        {
?>                          <tr>                     
                                <td class="user-name"><?php echo "$registered_user->user_name"; ?></td> 
                                <td class="user-ip"><?php echo wpbu_get_user_ips($registered_user->user_name, "private"); ?></td>                         
                                <td class="admin-name"><?php echo "$registered_user->admin_name"; ?></td>
                                <td class="admin-notes"><?php echo "$registered_user->admin_notes"; ?></td>
                                                       
                                <td class="lastcol">
                                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo plugin_basename(__FILE__) ?>">
                                        <input type="hidden" name="mode" value="unban" />                        
                                        <input type="hidden" name="id" value="<?php echo "$registered_user->id"; ?>" />
                                        <input type="submit" name="submit" value="Remove Ban" style="float:right;" />
                                    </form>
                                </td>
                            </tr>        
<?php                   }
?>                  </tbody>
                </table>                    
<?php       }

            if (count($general_users) > 0)
            {
?>              <h3 class="wpbu">General Users:</h3>
                <table id="sortable-t2" class="widefat wpbu-nt sortable">
                    <thead>                
                        <tr><th id="default">User Name</th><th class="unsortable">User IP</th><th>Admin Name</th><th class="unsortable">Admin Remarks</th><th></th></tr>  
                    </thead>
                    
                    <tbody>                        
<?php                   foreach ($general_users as $general_user) 
                        {
?>                          <tr>                     
                                <td class="user-name"><?php echo "$general_user->user_name"; ?></td> 
                                <td class="user-ip"><?php echo wpbu_get_user_ips($general_user->user_name, "public"); ?></td>                         
                                <td class="admin-name"><?php echo "$general_user->admin_name"; ?></td>
                                <td class="admin-notes"><?php echo "$general_user->admin_notes"; ?></td>
                                                       
                                <td class="lastcol">
                                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo plugin_basename(__FILE__) ?>">
                                        <input type="hidden" name="mode" value="unban" />                        
                                        <input type="hidden" name="id" value="<?php echo "$general_user->id"; ?>" />
                                        <input type="submit" name="submit" value="Remove Ban" style="float:right;" />
                                    </form>
                                </td>
                            </tr>
<?php                   }
?>                  </tbody>
                </table>
<?php       }   
        }
?>
        <br />            
        <h3 class="wpbu">Add another user to the list...</h3>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo plugin_basename(__FILE__) ?>">
        <fieldset class="wpbu">        
            <table class="form-table wpbu-ft">
            <tbody>
                <tr>
                    <th><label for="user_type">User Type</label></th>
                    <td class="user-type">    
                        <select name="user_type">
                            <option value="public" selected>General User</option>
                            <option value="private">Registered User</option>
                        </select>
                    </td>
                </tr>                
                <tr>
                    <th><label for="user_name">User Name</label></th>
                    <td><input name="user_name" type="text" />&nbsp;</td>
                </tr>
                <tr>
                    <th><label for="admin_notes">Admin Notes</label></th>
                    <td><textarea name="admin_notes" rows="2" cols="40" ></textarea></td>
                </tr>
                <tr>
                    <th><input type="submit" name="submit" value="Ban User!" /></th><td></td>
                </tr>
            </tbody>
            </table>
            
            <input type="hidden" name="mode" value="ban" />      
        </fieldset>    
        </form>
    
    </div>
<?php    
}

function wpbu_get_user_ips($comment_author, $user_type)
{
    global $wpdb;
    
    $table_name = $wpdb->prefix . "comments";
    
    if ($user_type == "public")    
        $sql = "select comment_author_IP from " . $table_name . " where comment_author='" . $comment_author . "' and user_id=0;";
    else
        $sql = "select comment_author_IP from " . $table_name . " where comment_author='" . $comment_author . "' and user_id!=0;";
     
    $comments = $wpdb->get_results($sql);
    
    if ((!isset($comments)) || (count($comments) == 0))
        return "No IP Address Associated.";
    
    $ips = "";
    foreach ($comments as $comment) 
    {
        $ips = $ips . $comment->comment_author_IP . "<br />";
    }

    return $ips;    
}

function wpbu_process_form()
{   
    // check to see if we want to ban or unban a user.
    if (!isset($_POST["mode"]))
        return;
          
    if (($_POST["mode"] != "ban") && ($_POST["mode"] != "unban"))
        return;
    
    // if we want to ban a user, we need to do some additional check
    // 1. check to see if the user name is valid
    if ($_POST["mode"] == "ban")
    {
        // the username must be valid    
        if ((!isset($_POST["user_name"])) || ($_POST["user_name"] == ""))
            return;
        
        global $wpdb;
        $users_table = $wpdb->prefix . "users";
        $banned_table = $wpdb->prefix . "users_banned";

        global $current_user;
        get_currentuserinfo();
        
        // insert the user_name into wp_users_banned    
        $user_name = $wpdb->escape($_POST["user_name"]);
        $user_type = $wpdb->escape($_POST["user_type"]);        
        $admin_name = $current_user->user_login;
        $admin_notes = $wpdb->escape($_POST["admin_notes"]);
    
        if ($user_type == "private")
        {
            $sql = "select user_login from " . $users_table . " where user_login='" . $user_name . "';";
            $users = $wpdb->get_results($sql);
      
            if ((!isset($users)) || (count($users) == 0))
                return;
        }

        $sql = "INSERT INTO " . $banned_table .
            " (user_type, user_name, admin_name, admin_notes) " .
            "VALUES ('" . $user_type . "', '" . $user_name . "', '" . $admin_name . "', '" . $admin_notes . "');";

        $results = $wpdb->query($sql);
    }

    // if we choose to un-ban a user
    else
    {
        if (!isset($_POST["id"]))
            return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . "users_banned";

        $id = $wpdb->escape($_POST["id"]);

        $sql = "DELETE FROM " . $table_name . " where id=" . $id . ";";
        $results = $wpdb->query($sql);        
    }    
}

function wpbu_is_banned($user_name)
{
    global $wpdb;
    
    $table_name = $wpdb->prefix . "users_banned";
    
    $sql = "select * from " . $table_name . " where user_name = '" . $wpdb->escape($user_name) . "';";
    $banned_users = $wpdb->get_results($sql); 
    
    if ((isset($banned_users)) && (count($banned_users) >= 1))
    {
        return true;
    }

    return false;    
}

function wpbu_check_user($author, $email, $url, &$comment, $user_ip, $user_agent)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "users_banned";

    global $current_user;
    get_currentuserinfo();
        
    // check to see if the person making the comment is currently logged in
    if ($current_user->ID)
        $sql = "select * from " . $table_name . " where user_name = '" . $wpdb->escape($author) . "' and user_type = 'private';";
    else
        $sql = "select * from " . $table_name . " where user_name = '" . $wpdb->escape($author) . "' and user_type = 'public';";
        
    $banned_users = $wpdb->get_results($sql); 
    
    if ((isset($banned_users)) && (count($banned_users) >= 1))
    {
        wp_die("<b><i>" . $author. "</i></b>, you are not allowed to comment.", "Uh oh...");
    }  
}

add_action("admin_init", "wpbu_setup");
add_action("admin_head", "wpbu_html_header");
add_action("admin_menu", "wpbu_add_menu");
add_action("wp_blacklist_check", "wpbu_check_user", 10, 6);
?>