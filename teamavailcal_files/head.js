document.observe("dom:loaded", function() {
	menu=new AccordMenu();
	$('username').observe('focus',function(el) {
		this.clear();
	});
	$$('.accordion').each(function(el) {
		var id=el.id;
		menu.createAccordion(id);
	});
	$$('.expander').each(function(el) {
		el.observe('click',function(ev) {
			var exp=this.up('div.body');
			if(exp.hasClassName('collapsed'))
			{
				exp.morph('expanded', {	duration:2.0,
						afterFinish:function(effect) {
							exp.removeClassName('collapsed');
						}
				});
			}
			else
			{
				exp.morph('collapsed', {	duration:2.0,
						afterFinish:function(effect) {
							exp.removeClassName('expanded');
						}
				});
			}
		});
	});
	if($('hidewarnings'))
	{
		$('hidewarnings').observe('click', function(ev) {
			ev.stop();
			var myID=$('hidewarnings').getAttribute('data-myid');
			var time=$('hidewarnings').getAttribute('data-time');
			document.cookie='hw='+time+'|'+myID;
			$('deadlines').hide();
		});
	}
});

var AccordMenu = Class.create({
	initialize: function() {
		this.maxHt=new Object();
		this.done=new Object();
		this.startHt=$('access-bar').getHeight();
		this.pulldown='accord';
	},
	createAccordion: function(id)
	{
		if($(id).hasClassName('pulldown'))
			this.pulldown=id;
		this.maxHt[id]=(this.maxHt[id] > 0) ? this.maxHt[id] : 0;
		var contents=$$('div.'+id+'_content');
		for(var i=0; i<contents.length; i++) {
				if(contents[i].getHeight() > this.maxHt[id]) {
					this.maxHt[id] = contents[i].getHeight();
				}
		}
		if($(id).down('div.accordion'))
		{
			var child=$(id).down('div.accordion').id;
			this.createAccordion(child);
			this.done[child]=true;
			///this needs to be delayed til above finishes
			if(!this.done[id])
			{
				this.createAccordion(id);
				this.done[id]=true;
			}
		}
		else if(!this.done[id])
		{
			new Accordion(id,{
								toggleClass : id+'_toggle',
								toggleActive : 'accord_toggle_active',
								contentClass : id+'_content',
								maxHeight : this.maxHt[id],
								startHeight : this.startHt,
								accordID: this.pulldown
			});
			$(id).removeClassName('accordion');
		}
	}
});