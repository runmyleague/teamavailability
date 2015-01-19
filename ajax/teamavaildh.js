document.observe('dom:loaded', function() {
	if($$('.switch'))
	{
		new iPhoneStyle('.switch');
	}
	if($('AmaxDH'))
	{
		Event.observe('AmaxDH', 'change', function() {
			$('Max-total').innerHTML=Number($('AmaxDH').value) + Number($('BmaxDH').value);
		});
		Event.observe('BmaxDH', 'change', function() {
			$('Max-total').innerHTML=Number($('AmaxDH').value) + Number($('BmaxDH').value);
		});
		$$('input[type="radio"]').each(function(el){
			el.observe('change',recalc);
		});	
	}
});

document.observe("switch:flipped", function(ev) {
	var chkbx=ev.memo.el;
	var fs=chkbx.up('legend');
	if(!chkbx.checked)
		fs.setStyle({color:'#8e8e8e'});
	else
		fs.setStyle({color:'#000000'});
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

/*Event.observe('switch','change',lateTimes);

function lateTimes(switch)
{
	day=switch.id.substr(6);
	if(switch.checked)
	{
		$('end-'.day).innerHTML='<OPTION VALUE="">SELECT<OPTION VALUE="20:30:00" SELECTED>8:30 PM';
	}
	else
	{
		$('end-'.day).innerHTML='<OPTION VALUE="">SELECT
				<OPTION VALUE="09:00:00" SELECTED>9:00 AM
				<OPTION VALUE="09:30:00" SELECTED>9:30 AM
				<OPTION VALUE="10:00:00" SELECTED>10:00 AM
				<OPTION VALUE="10:30:00" SELECTED>10:30 AM
				<OPTION VALUE="11:00:00" SELECTED>11:00 AM
				<OPTION VALUE="11:30:00" SELECTED>11:30 AM
				<OPTION VALUE="12:00:00" SELECTED>12:00 PM
				<OPTION VALUE="12:30:00" SELECTED>12:30 PM
				<OPTION VALUE="13:00:00" SELECTED>1:00 PM
				<OPTION VALUE="13:30:00" SELECTED>1:30 PM
				<OPTION VALUE="14:00:00" SELECTED>2:00 PM
				<OPTION VALUE="14:30:00" SELECTED>2:30 PM
				<OPTION VALUE="15:00:00" SELECTED>3:00 PM
				<OPTION VALUE="15:30:00" SELECTED>3:30 PM
				<OPTION VALUE="16:00:00" SELECTED>4:00 PM
				<OPTION VALUE="16:30:00" SELECTED>4:30 PM
				<OPTION VALUE="17:00:00" SELECTED>5:00 PM
				<OPTION VALUE="17:30:00" SELECTED>5:30 PM
				<OPTION VALUE="18:00:00" SELECTED>6:00 PM
				<OPTION VALUE="18:30:00" SELECTED>6:30 PM
				<OPTION VALUE="19:00:00" SELECTED>7:00 PM';
	}

}*/