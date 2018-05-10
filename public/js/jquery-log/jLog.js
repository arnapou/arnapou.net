/*********************************************************************************
# jLog - Open Source Javascript script based on jQuery Javascript Library
# 
# Author  : Arnaud BUATHIER
# Web     : http://arnapou.net
# Version : 1.0 (2008-10-23)
#
# Copyright (c) 2008 Arnaud BUATHIER
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.
*********************************************************************************/
var jLog = {
	obj: null,
	body: null,
	header: null,
	log: null,
	start_time: null,
	position: 0,
	times: [],
	filters: [],
	large: false,
	add: function(cl, ch, msg) {
		var i, t, m, f, s, li;
		t = ((new Date()).getTime() - jLog.start_time)/1000;
		i = Math.floor(t);
		m = Math.floor(i/60);
		s = i%60;
		f = Math.floor(1000*(t - i));
		if(m < 10) { m = '0'+m; }
		if(s < 10) { s = '0'+s; }
		if(f < 10) { f = '00'+f; } else if(f < 100) { f = '0'+f; }
		li = document.createElement('li');
		li.className = cl;
		li.innerHTML = '<span class="'+cl+'">'+ch+'</span><span class="jLog_Time">'+m+':'+s+'.'+f+'</span>'+msg;
		jLog.log.appendChild(li);
		jLog.body.scrollTop = jLog.body.scrollHeight;
	},
	toggle: function() {
		if(jLog.obj.css('display') == 'block') {
			jLog.hide();
		}
		else {
			jLog.show();
		}
	},
	onresize: function() {
		jLog.obj.find('.jLogBody').height(jLog.obj.height());
	},
	show: function() {
		jLog.obj.css('display', 'block');
		jLog.onresize();
	},
	hide: function() {
		jLog.obj.css('display', 'none');
	},
	move: function(position) {
		if(typeof(position) != 'number') {
			jLog.position = (jLog.position + 1)%4;
		}
		else {
			jLog.position = position%4;
		}
		var large = '';
		if(jLog.large) {
			large = ' large';
		}
		switch(jLog.position) {
			case 1:
				jLog.obj.attr('className', 'top_right'+large);
				jLog.header.find('span.jLog_Move').html('&darr;');
				break;
			case 2:
				jLog.obj.attr('className', 'bottom_right'+large);
				jLog.header.find('span.jLog_Move').html('&larr;');
				break;
			case 3:
				jLog.obj.attr('className', 'bottom_left'+large);
				jLog.header.find('span.jLog_Move').html('&uarr;');
				break;
			default:
				jLog.obj.attr('className', 'top_left'+large);
				jLog.header.find('span.jLog_Move').html('&rarr;');
				jLog.position = 0;
				break;
		}
	},
	resize: function() {
		if(jLog.large) {
			jLog.obj.removeClass('large');
			jLog.header.find('span.jLog_Resize').html('&or;');
		}
		else {
			jLog.obj.addClass('large');
			jLog.header.find('span.jLog_Resize').html('&and;');
		}
		jLog.large = !jLog.large;
		jLog.onresize();
	},
	onkeyup: function(e) {
		if(!e) {
			e = window.event;
		}
		if(e && e.keyCode == 113) { // F2 key
			if(e.shiftKey && !e.ctrlKey && !e.altKey) {
				jLog.move();
			}
			else if(!e.shiftKey && e.ctrlKey && !e.altKey) {
				if(jLog.large) {
					jLog.resize();
					jLog.hide_times();
				}
				else {
					jLog.resize();
					jLog.show_times();
				}
			}
			else if(e.shiftKey && e.ctrlKey && !e.altKey) {
				jLog.clear();
			}
			else {
				jLog.toggle();
			}
		}
	},
	init: function() {
		if(jLog.obj) {
			return false;
		}
		jQuery('body').append('<div id="jLog" style="display:none" class="top_left"><div class="jLogBgBody"></div><div class="jLogHeader"><div class="jLogHeaderLeft"><span class="jLog_Debug jLog_Filter" title="Debug">&radic;</span><span class="jLog_Info jLog_Filter" title="Info">i</span><span class="jLog_Warn jLog_Filter" title="Warning">w</span><span class="jLog_Error jLog_Filter" title="Error">!</span><span class="jLog_Timer jLog_Filter" title="Timer">t</span></div><div class="jLogHeaderRight"><span class="jLog_Close" title="Hide (F2 key)">X</span><span class="jLog_ShowTimes jLog_Filter" title="Show/Hide Times">@</span><span class="jLog_Clear" title="Clear (Alt+F2 Key)">&Oslash;</span><span class="jLog_Move" title="Move (Shift+F2 key)">&rarr;</span><span class="jLog_Resize" title="Resize">&or;</span></div></div><div class="jLogBody"><ul></ul></div></div>');
		jLog.obj = jQuery('#jLog');
		jLog.obj.find('.jLogBgBody').css('opacity', 0.9);
		jLog.log = jLog.obj.find('ul').get(0);
		jLog.body = jLog.obj.find('.jLogBody').get(0);
		jLog.header = jLog.obj.find('.jLogHeader').eq(0);
		jLog.header.find('span').css('cursor', 'pointer').hover(jLog.button_over,jLog.button_out);
		jLog.header.find('span.jLog_Filter').click(jLog.filter_click);
		jLog.header.find('span.jLog_Move').click(jLog.move);
		jLog.header.find('span.jLog_Clear').click(jLog.clear);
		jLog.header.find('span.jLog_Resize').click(jLog.resize);
		jLog.header.find('span.jLog_Close').click(jLog.hide);
		jLog.onresize();
		jLog.start_time = (new Date()).getTime();
		jQuery(document).keyup(jLog.onkeyup);
		return true;
	},
	show_times: function() {
		var obj = jLog.header.find('span.jLog_ShowTimes').get(0);
		obj.className = 'jLog_ShowTimes';
		jLog.apply_filter(obj);
	},
	hide_times: function() {
		var obj = jLog.header.find('span.jLog_ShowTimes').get(0);
		obj.className = 'jLog_ShowTimes jLog_Filter_Selected';
		jLog.apply_filter(obj);
	},
	toggle_times: function() {
		jLog.apply_filter(jLog.header.find('span.jLog_ShowTimes').get(0));
	},
	button_over: function() {
		var cl = (this.className.split(' '))[0];
		jQuery(this).addClass(cl+'_Over');
	},
	button_out: function() {
		var cl = (this.className.split(' '))[0];
		jQuery(this).removeClass(cl+'_Over');
	},
	filter_click: function() {
		jLog.apply_filter(this);
	},
	toggle_filter: function(type) {
		var cl = null;
		switch(type) {
			case 'debug': cl = 'jLog_Debug'; break;
			case 'info': cl = 'jLog_Info'; break;
			case 'warn': cl = 'jLog_Warn'; break;
			case 'error': cl = 'jLog_Error'; break;
			case 'timer': cl = 'jLog_Timer'; break;
		}
		if(cl) {
			var obj = jLog.header.find('span.'+cl).get(0);
			jLog.apply_filter(obj);
		}
	},
	filter: function(type, val) {
		var cl = null;
		switch(type) {
			case 'debug': cl = 'jLog_Debug'; break;
			case 'info': cl = 'jLog_Info'; break;
			case 'warn': cl = 'jLog_Warn'; break;
			case 'error': cl = 'jLog_Error'; break;
			case 'timer': cl = 'jLog_Timer'; break;
		}
		if(cl) {
			var obj = jLog.header.find('span.'+cl).get(0);
			if(val) {
				obj.className = cl;
			}
			else {
				obj.className = cl+' jLog_Filter_Selected';
			}
			jLog.apply_filter(obj);
		}
	},
	apply_filter: function(obj) {
		var cl = (obj.className.split(' '))[0];
		var filters = [], i;
		if(obj.className.match(/jLog_Filter_Selected/i)) {
			for(i in jLog.filters) {
				if(jLog.filters[i] != cl) {
					filters.push(jLog.filters[i]);
				}
			}
			jLog.filters = filters;
			jQuery(obj).removeClass('jLog_Filter_Selected');
		}
		else {
			jLog.filters.push(cl);
			jQuery(obj).addClass('jLog_Filter_Selected');
		}
		jLog.log.className = jLog.filters.join(' ');
	},
	clear: function(msg) {
		jLog.log.innerHTML = '';
	},
	debug: function(msg) {
		jLog.add('jLog_Debug', '&radic;', msg);
	},
	info: function(msg) {
		jLog.add('jLog_Info', 'i', msg);
	},
	warn: function(msg) {
		jLog.add('jLog_Warn', 'w', msg);
	},
	error: function(msg) {
		jLog.add('jLog_Error', '!', msg);
	},
	timer: function(key, msg) {
		if(jLog.times[key]) {
			var duree = (new Date()).getTime() - jLog.times[key];
			jLog.times[key] = 0;
			if(msg) {
				jLog.add('jLog_Timer', 't', msg+' ('+duree+' ms)');
			}
			else {
				jLog.add('jLog_Timer', 't', key+' ('+duree+' ms)');
			}
		}
		else {
			jLog.times[key] = (new Date()).getTime();
		}
	}
};

jQuery(document).ready(function() {
	jLog.init();
});
