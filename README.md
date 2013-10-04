cforms-stats
============

Add statistical analysis plugin for use with form date collected by cforms (http://www.deliciousdays.com/cforms-plugin/)

What is this useful for?
-------------------------

This plugin is useful for anyone using cforms looking to perform data analysis to judge the effectiveness and conversion rates of their forms. It is also useful for:
- Surveys
- Contest forms
	- The random selection of submissions feature was written specifically for this use case.

Features
-------------------------

- Generates statistical analysis report for form submission data collected by the cforms data tracking feature.
- Reports will contain data based on the following field types:
	- Checkbox
	- Checkbox Group
	- Radio Buttons
	- Selectbox
	- Text Input
	- Textarea
- Report Field information is presented in the order in which the form fields are created in cform. Most of the time this is the order in which they are presented to the user.
- Each Field in the report contains the following information:
	- Total number of form submissions this field was included in.
	- Number and percentage of users who answered the question.
	- Number and percentage of users who abstained from answering the question.
	- Additional information about answer data:
		- For Text Inputs, the top 10 most common answers.
		- For Textareas, the top 10 most common lines of the answers.
		- For Checkboxes, Checkbox Groups, Radio Buttons, and Selectboxes, all answers given ranked by popularity.
- Selection criteria available for reports:
	- Select an individual cforms form to generate a report for.
	- Results unique by
		- IP Address
		- Email Address (if an email field was collected on the form)
	- Specify a date range for the results included in the report.
	- Specify a number of submissions to randomly select and display at the top of your report.

Wishlist
-------------------------
- Input validation on the report form.
- Appropriate messages/checks based on the data tracking settings in cforms.
- Support for archived form fields. Archived fields are those whose label or field type has changed since their creation.
