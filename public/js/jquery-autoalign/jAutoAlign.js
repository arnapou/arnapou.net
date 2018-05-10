// 6be58f48a0511d90b4859bb62140f1a5d21809442514309202502694f179e00.js
jQuery.fn.border_left_width=function(){return parseInt(jQuery(this).css('border-left-width').toString().replace(/[^0-9-]/g,'').replace(/^jQuery/,'0'));}
jQuery.fn.border_right_width=function(){return parseInt(jQuery(this).css('border-right-width').toString().replace(/[^0-9-]/g,'').replace(/^jQuery/,'0'));}
jQuery.fn.border_top_width=function(){return parseInt(jQuery(this).css('border-top-width').toString().replace(/[^0-9-]/g,'').replace(/^jQuery/,'0'));}
jQuery.fn.border_bottom_width=function(){return parseInt(jQuery(this).css('border-bottom-width').toString().replace(/[^0-9-]/g,'').replace(/^jQuery/,'0'));}
jQuery.fn.padding_left=function(){return parseInt(jQuery(this).css('padding-left').toString().replace(/[^0-9-]/g,'').replace(/^jQuery/,'0'));}
jQuery.fn.padding_right=function(){return parseInt(jQuery(this).css('padding-right').toString().replace(/[^0-9-]/g,'').replace(/^jQuery/,'0'));}
jQuery.fn.padding_top=function(){return parseInt(jQuery(this).css('padding-top').toString().replace(/[^0-9-]/g,'').replace(/^jQuery/,'0'));}
jQuery.fn.padding_bottom=function(){return parseInt(jQuery(this).css('padding-bottom').toString().replace(/[^0-9-]/g,'').replace(/^jQuery/,'0'));}
jQuery.fn.same_height=function(){var max=0;this.each(function(){var h=jQuery(this).height();if(h>max){max=h;}});return this.css('height',max+'px');};jQuery.fn.same_width=function(){var max=0;this.each(function(){var w=jQuery(this).width();if(w>max){max=w;}});return this.css('width',max+'px');};jQuery.fn.adjust_width=function(childs,padding,margin){if(!padding){padding=0;}
if(!margin){margin=0;}
return this.each(function(){var parent=jQuery(this);var enfants=parent.find(childs);var max=parent.width();var n=enfants.length;enfants.each(function(){var child=jQuery(this);var w=parseInt(max/n)
w-=2*padding;w-=2*margin;w-=child.border_left_width()+child.border_right_width();child.css({'width':w+'px','padding':padding+'px','margin':margin+'px'});});});};jQuery.fn.adjust_spacing_horiz=function(childs){return this.each(function(){var parent=jQuery(this);var enfants=parent.find(childs);var max=parent.width();var n=enfants.length;var w=0;enfants.each(function(){var child=jQuery(this);w+=child.width();w+=child.border_left_width()+child.border_right_width();w+=child.padding_left()+child.padding_right();});if(w<max&&n>1){var m=parseInt((max-w)/(n-1)/2);enfants.each(function(i){if(i>0){jQuery(this).css('margin-left',m+'px');}
if(i<n-1){jQuery(this).css('margin-right',m+'px');}});}});};jQuery.fn.center=function(){return this.each(function(){var moi=jQuery(this);var pw=moi.parent().width();var ph=moi.parent().height();var w=moi.width()+moi.border_left_width()+moi.border_right_width()+moi.padding_left()+moi.padding_right();var h=moi.height()+moi.border_top_width()+moi.border_bottom_width()+moi.padding_top()+moi.padding_bottom();var mw=parseInt((pw-w)/2);var mh=parseInt((ph-h)/2);if(mw>0){moi.css({'margin-left':mw+'px','margin-right':mw+'px'});}
if(mh>0){moi.css({'margin-top':mh+'px','margin-bottom':mh+'px'});}});};

