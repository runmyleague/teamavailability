document.observe('dom:loaded', function() {
	datePickerController.createDatePicker({formElements:{"homeonly":"m-sl-d-sl-Y"}});
	datePickerController.createDatePicker({formElements:{ "longdist":"m-sl-d-sl-Y"}});
});
