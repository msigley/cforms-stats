<?php
/*
Plugin Name: cforms Stats
Plugin URI: http://chaosmedley.com/
Description: Provides data statistics for database form data collected by the cformsII plugin (http://www.deliciousdays.com/cforms-plugin/).
Version: 1.0.20131003
Author: Matthew Sigley
Author URI: http://chaosmedley.com/
License: GPLv2

TODO:
-Form field validatation.
-Checks for cforms database tracking.
-Archived form field support.

Change Log:

1.0.20131004
-Added fuzzy matching when comparing textarea lines.

1.0.20131003
-Added options for specifying a date range for the generated statistics.
-Added option for selecting a set number of random form submissions.

1.0.20131002
-Added options for unique results based on IP address or Email Address.
-Fixed field name mathing in the database queries where:
	+Regex and other field options were not being sanitized off of the name correctly.
	+Added a double addslashes call to properly matched the slash escaped names stored in the database.
	+Limited the database field name to the first 100 characters to match the cformsdata table structure.

1.0.20130913
- Initial Release.
*/

global $wpdb, $cforms_stats_data, $cforms_stats_sub_data, $cforms_stats_form_name, $cforms_stats_data_desc;

if (empty($wpdb->cformssubmissions)) $wpdb->cformssubmissions = $wpdb->prefix . 'cformssubmissions';
if (empty($wpdb->cformsdata)) $wpdb->cformsdata = $wpdb->prefix . 'cformsdata';

$cforms_stats_page_hook = '';

add_action( 'admin_menu', 'cforms_stats_menu');
add_action( 'admin_init', 'cforms_stats_init' );
add_action('admin_enqueue_scripts', 'cforms_stats_scripts');

function cforms_stats_menu() {
	global $cforms_stats_page_hook;
	$cforms_stats_page_hook = add_menu_page('cformsII Stats', 'cformsII Stats', 'activate_plugins', 'cforms-stats', 'cforms_stats_menu_page');
}

function cforms_stats_init() {
	wp_register_script('cforms-stats-form', plugins_url('/js/form.js', __FILE__));
}

function cforms_stats_scripts($hook) {
	global $cforms_stats_page_hook;
	
	if($cforms_stats_page_hook != $hook)
		return;
		
	if (empty($_POST)) {
    	//Load scripts for the form
    	wp_enqueue_script('jquery-ui-datepicker');
    	wp_enqueue_script('cforms-stats-form');
    }
}

function cforms_stats_menu_page() {
	global $cforms_stats_data, $cforms_stats_sub_data, $cforms_stats_form_name, $cforms_stats_data_desc;
	
	if (empty($_POST))
		include 'ui/form.php';
	else {
		extract($_POST);
		$cforms_stats_data = cforms_stats_get_fields($form_id);
		cforms_stats_build_data($form_id, $cforms_stats_data, $unique, $startdate, $enddate);
		$cforms_stats_sub_data = cforms_stats_random_submissions($form_id, $sub_num, $unique, $startdate, $enddate);
		$cforms_stats_form_name = cforms_stats_get_form_name($form_id);
		$cforms_stats_data_desc = "Showing ";
		switch($unique) {
			case 'ip':
				$cforms_stats_data_desc .= "Last Submissions From Each Unique IP Address";
				break;
			case 'email':
				$cforms_stats_data_desc .= "Last Submissions From Each Unique Email Address";
				break;
			default:
				$cforms_stats_data_desc .= "All Submissions";
		}
		$cforms_stats_data_desc .= " From ";
		if(!empty($startdate))
			$cforms_stats_data_desc .= "$startdate 00:00:00";
		else
			$cforms_stats_data_desc .= "The Begining of Time";
		
		$cforms_stats_data_desc .= " To ";
		if(!empty($startdate))
			$cforms_stats_data_desc .= "$enddate 23:59:59";
		else
			$cforms_stats_data_desc .= "The End of Time";
		include 'ui/stats.php';
	}
}

/*
 * Outputs the selectbox options for selecting a cforms form.
 */
function cforms_stats_form_options() {
	//Get a list of all of the forms
	$cformsSettings = get_option('cforms_settings');
	for ($i=1; $i <= $cformsSettings['global']['cforms_formcount']; $i++){
		$n = ( $i==1 )?'':$i;
		$form_names[$i]=stripslashes($cformsSettings['form'.$n]['cforms'.$n.'_fname']);
	}
	
	foreach($form_names as $form_id=>$form_name) {
		echo '<option value="'.$form_id.'">'.$form_name.'</option>'."\n";
	}
}

/*
 * Builds an array of form field infoformation (name, type, value set) for the cforms id given.
 */
function cforms_stats_get_fields($cforms_form_id) {
	$cformsSettings = get_option('cforms_settings');
	
	//Build Form Field Information Array
	$n = ( $cforms_form_id==1 )?'':$cforms_form_id;
	$num_form_fields = $cformsSettings['form'.$n]['cforms'.$n.'_count_fields'];
	$form_fields = array();
	for ($i=1; $i<=$num_form_fields; $i++) {
		$form_field = $cformsSettings['form'.$n]['cforms'.$n.'_count_field_'.$i];
		$form_field = explode('$#$', $form_field );
		$field_name = stripslashes($form_field[0]);
		$field_type = $form_field[1];
		$field_value_set = array();
		switch($field_type) {
			//Skip Irrelevant Field Types
			case 'fieldsetstart':
			case 'fieldsetend':
			case 'textonly':
			case 'emailtobox':
			case 'ccbox':
			case 'verification':
			case 'captcha':
				continue 2;
				break;
			case 'checkbox':
			case 'radiobuttons':
			case 'selectbox':
			case 'checkboxgroup':
				//Populate value set and correct name
				$field_value_set = explode('#', $field_name);
				$field_name = stripslashes(array_shift($field_value_set));
				foreach ($field_value_set as $key => &$field_value) {
					if (false !== stripos($field_value, '|'))
						$field_value = explode('|', $field_value);
					elseif (!empty($field_value))
						unset($field_value_set[$key]);
				}
				
				//Normalize value set array indices
				$field_value_set = array_values($field_value_set);
				break;
		}
		
		//Remove regex information from field name
		$field_name = array_shift(explode('|', $field_name));
		
		$form_fields[] = array( 'name' => $field_name,
							'type' => $field_type,
							'value_set' => $field_value_set
							);
	}
	
	return $form_fields;
}

/*
 * Parses the cform field information and generates the statistics from the actual form submissions in the database
 */
function cforms_stats_build_data($cforms_form_id, &$form_fields, $unique='', $startdate='', $enddate='') {
	global $wpdb;
	$n = ( $cforms_form_id==1 )?'':$cforms_form_id;
	$unique_query = cforms_stats_unique_query($unique);
	$date_query = cforms_stats_unique_query($startdate, $enddate);
	
	
	foreach($form_fields as &$form_field) {
		$db_field_name = addslashes(addslashes(substr($form_field['name'], 0, 100)));
		$delimiter = '';
		switch($form_field['type']) {
			case "textfield":
				//Get the exact 10 most common results
				$query = "SELECT t.field_val as data_name,
								COUNT(t.field_val) as data_count
							FROM ( 
								SELECT *
									FROM $wpdb->cformssubmissions
									JOIN $wpdb->cformsdata ON $wpdb->cformssubmissions.id = $wpdb->cformsdata.sub_id
									WHERE $wpdb->cformssubmissions.form_id = '$n'
									AND $wpdb->cformsdata.field_name = '$db_field_name'
									AND TRIM($wpdb->cformsdata.field_val) <> ''
									$date_query
									$unique_query
								) t
							GROUP BY t.field_val
							ORDER BY data_count DESC
							LIMIT 10";
				$results = cforms_stats_db_select($query);
				break;
				
			case "textarea":
				//Data is broken down by phrases separated by newlines. 
				$delimiter = "\n";
				$limit = 10;
				$fuzzy_matching = true;
			case "checkboxgroup":
				//Assume data is comma delimited, is delimiter is not set
				if (empty($delimiter)) $delimiter = ',';
				if (empty($fuzzy_matching)) $fuzzy_matching = false;
				
				//Get the unique data sets and their number of occurances
				$query = "SELECT t.field_val as data_values,
								COUNT(t.field_val) as data_count
							FROM (
								SELECT *
									FROM $wpdb->cformssubmissions
									JOIN $wpdb->cformsdata ON $wpdb->cformssubmissions.id = $wpdb->cformsdata.sub_id
									WHERE $wpdb->cformssubmissions.form_id = '$n'
									AND $wpdb->cformsdata.field_name = '$db_field_name'
									AND TRIM($wpdb->cformsdata.field_val) <> ''
									$date_query
									$unique_query
								) t
							GROUP BY t.field_val
							ORDER BY data_count DESC";
				$result_sets = cforms_stats_db_select($query);
				
				//Combine data into a single array
				$results = array();
				foreach ($result_sets as $result_set) {
					//Sanitize newlines
					$data_values = preg_replace('/[\r\n]+/', "\n", $result_set->data_values);
					//Split data values based on delimiter
					$data_values = explode($delimiter, $data_values);
					foreach ($data_values as $data_value) {
						//Basic sanitation
						$data_value = trim($data_value);
						
						//Extensive sanitation to implement fuzzy matches
						if ($fuzzy_matching) {
							$data_value = strtolower($data_value);
							
							//Eliminate articles at the begining of the string
							$articles = array('the', 'a', 'an');
							$data_value = explode(' ', $data_value);
							if( false !== array_search($data_value[0], $articles) )
								array_shift($data_value);
							
							//Replace commonly used symbols with their article equivelent
							$articles = array('&' => 'and', '@' => 'at', '#' => 'number', '%' => 'percent');
							foreach( $data_value as &$data_word ) {
								if( isset($articles[$data_word]) )
									$data_word = $articles[$data_word];
							}
							
							$data_value = implode(' ', $data_value);
							
							//Remove answers equivelent to nothing
							$empty_words = array('nope', 'no', 'none', 'nothing', 'n/a', 'n\a');
							if( false !== array_search($data_value, $empty_words) )
								$data_value = '';
						}
							
						if (!empty($data_value))
							$results[$data_value] += $result_set->data_count;
					}
				}
				
				//Sort results by data_count
				arsort($results);
				
				//Enforce results limit
				if (!empty($limit)) $results = array_slice($results, 0, $limit);
					
				//Reformat results to array of objects
				foreach ($results as $data_name => &$result) {
					$result = (object) array('data_name' => $data_name, 'data_count' => $result);
				}
				break;
				
			case "selectbox":
			case "radiobuttons":
				$query = "SELECT t.field_val as data_name,
								COUNT(t.field_val) as data_count
							FROM (
								SELECT *
									FROM $wpdb->cformssubmissions
									JOIN $wpdb->cformsdata ON $wpdb->cformssubmissions.id = $wpdb->cformsdata.sub_id
									WHERE $wpdb->cformssubmissions.form_id = '$n'
									AND $wpdb->cformsdata.field_name = '$db_field_name'
									AND TRIM($wpdb->cformsdata.field_val) <> ''
									$date_query
									$unique_query
							) t
							GROUP BY t.field_val
							ORDER BY data_count DESC";
				$results = cforms_stats_db_select($query);
				break;
		}
		$form_field['data'] = $results;
		
		//Get the total number of answers
		$query = "SELECT COUNT(t.f_id) as answer_count
					FROM ( 
						SELECT *
							FROM $wpdb->cformssubmissions
							JOIN $wpdb->cformsdata ON $wpdb->cformssubmissions.id = $wpdb->cformsdata.sub_id
							WHERE $wpdb->cformssubmissions.form_id = '$n'
							AND $wpdb->cformsdata.field_name = '$db_field_name'
							AND TRIM($wpdb->cformsdata.field_val) <> ''
							$date_query
							$unique_query
					) t";
		$results = cforms_stats_db_select($query);
		$form_field['answer_total'] = (int) $results[0]->answer_count;
		
		//Get the total number abstains
		$query = "SELECT COUNT(t.f_id) as abstain_count
					FROM (
						SELECT *
							FROM $wpdb->cformssubmissions
							JOIN $wpdb->cformsdata ON $wpdb->cformssubmissions.id = $wpdb->cformsdata.sub_id
							WHERE $wpdb->cformssubmissions.form_id = '$n'
							AND $wpdb->cformsdata.field_name = '$db_field_name'
							AND TRIM($wpdb->cformsdata.field_val) = ''
							$date_query
							$unique_query
						) t";
		$results = cforms_stats_db_select($query);
		$form_field['abstain_total'] = (int) $results[0]->abstain_count;
		
		//Calculate answer percentages
		$form_field['total_submissions'] = $form_field['answer_total'] + $form_field['abstain_total'];
		$form_field['answer_percent'] = round( ($form_field['answer_total'] / $form_field['total_submissions']) * 100, 2 );
		$form_field['abstain_percent'] = round( ($form_field['abstain_total'] / $form_field['total_submissions']) * 100, 2);
	}
}

function cforms_stats_random_submissions($cforms_form_id, $sub_num=0, $unique='', $startdate='', $enddate='') {
	global $wpdb;
	$n = ( $cforms_form_id==1 )?'':$cforms_form_id;
	$unique_query = cforms_stats_unique_query($unique);
	$date_query = cforms_stats_unique_query($startdate, $enddate);
	
	$query = "SELECT *
				FROM (
					SELECT *
						FROM $wpdb->cformssubmissions
						WHERE $wpdb->cformssubmissions.form_id = '$n'
						$date_query
						$unique_query
					) t
				ORDER BY RAND()
				LIMIT $sub_num";
	return cforms_stats_db_select($query);
}

/*
 * Get form's current name.
 */
function cforms_stats_get_form_name($cforms_form_id) {
	$cformsSettings = get_option('cforms_settings');
	$n = ( $cforms_form_id==1 )?'':$cforms_form_id;
	return stripslashes($cformsSettings['form'.$n]['cforms'.$n.'_fname']);
}

/*
 * Performs a SELECT operation on the wordpress database and forces the output as an array.
 */
function cforms_stats_db_select($query) {
	global $wpdb;
	$results = $wpdb->get_results($query);
	if (false === $results)
		return false;
	if (!is_array($results))
		return array($results);
	return $results;
}

/*
 * Builds the portion of SELECT query that specifies the unique criteria
 */
function cforms_stats_unique_query($unique) {
	global $wpdb;
	$unique_query = '';
	switch($unique) {
		case "ip":
			$unique_query = "GROUP BY $wpdb->cformssubmissions.ip
							ORDER BY $wpdb->cformssubmissions.sub_date\n";
			break;
		case "email":
			$unique_query = "AND TRIM($wpdb->cformssubmissions.email) <> '' 
							GROUP BY $wpdb->cformssubmissions.email
							ORDER BY $wpdb->cformssubmissions.sub_date\n";
			break;
	}
	return $unique_query;
}

/*
 * Builds the portion of SELECT query that specifies the date range criteria
 */
function cforms_stats_date_query($startdate, $enddate) {
	global $wpdb;
	$date_query = '';
	if( !empty($startdate) )
		$date_query .= "AND $wpdb->cformssubmissions.sub_date >= '$startdate 00:00:00'\n";
	if( !empty($enddate) )
		$date_query .= "AND $wpdb->cformssubmissions.sub_date <= '$enddate 23:59:59'\n";
	return $date_query;
}