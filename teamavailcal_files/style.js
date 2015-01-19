document.observe("dom:loaded", function(){
		$$('.errorbox').each(function(el) {
			el.appear({duration:3.0});
		});

	$$(".confirm-tip").each( function(link) {
		new Tooltip(link, {opacity:".9", textColor:"#3E6D01", textShadowColor: "#ABF74A", borderColor:"#3E6D01", backgroundColor:"#ABF74A" });
	});
	$$(".warning-tip").each( function(input) {
		new Tooltip(input, {backgroundColor: "#FC9", borderColor: "#D67109",
		textColor: "#D67109", textShadowColor: "#FC9", opacity:".9"});
	});
});

function pulseThis(e)
{
	Event.stopObserving(this, 'mouseover', pulseThis);
	var ec='';
	var check4color=this;
	while(ec=='')
	{
		if(check4color.getStyle('background-color') != 'transparent')
		{
			ec=check4color.getStyle('background-color');
			break;
		}
		check4color=check4color.up();
	}
	var endcolor=colorToHex(ec);
	var h=this.height;
	var w=this.width;
	new Effect.Highlight(this, {startcolor:'#ff0000', endcolor: endcolor});
	new Effect.Scale(this,125, {
		duration: 0.5,
		afterFinish: function(effect) {
			new Effect.Scale(effect.element, 100, {
				scaleMode: {originalHeight: h, originalWidth: w},
				afterFinish: function(effect2) {
					Event.observe(effect2.element,'mouseover', pulseThis);
				}
			});
		}
	});
}



function colorToHex(color) {
    if (color.substr(0, 1) === '#') {
        return color;
    }
    var digits = /(.*?)rgb\((\d+), (\d+), (\d+)\)/.exec(color);
    
    var red = parseInt(digits[2]);
    var green = parseInt(digits[3]);
    var blue = parseInt(digits[4]);
    
    var rgb = blue | (green << 8) | (red << 16);
    return digits[1] + '#' + rgb.toString(16);
};
