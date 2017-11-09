<div class="wpwoof-box">
    <form method="post" action="">
        <h3>Feed Auto Refresh Interval:</h3>
        
        <p class="wpwoof-aligncenter">
            <b>Interval</b>
            <select name="wpwoof_schedule" id="wpwoof_schedule">
                <?php 
                $current_interval = wpwoof_get_interval();
                $intervals = array(
                    /*
                    '604800'    => '1 Week',
                    '86400'     => '24 Hours',
                    '43200'     => '12 Hours',
                    '21600'     => '6 Hours',
                    '3600'      => '1 Hour',
                    '900'       => '15 Minutes',
                    '300'       => '5 Minutes',
                    */
                    '3600'      => 'Hourly',
                    '43200'     => 'Twice daily',
                    '86400'     => 'Daily',
                );
                foreach($intervals as $interval => $interval_name) {
                    echo '<option ', selected( $interval, $current_interval, true),' value="', $interval, '">', $interval_name, '</option>';
                }
                ?>
            </select>
            <input type="submit" class="wpwoof-button wpwoof-button-blue" value="Update Interval" name="wpwoof_schedule_submit" />
        </p>
    </form>
</div>

<?php include('feed-manage-list.php');

$myListTable = new Wpwoof_Feed_Manage_list();
$myListTable->prepare_items();

  ?>
<form id="contact-filter" method="post">
	<!-- For plugins, we also need to ensure that the form posts back to our current page -->
	<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
	<?php //$myListTable->search_box('search', 'search_id'); ?>
	<!-- Now we can render the completed list table -->
	<?php $myListTable->display() ?>
</form>

<div class="wpwoof-content-bottom wpwoof-box">
    <h2>Are you doing Facebook Ads? Get our <span class="red_color">FREE</span> Facebook Pixel Guide</h2>
    <div><strong>After more than 10 000 users and many hours spent on answering questions, we decided to make a comprehensive guide about the new Facebook Pixel.</strong></div>
    <div><strong>And then give it to you for free.</strong></div>
    <p><a target="_blank" href="http://www.pixelyoursite.com/facebook-pixel-pdf-guide" class="wpwoof-button wpwoof-button-red">CLICK TO GET THE FREE GUIDE</a></p>
    <div>Download the Facebook Pixel FREE Guide, because we answer to all the essential questions (and some more)</div>
</div>