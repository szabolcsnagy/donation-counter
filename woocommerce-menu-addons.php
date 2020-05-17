<?php



# adding menu to Woocommerce
add_action("admin_menu","dcfwc_add_menu");
function dcfwc_add_menu() {
  add_submenu_page( 
    'woocommerce',                          # $parent_slug 
    __("Adomanyok",'donation-counter'),     # $page_title
    __("Adományok",'donation-counter'),     # $menu_title
    'manage_woocommerce',                   # $capability requirement  
    'donation-options',                     # $menu_slug
    'dcfwc_update_options_page', # $function - that renders the page
    1                                       # $postition
    );
}

function dcfwc_update_options_page() {
    # switch to html land
    ?>
    <div class="wrap">
      <h2><?php echo __("Adományok","donation-counter")?></h2>
  
      <form method="POST" action="options.php">
        <?php # and php again
          settings_fields("dcfwc_config");
          do_settings_sections("donation-options");
          submit_button();
        ?>
      </form> 
    </div>
  
    <!-- switch back to php land -->
    <?php
}

# admin_init event handler
add_action("admin_init","dcfwc_settings");
function dcfwc_settings(){
  
  add_settings_section(
    "dcfwc_config",          # $id
    "Jelenlegi Adomány Gyujtes",        # $title
    null,                               # $callback
    "donation-options"                  # $page
  );

  add_settings_field(
    "donation-counter-name",            # $id
    "Adomány Neve",                     # $title
    "dcfwc_settings_name",              # $callback
    "donation-options",                 # $page
    "dcfwc_config"                      # $section from above
  );

  #You MUST register any options or they won’t be saved and updated automatically.
  register_setting(
    "dcfwc_config",                     # $option_group - same as the section
    "donation-counter-text"             # $option_name - same as the field name
  );

  # active field
  add_settings_field(
    "donation-counter-active",          # $id
    "Active",                           # $title
    "dcfwc_settings_active",            # $callback
    "donation-options",                 # $page
    "dcfwc_config"                      # $section from above
  );

  register_setting(
    "dcfwc_config",                     # $option_group - same as the section
    "donation-counter-active"           # $option_name - same as the field name
  );

  # sum field
  add_settings_field(
    "donation-counter-sum",             # $id
    "Jelenlegi Osszeg",                 # $title
    "dcfwc_settings_sum",               # $callback
    "donation-options",                 # $page
    "dcfwc_config"                      # $section from above
  );
  
  register_setting(
    "dcfwc_config",                     # $option_group - same as the section
    "donation-counter-sum"              # $option_name - same as the field name
  );

  # past donations
  add_settings_field(
    "donation-counter-list",             # $id
    "",                                  # $title
    "dcfwc_settings_list",               # $callback
    "donation-options",                  # $page
    "dcfwc_config"                       # $section from above
  );
  
  register_setting(
    "dcfwc_config",                   # $option_group - same as the section
    "donation-counter-list"           # $option_name - same as the field name
  );
}

# the callback for the text field
function dcfwc_settings_name() {
  # should render the input control
  ?>
  <div class="postbox" style="padding: 30px;">
    <input type="text" name="donation-counter-name" value="<?php
      echo stripslashes_deep(esc_attr(get_option("donation-counter-name")));
    ?>" />
  </div>

  <?php
}

# the callback for the checkbox
function dcfwc_settings_active() {
  # should render the input control
  ?>
  <div class="postbox" style="padding: 30px;">
    <input type="checkbox" id="donation-counter-active" name="donation-counter-active" value="1" <?php
      echo esc_attr(get_option('donation-counter-active')) == 1 ? 'checked' : '';
    ?> />
    <label for="donation-counter-active">Active</label>
  </div>

  <?php
}


# the callback for the sum
function dcfwc_settings_sum() {
  # should render the input control
  ?>
  <div class="postbox" style="padding: 30px;">
    <input type="text" name="donation-counter-sum" value="<?php
      echo sprintf(get_woocommerce_price_format(),get_woocommerce_currency_symbol(),esc_attr(get_option("donation-counter-sum",0)));
    ?>" readonly/>
  </div>

  <?php
}


# the callback for the list
function dcfwc_settings_list() {
  # should render the input control
  $donationList = json_decode(get_option("donation-counter-list",'[{"name":"Owl","sum":1234,"status":"Active"}]'));
  ?>
  <table>
    <tr>
      <th>Adomany</th>
      <th>Osszeg</th>
      <th>Status</th>
    </tr>
    <?php
    foreach($donationList as $index => $donation) { 
      $sumFormatted = sprintf(get_woocommerce_price_format(),get_woocommerce_currency_symbol(),$donation->sum);
      echo '<tr>';
      echo sprintf('<td>%s</td><td>%s</td><td>%s</td>',$donation->name,$sumFormatted,$donation->status);
      echo '</tr>';
    }
    ?>
  </table>
    
  <?php
}