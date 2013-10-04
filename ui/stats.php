<?php global $cforms_stats_data, $cforms_stats_sub_data, $cforms_stats_form_name, $cforms_stats_data_desc; ?>
<div class="wrap">
	<h2>Statistics for <?php echo $cforms_stats_form_name; ?></h2>
	<h3><?php echo $cforms_stats_data_desc; ?></h3>
	<?php if( !empty($cforms_stats_sub_data) ) : ?>
		<h3><?php echo count($cforms_stats_sub_data); ?> Randomly Selected Form Submissions</h3>
		<table class="widefat">
			<thead>
				<tr>
					<th>Submission Date</th>
					<th>Email</th>
					<th>IP Address</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach( $cforms_stats_sub_data as $cforms_stats_sub ) : ?>
				<tr>
					<td><?php echo $cforms_stats_sub->sub_date; ?></td>
					<td><?php echo $cforms_stats_sub->email; ?></td>
					<td><?php echo $cforms_stats_sub->ip; ?></td>
					<td>
						<a href="<?php echo admin_url("admin.php?page=cforms/cforms-database.php&d-id=$cforms_stats_sub->id#entries"); ?>"
							target="_blank">
							View Details
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php foreach( $cforms_stats_data as $cforms_stats_field) : ?>
		<h3><?php echo $cforms_stats_field['name']; ?></h3>
		<p>Field Type: <?php echo $cforms_stats_field['type'] ?></p>
		<?php switch( $cforms_stats_field['type'] ) {
			case 'textarea':
				?>
				<h4>Top 10 Most Common Lines of the Answers</h4>
				<?php
				break;
			case 'textfield':
				?>
				<h4>Top 10 Most Common Answers</h4>
				<?php
				break;
			default:
				?>
				<h4>All Answers by Popularity</h4>
				<?php
				break;
		} ?>
		<table class="widefat">
			<thead>
				<tr>
					<th>Answer</th>
					<th style="width: 200px;">Number of Occurences</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $cforms_stats_field['data'] as $form_data ) : ?>
					<tr>
						<td><?php echo $form_data->data_name; ?></td>
						<td><?php echo $form_data->data_count; ?></td>	
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th>Total Number of Submissions</th>
					<th><?php echo $cforms_stats_field['total_submissions']; ?></th>
				</tr>
				<tr>
					<th>Number of Answers</th>
					<th><?php echo $cforms_stats_field['answer_total']; ?> (<?php echo $cforms_stats_field['answer_percent']; ?>%)</th>
				</tr>
				<tr>
					<th>Number of Abstains</th>
					<th><?php echo $cforms_stats_field['abstain_total']; ?> (<?php echo $cforms_stats_field['abstain_percent']; ?>%)</th>
				</tr>
			</tfoot>
		</table>
	<?php endforeach; ?>
</div>