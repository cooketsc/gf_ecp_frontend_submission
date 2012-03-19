<?php
/*---------------------------------------
*  Post GF Form data to Events Calendar Pro
*  
*  Create front-end submission forms by 
*  formatting GF Form data for Events
*  Calendar Pro API
*
*  For more info and GF form boilerplate visit:
*  http://anthonydispezio.com/blog/gf-ecp-frontend-submission/ ‎
----------------------------------------*/

//  Add the action hook to format the GF form data into ECP event meta
//  The GF form id is added to the "gform_pre_submission" hook so it only fires when that form is submitted
add_action("gform_pre_submission_1", "format_tec_event_meta_from_gravity");

function format_tec_event_meta_from_gravity(){

	/*  VARIABLES - The following variables should correspond to their respective GF form elements IDs */
	
	// All day event
	$eventAllDay = 3;

	// Start and end dates (and times if the event is not all day)
	$startDateFormId = 4;
	$startTimeFormId = 5;
	$endDateFormId = 6;
	$endTimeFormId = 7;

	// Event recurrence type
	$recType = 8;

	// Recurrence ends "On" a speceific date or "After" a certain number of occurrences
	$recEndType = 9;

	// End date for event recurrence (if "On" is selected)
	$recEnd = 10;

	// A different "After" multiplier exists for each possible recurrence type (if "After" is selected)
	$recEndCounts = array(
		'Every Day' => 11,
		'Every Week' => 12,
		'Every Month' => 13,
		'Every Year' => 14,
		);

	// Venue details
	$venueName = 18;
	$venueAddress = 19;
	$venueCity = 20;
	$venueCountry = 21;

	// for neither US or Canada, use province text field
	$venueProvince = 22;

	// for US, use state dropdown (two letter values have been added to match ECP meta)
	$venueState = 23;

	// for Canada, use province/territory dropdown
	$venueCaProvince = 24;

	$venueZip = 25;
	$venuePhone = 36;

	// Google Maps
	$showGoogleMapLink = 26;
	$showGoogleMap = 27;

	//Organizer details
	$organizerName = 29;
	$organizerPhone = 30;
	$organizerWebsite = 31;
	$organizerEmail = 32;

	/*  DATE & TIME FORMATTING - Format the date and time from GF to match ECP meta */
	
	// break the dates into arrays
	$startDate = date_parse($_POST['input_'. $startDateFormId]); 
	$endDate = date_parse($_POST['input_'. $endDateFormId]);

	// sql format the result
	$startDateString = $startDate['year'] . '-' . str_pad($startDate['month'], 2, "0", STR_PAD_LEFT) . '-' . str_pad($startDate['day'], 2, "0", STR_PAD_LEFT);
	$endDateString = $endDate['year'] . '-' . str_pad($endDate['month'], 2, "0", STR_PAD_LEFT) . '-' . str_pad($endDate['day'], 2, "0", STR_PAD_LEFT);

	// get the start/end times
	$startTime = $_POST['input_'. $startTimeFormId];
	$endTime = $_POST['input_'. $endTimeFormId];

	/* SET ECP FORM VALUES - Set the ECP form values to match their respective GF fields */

	$_POST['EventAllDay'] = $_POST['input_'. $eventAllDay];	

	$_POST['EventStartDate'] = $startDateString;
	$_POST['EventStartHour'] = str_pad($startTime[0], 2, "0", STR_PAD_LEFT);
	$_POST['EventStartMinute'] = str_pad($startTime[1], 2, "0", STR_PAD_LEFT);
	$_POST['EventStartMeridian'] = $startTime[2];

	$_POST['EventEndDate'] = $endDateString;
	$_POST['EventEndHour'] = str_pad($endTime[0], 2, "0", STR_PAD_LEFT);
	$_POST['EventEndMinute'] = str_pad($endTime[1], 2, "0", STR_PAD_LEFT);
	$_POST['EventEndMeridian'] = $endTime[2];

	$_POST['recurrence']['type'] = $_POST['input_'. $recType];
	$_POST['recurrence']['end-type'] = $_POST['input_'. $recEndType];
	$_POST['recurrence']['end'] = $_POST['input_'. $recEnd];

	// Match the correct recurrence multiplier with the correct recurrence type
	foreach($recEndCounts as $recTypeName => $recEndCount){
		if ($_POST['input_'. $recType] == $recTypeName) {
			$_POST['recurrence']['end-count'] = $_POST['input_'. $recEndCount];
		}
	}

	// Check for the existence of the submitted venue and organization by title
	$savedVenue = get_page_by_title($_POST['input_'. $venueName], 'OBJECT', 'tribe_venue');
	$savedOrganizer = get_page_by_title($_POST['input_'. $organizerName], 'OBJECT', 'tribe_organizer');

	// If the venue already exists, pass along the exising venue ID
	if (isset($savedVenue)){
		$_POST['venue']['VenueID'] = $savedVenue->ID;
	// If the venue doesn't exist, pass the venue meta needed to create a new venue
	} else {
		// Required for venue info to be stored
		$_POST['EventVenue'] = $_POST['input_'. $venueName];
		$_POST['post_title'] = $_POST['input_'. $venueName];

		// Pass remaining venue meta
		$_POST['venue']['Venue'] = $_POST['input_'. $venueName];
		$_POST['venue']['Address'] = $_POST['input_'. $venueAddress];
		$_POST['venue']['City'] = $_POST['input_'. $venueCity];
		$_POST['venue']['Country'] = $_POST['input_'. $venueCountry];
		// Ensure that the correct state or province field is populated
		switch($_POST['input_'. $venueCountry]) {
			case 'United States':
				$_POST['venue']['State'] = $_POST['input_'. $venueState];
				break;
			case 'Canada':
				$_POST['venue']['Province'] = $_POST['input_'. $venueCaProvince];
				break;
			default: 
				$_POST['venue']['Province'] = $_POST['input_'. $venueProvince];
				break;
		}
		$_POST['venue']['Zip'] = $_POST['input_'. $venueZip];
		$_POST['venue']['Phone'] = $_POST['input_'. $venuePhone];
	}

	// Pass google maps meta
	$_POST['EventShowMapLink'] = $_POST['input_'. $showGoogleMapLink];	
	$_POST['EventShowMap'] = $_POST['input_'. $showGoogleMap];	

	// If the organizer already exists, pass along the exising organizer ID
	if (isset($savedOrganizer)){
		$_POST['organizer']['OrganizerID'] = $savedOrganizer->ID;
	} else {
		// If the venue doesn't exist, pass the venue meta needed to create a new venue
		$_POST['organizer']['Organizer'] = $_POST['input_'. $organizerName];
		$_POST['organizer']['Phone'] = $_POST['input_'. $organizerPhone];

		//If the user doesn't put in a web address we want to make the website '' instead of 'http://' since that's what Gravity Forms adds by default
		$_POST['organizer']['Website'] = $_POST['input_'. $organizerWebsite] == 'http://' ? '' : $_POST['input_'. $organizerWebsite];

		$_POST['organizer']['Email'] = $_POST['input_'. $organizerEmail];
	}
}


// Store the new form values as ECP metadata when saving
add_action('save_post', 'save_tec_event_meta_from_gravity', 11, 2);

function save_tec_event_meta_from_gravity($postId, $post) {
	if( class_exists('TribeEvents') ) {

	// only continue if it's an event post
	if ( $post->post_type != TribeEvents::POSTTYPE || defined('DOING_AJAX') ) {
		return;
	}

	// don't do anything on autosave or auto-draft either or massupdates
	//if ( wp_is_post_autosave( $postId ) || isset($_GET['bulk_edit']) )
	if ( wp_is_post_autosave( $postId ) || $post->post_status == 'auto-draft' || isset($_GET['bulk_edit']) || $_REQUEST['action'] == 'inline-save' )
	{
		return;
	}

	if( class_exists('TribeEventsAPI') ) {
		$_POST['Organizer'] = stripslashes_deep($_POST['organizer']);
		$_POST['Venue'] = stripslashes_deep($_POST['venue']);
		$_POST['Recurrence'] = stripslashes_deep($_POST['recurrence']);

		if( !empty($_POST['Venue']['VenueID']) )
		$_POST['Venue'] = array('VenueID' => $_POST['Venue']['VenueID']);

		if( !empty($_POST['Organizer']['OrganizerID']) )
		$_POST['Organizer'] = array('OrganizerID' => $_POST['Organizer']['OrganizerID']);

		TribeEventsAPI::saveEventMeta($postId, $_POST, $post);
		}
	}
} 
?>