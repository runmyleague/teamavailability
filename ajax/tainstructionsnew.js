document.observe('dom:loaded', function() {
	/*$('weekday-examples').hide();
	$('dhdays').select('option').each(function(e) {
		if(e.value!='None')
			e.disabled=true;
	});*/
	datePickerController.createDatePicker({formElements:{"startdt":"m-sl-d-sl-Y"}});
	datePickerController.createDatePicker({formElements:{ "enddt":"m-sl-d-sl-Y"}});
	//test if things are already selected(ie. there was an error), then run functions
	/*if($('satavail').checked || $('satorsun').checked)
	{
		activateSat();
	}
	if($('sunavail').checked || $('satorsun').checked)
		activateSun();
	if($('sat1').value)
		consistentTimes($('sat1'));
	if($('sun1').value)
		consistentTimes($('sun1'));
	activateWeekdays();
	Event.observe('eitherormax','change',activateWeekdays);
	Event.observe('eitherormax2','change',activateWeekdays);
	Event.observe('satavail','click', function() {
		forceOrs(this);
		activateSat();
	});
	Event.observe('satorsun','click', function() {
		forceOrs(this);
		deactivateSat();
		deactivateSun();
		activateSat();
		activateSun();
	});
	Event.observe('satnotavail','click', function() {
		forceOrs(this);
		deactivateSat();
	});
	Event.observe('sunavail','click', function() {
		forceOrs(this);
		activateSun();
	});
	Event.observe('sunnotavail','click', function() {
		forceOrs(this);
		deactivateSun();
	});
	Event.observe('sat1', 'change', function() {
		consistentTimes(this);
	});
	Event.observe('sun1', 'change', function() {
		consistentTimes(this);
	});
	Event.observe('dhdays', 'change', adjustWkendMax);
	$$('input[type="checkbox"]').each(function(e) {
		Event.observe(e, 'click',function() {
			exclusiveWeekdays(this);
		});
	});
	Event.observe('show-examples','click', function() {
		$('weekday-examples').toggle();
	});*/
});


function activateSat()
{
	$$('.sattime').each(function(e) {
		e.disabled=false;
		});
	if($('sunavail').checked)
	{
		$('dhdays').select('option').each( function(e) {
				e.disabled=false;
		});
		$('longdistance').select('option').each( function(e) {
				e.disabled=false;
		});
	}
	else if($('satorsun').checked)
	{
		$('dhdays').down('option[value="Either"]').disabled=false;
		$('longdistance').select('option').each( function(e) {
				e.disabled=false;
		});
	}
	else
	{
		$('dhdays').down('option[value="Saturdays"]').disabled=false;
		$('longdistance').down('option[value="Saturdays"]').disabled=false;
	}
	adjustWkendMax();
}
function deactivateSat()
{
	$$('.sattime').each(function(e) {
		e.disabled=true;
		});
	if(['Saturdays','None','Select'].include($('dhdays').value) )
		$('dhdays').value='None';
	else
		$('dhdays').value='Sundays';
	if($('longdistance').value==6)
		$('longdistance').value=0;
	if($('longdistance').value==2)
		$('longdistance').value=1;
	$('dhdays').select('option').each( function(e) {
		if(e.value!='None' && e.value!='Sundays')
			e.disabled=true;
	});
	$('longdistance').select('option').each( function(e) {
		if(e.value!=0 && e.value!=1)
			e.disabled=true;
	});
	adjustWkendMax();
}
function deactivateSun()
{
	$$('.suntime').each(function(e) {
		e.disabled=true;
		});
	if(['Sundays','None','Select'].include($('dhdays').value) )
		$('dhdays').value='None';
	else
		$('dhdays').value='Saturdays';
	if($('longdistance').value==1)
		$('longdistance').value=0;
	if($('longdistance').value==2)
		$('longdistance').value=6;
	$('dhdays').select('option').each( function(e) {
		if(e.value!='None' && e.value!='Saturdays')
			e.disabled=true;
	});
	$('longdistance').select('option').each( function(e) {
		if(e.value!=0 && e.value!=6)
			e.disabled=true;
	});
	adjustWkendMax();
}
function activateSun()
{
	$$('.suntime').each(function(e) {
		e.disabled=false;
		});
	if($('satavail').checked)
	{
		$('dhdays').select('option').each( function(e) {
				e.disabled=false;
		});
		$('longdistance').select('option').each( function(e) {
				e.disabled=false;
		});
	}
	else if($('satorsun').checked)
	{
		$('dhdays').down('option[value="Either"]').disabled=false;
		$('longdistance').select('option').each( function(e) {
				e.disabled=false;
		});
	}
	else
	{
		$('dhdays').down('option[value="Sundays"]').disabled=false;
		$('longdistance').down('option[value="Sundays"]').disabled=false;
	}
	adjustWkendMax();
}

function forceOrs(elclicked)
{
	if(elclicked.value==5)
	{
		$('satavail').checked=false;
		$('sunavail').checked=false;
		$('satnotavail').checked=false;
		$('sunnotavail').checked=false;
	}
	else
	{
		$('satorsun').checked=false;
	}
}

function consistentTimes(elclicked)
{
	var namecl=elclicked.name;
	var valuecl=elclicked.value;
	var namex=namecl.substr(0,3) + '2';
	var elx=$(namex);
	elx.select('option').each(function(e) {
		if(e.value=='')
			e.disabled=false;
		else if(e.value==valuecl)
			e.disabled=false;
		else if(e.value<valuecl)
			e.disabled=true;
		else
			e.disabled=false;
	});
}

function adjustWkendMax()
{
	var dhsel=$('dhdays').value;
	if($('satorsun').checked==true)
	{
		if(dhsel=='Both')
		{
			var sel=2;
			var disable=[0,1,3,4];
		}
		else if(['Either','Saturdays','Sundays'].include(dhsel) )
		{
			var sel=2;
			var disable=[0,1,3,4];
		}
		else
		{
			var sel=1;
			var disable=[0,2,3,4];
		}		
	}
	else if($('satavail').checked==false)
	{
		if($('sunavail').checked==false)
		{
			var sel=0;
			var disable=[1,2,3,4];
		}
		else
		{
			if(dhsel=='Sundays')
			{
				var sel=2;
				var disable=[0,1,3,4];
			}
			else
			{
				var sel=1;
				var disable=[0,2,3,4];
			}
		}
	}
	else
	{
		if($('sunavail').checked==false)
		{
			if(dhsel=='Saturdays')
			{
				var sel=2;
				var disable=[0,1,3,4];
			}
			else
			{
				var sel=1;
				var disable=[0,2,3,4];
			}
		}
		else
		{
			if(dhsel=='Both')
			{
				var sel=4;
				var disable=[0,1,2,3];
			}
			else if(['Either','Saturdays','Sundays'].include(dhsel) )
			{
				var sel=3;
				var disable=[0,1,4];
			}
			else
			{
				var sel=2;
				var disable=[0,3,4];
			}
		}	
	}
}

function exclusiveWeekdays(elclicked)
{
	var valcl=elclicked.value;
	var list=elclicked.id.substr(-1,1);
	var olist=(list==1) ? 2 : 1;
	var subjectid=elclicked.id.replace(list, olist);
	var bro=$(subjectid);
	if(elclicked.checked)
		bro.disabled=true;
	else
		bro.disabled=false;
}

function activateWeekdays()
{
	var active1=$('eitherormax').value;
	var active2=$('eitherormax2').value;
	$$('.eo1').each(function(el) {
		if(active1>0)
			el.enable();
		else
		{
			el.checked=false;
			el.disable();	
		}
	});
	$$('.eo2').each(function(el) {
		if(active2>0)
			el.enable();
		else
		{
			el.checked=false;
			el.disable();
		}
	});
}
