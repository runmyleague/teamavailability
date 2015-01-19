/* Simple Accordion Script 
 * Requires Prototype and Script.aculo.us Libraries
 * By: Brian Crescimanno <brian.crescimanno@gmail.com>
 * http://briancrescimanno.com
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0
 * http://creativecommons.org/licenses/by-sa/3.0/us/
 */

if (typeof Effect == 'undefined')
  throw("You must have the script.aculo.us library to use this accordion");

var Accordion = Class.create({

    initialize: function(id, options) {
        if(!$(id)) throw("Attempted to initalize accordion with id: "+ id + " which was not found.");
        this.accordion = $(id);
        this.options = Object.extend({
            toggleClass: "accord_toggle",
            toggleActive: "accord_toggle_active",
            contentClass: "accord_content",
            maxHeight: 0,
            startHeight: 0,
            accordID: 'accord'
        }, options || {});
        this.contents = this.accordion.select('div.'+this.options.contentClass);
        this.isAnimating = false;
        this.maxHeight = 0;
        this.current = this.contents[0];
        this.toExpand = null;

        this.checkMaxHeight();
        this.attachInitialMaxHeight();
        this.initialHide();
        this.current.previous('div.'+this.options.toggleClass).removeClassName(this.options.toggleActive);

        this.accordion.observe('click', function(ev) {
        	this.mouseY=Event.pointerY(ev);
        	this.clickHandler(ev);
        	//ev.stop();
        }.bindAsEventListener(this));
    },

    expand: function(el) {
    	if(el)
    	{
	        this.toExpand = el.next('div.'+this.options.contentClass);
			this.toExpand.show();
    		this.animate();
    	}  
    },

    checkMaxHeight: function() {
    	if(this.options.maxHeight)
    		this.maxHeight=this.options.maxHeight;
    	else
    	{
			for(var i=0; i<this.contents.length; i++) {
				if(this.contents[i].getHeight() > this.maxHeight) {
					this.maxHeight = this.contents[i].getHeight();
				}
			}
		} 
    },

    attachInitialMaxHeight: function() {
		if(this.current)
		{
			this.current.previous('div.'+this.options.toggleClass).addClassName(this.options.toggleActive);
			if(this.current.getHeight() != this.maxHeight) 
				this.current.setStyle({height: this.maxHeight+"px"});
       	}
    },

    clickHandler: function(e) {
        var x = e.element();
        if(!$(x).hasClassName(this.options.toggleClass))
        	el=x.up('.'+this.options.toggleClass);
        else
        	el=x;
        if(!this.isAnimating) {
            this.expand(el);
        }
    },

    initialHide: function(){
        for(var i=0; i<this.contents.length; i++){
            //if(this.contents[i] != this.current) {
                this.contents[i].hide();
                this.contents[i].setStyle({height: 0});
            //}
        }
    },

	animate: function() {
		var effects = new Array();
		var ul = this.toExpand.getElementsByTagName("div");
		var h = ul[0].getHeight();
		var options = {
			sync: true,
			scaleFrom: 0,
			scaleContent: false,
			transition: Effect.Transitions.sinoidal,
			scaleMode: {
				originalHeight: h,
				originalWidth: this.accordion.getWidth()
			},
			scaleX: false,
			scaleY: true
		};
		
		effects.push(new Effect.Scale(this.toExpand, 100, options));
		options = {
			sync: true,
			scaleContent: false,
			transition: Effect.Transitions.sinoidal,
			scaleX: false,
			scaleY: true
		};
		effects.push(new Effect.Scale(this.current, 0, options));
		var myDuration = 0.4;
		new Effect.Parallel(effects, {
			duration: myDuration,
				fps: 35,
				queue: {
				position: 'end',
				scope: 'accordion'
			},
			beforeStart: function() {
				this.isAnimating = true;
				this.current.previous('div.'+this.options.toggleClass).removeClassName(this.options.toggleActive);
				this.toExpand.previous('div.'+this.options.toggleClass).addClassName(this.options.toggleActive);
			}.bind(this),
			afterFinish: function() {
				var bottom=this.options.startHeight + $(this.options.accordID).getHeight();
				//alert(bottom + '-' + this.mouseY);
				if(this.mouseY > bottom)
				{
					$(this.options.accordID).setStyle({visibility:'visible'});
					$(this.options.accordID).observe('mouseover', function(ev) {
						ev.element().setStyle({visibility:''});
						ev.element().stopObserving('mouseover');
					});
				}
				if(this.current != this.toExpand)
					this.current.hide();
				this.toExpand.setStyle({ height: "" });
				this.current = this.toExpand;
				this.isAnimating = false;
			}.bind(this)
		});
	}

});


