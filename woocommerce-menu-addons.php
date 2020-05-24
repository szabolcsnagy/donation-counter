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

  #You MUST register any options or they won’t be saved and updated automatically.
  register_setting(
    "dcfwc_config",                     # $option_group - same as the section
    "donation-counter-name"             # $option_name - same as the field name
  );

  register_setting(
    "dcfwc_config",                     # $option_group - same as the section
    "donation-counter-id"               # $option_name - same as the field name
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


# the callback for the list
function dcfwc_settings_list() {
  
  # should render the input control
  $donationList = get_option("donation-counter-list",'');
  ?>
  <input type="hidden" id="donation-counter-id" name="donation-counter-id" value="<?php
      esc_attr_e(get_option('donation-counter-id'));
  ?>" />
  <input type="hidden" id="donation-counter-name" name="donation-counter-name" value="<?php
      esc_attr_e(get_option('donation-counter-name'));
  ?>" />
  <input type="hidden" name="donation-counter-sum" value="<?php esc_attr_e(dcfwc_add_up_donation(get_option('donation-counter-id')));?>"/>
  <input type="hidden" name="donation-counter-list" id="donation-counter-list" value="<?php esc_attr_e($donationList);?>"/>
  <button class="button" id="new-donation">Új kampány hozzáadása</button>
  
  <table id="past-donations">
    <thead>
    <tr>
      <th>Adomány</th>
      <th>Összeg</th>
      <th>Státusz</th>
      <th></th>
    </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
 
  <script id="row-template" type="text/x-custom-template">
    <tr data-campaign-id={id}>
        <td>{campaign}</td>
        <td>{sum}</td>
        <td>{status}</td>
        <td>{close}</td>
    <tr>
  </script>
  <script id="new-row-template" type="text/x-custom-template">
    <tr data-campaign-id={id} id="new-campaign">
        <td><input type="text" name="campaign"/></td>
        <td>0 Ft</td>
        <td>Aktív</td>
        <td><button id="save-new" type="submit" class="button">Mentés</button></td>
    <tr>
  </script>
  <script id="no-donations-template" type="text/x-custom-template">
    <tr>
        <td colspan="4">No campaign yet.</td>
    <tr>
  </script>

  
  <script>
    (function($){
      var ACTIVE_LABEL = 'Aktív';
      var originalList = function() {
        var list = $('#donation-counter-list').val();
        
        var arrList = [];
        if (list) {
          var arrList = JSON.parse(list);
          arrList = $.isArray(arrList) ? arrList : [];
        }
        var campaignId = $('#donation-counter-id').val();
        var currentSum = $('[name="donation-counter-sum"]').val();
        if(!isNaN(parseInt(campaignId)) && !isNaN(parseFloat(currentSum))) {
          arrList =arrList.map(function(donation){
            if(donation.id == campaignId) {
              donation.sum = currentSum;
            }
            return donation;
          })
        }
        // write back the list with the new sum
        $('#donation-counter-list').val(JSON.stringify(arrList));

        return arrList;
      }();

      var nextId = originalList.reduce(function(acc,donation){
        var id = isNaN(parseInt(donation.id))?0:donation.id;
        return acc < id? id : acc;
      },0) + 1;

      function init(arrList){
        
          if (arrList.length) {

            var template = $('#row-template').html();
            arrList.forEach(function(row){
              $('#past-donations tbody').append(
                template.replace('{id}',row.id)
                .replace('{campaign}',row.campaign)
                .replace('{sum}',row.sum + ' Ft')
                .replace('{status}',row.status)
                .replace('{close}',row.status === ACTIVE_LABEL?'<button id="close-campaign" class="button" type="submit">Lezár</button>':'')
                )
              
            });

            $('#past-donations tbody').find('#close-campaign').on('click',closeCampaign);

          } else {  
            $('#past-donations tbody').append($('#no-donations-template').html());
          }
      
        
        $('#new-donation').on('click',function(e){
          
          e.preventDefault();
          if(hasActive(originalList) && !confirm('Az új kampány automatikusan lezárja a jelenleg aktívat. Mehet?')) {
            return;
          }
          if(!$('#past-donations #new-campaign').length){
            $('#past-donations tbody').prepend($('#new-row-template').html())
            .find('#save-new').on('click',saveCampaign);
          }
        });
      }

      function closeAllActive(arrList) {
        return arrList.map(function(donation){donation.status='Lezárva';return donation;});
      }

      function hasActive(arrList) {
        return arrList.filter(function(donation){return donation.status==='Active';}).length > 0;
      }
      function saveCampaign(e) {
        
        var button = $(e.target);
        var textInput = button.closest('tr').find('[name=campaign]')
        var campaign = textInput.val();
        button.attr('disabled',true);
        textInput.attr('disabled',true);
        if(!campaign) {
          alert('Adomány név kötelezõ');
          e.preventDefault();
          return;
        }
       
        var record = {
          id: nextId,
          campaign: campaign,
          sum:0,
          status: ACTIVE_LABEL
        };
        $('[name=donation-counter-name]').val(record.campaign);
        $('[name=donation-counter-id]').val(record.id);
        
        originalList = closeAllActive(originalList);
        originalList.unshift(record);
        var newList = JSON.stringify(originalList);
        $('#donation-counter-list').val(newList);
      }

      function closeCampaign(e){
        
        if (!confirm('Biztos hogy lezárod a kampányt?')) {
          e.preventDefault();
          return;
        } 
        $('[name=donation-counter-name]').val('');
        $('[name=donation-counter-id]').val('');
        originalList = closeAllActive(originalList);
        var newList = JSON.stringify(originalList);
        $('#donation-counter-list').val(newList);
      }
      
      init(originalList);
      
    })(jQuery);
  </script>
  <?php
}

# add up the donations for a given campaign
function dcfwc_add_up_donation($campaign_id) {
  global $wpdb;
  $query = "select sum(meta_value) from wp_postmeta pm join wp_posts p on pm.post_id=p.id where pm.meta_key='_donation_amount_%d' AND p.post_status='wc-completed'";
  $summa = $wpdb->get_var($wpdb->prepare($query,$campaign_id));
  return $summa;
}