document.observe('dom:loaded', function() {
	/*if($('availability'))
	{
		availOptions();
		Event.observe('availability', 'change', availOptions);
	}
	datePickerController.createDatePicker({formElements:{"stday":"m-sl-d-sl-Y"}});
	datePickerController.createDatePicker({formElements:{"enday":"m-sl-d-sl-Y"}});*/
	if($$('.switch'))
		new iPhoneStyle('.switch');
	/*$('.checkbox').each(function(el) {
		el.observe('change', recalc);
	});*/
	if($('Amaxgames'))
	{
		Event.observe('Amaxgames', 'change', function() {
			$('Max-total').innerHTML=Number($('Amaxgames').value) + Number($('Bmaxgames').value);
		});
		Event.observe('Bmaxgames', 'change', function() {
			$('Max-total').innerHTML=Number($('Amaxgames').value) + Number($('Bmaxgames').value);
		});
		$$('input[type="radio"]').each(function(el){
			el.observe('change',recalc);
		});	
	}
});

document.observe("switch:flipped", function(ev) {
	var chkbx=ev.memo.el;
	var fs=chkbx.up('legend');
	//var sels=fs.select('select');
	if(!chkbx.checked)
		fs.setStyle({color:'#8e8e8e'});
	else
		fs.setStyle({color:'#000000'});
	/*sels.each(function(el) {
		if(!chkbx.checked)
			el.writeAttribute({disabled:true});
		else
			el.writeAttribute({disabled:false});
	});*/
});

function recalc()
{
	var cts=$H({
		P1:0,
		P2:0,
		P3:0,
		NO:0
	});
	$$('input[type="radio"]:checked').each(function(c){
   		cls=c.className;
   		if(c.checked)
	   		cts.set(cls, Number(cts.get(cls))+1);
	});
	cts.each(function(pair) {
		$(pair.key+'-total').innerHTML=pair.value;
	})
}


/*function availOptions()
{
	if($('availability').value==1)
	{
		$('available-block').show();
	}
	else
	{
		$('available-block').hide();
	}
}

function inputstatus()
{

}*/