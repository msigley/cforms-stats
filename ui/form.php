<?php $form_action_uri = add_query_arg( array('action' => 'view-stats', 'results' => current_time('timestamp')) ); ?>
<style type="text/css">
	.ui-datepicker-year {
		display: inline !important;	
	}
</style>
<div class="wrap">
	<h2>cformsII Stats</h2>
	<form action="<?php echo $form_action_uri; ?>" method="post">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="form_id">Form to generate statistics for</label>
					</th>
					<td>
						<select id="form_id" name="form_id">
							<?php cforms_stats_form_options(); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="unique">Unique Results?</label>
					</th>
					<td>
						<select id="unique" name="unique">
							<option value="">No</option>
							<option value="ip">By IP Address</option>
							<option value="email">By Email (First Email Field In Form)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="startdate">Starting Date for Submissions</label>
					</th>
					<td>
						<input type="text" value="" id="startdate" class="datepicker" name="startdate" />
						<span class="description">Leave blank to start from earliest existing submission.</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="enddate">Ending Date for Submissions</label>
					</th>
					<td>
						<input type="text" value="" id="enddate" class="datepicker" name="enddate" />
						<span class="description">Leave blank to end at last existing submission.</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="sub_num">Number of Submissions to Randomly Select</label>
					</th>
					<td>
						<input type="text" value="0" id="sub_num" name="sub_num" />
						<span class="description">0-50</span>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input id="submit" class="button-primary" type="submit" value="Generate Statistics" name="submit">
		</p>
	</form>
</div>