// object
var jCalendar = {
	current: -1,
	instances : [],
	langs : {
		en: {
			months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
			monthsAbbr: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
			daysAbbr: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
			nextMonth: 'Next Month',
			prevMonth: 'Previous Month',
			nextYear: 'Next Year',
			prevYear: 'Previous Year',
			close: 'Close',
			today: 'Today'
		},
		fr: {
			months: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
			days: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
			monthsAbbr: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
			daysAbbr: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
			nextMonth: 'Mois suivant',
			prevMonth: 'Mois précédent',
			nextYear: 'Année suivante',
			prevYear: 'Année précédente',
			close: 'Fermer',
			today: "Aujourd'hui"
		}
	},
	Symbols : {
		prevMonth : '&lsaquo;',
		nextMonth : '&rsaquo;',
		prevYear : '&laquo;',
		nextYear : '&raquo;',
		close : 'X'
	}
};

// show jCalendar
jCalendar.show = function(n) {
	var instance = jCalendar.instances[n];
	if(instance.hidden && !instance.inline) {
		instance.hidden = false;
		var $input = jQuery(instance.node_input);
		var $jcal = jQuery(instance.node_jcalendar);
		jQuery('.jCalendarObject').each(function() {
			if(this != instance.node_jcalendar) {
				this.jCalendar.hide();
			}
		});
		jCalendar.current = instance.index;
		if($input.attr(instance.attribute)) {
			d = jCalendar.checkDate($input.attr(instance.attribute));
			if(d) {
				if(!jCalendar.sameDate(d, instance.date)) {
					jCalendar.setDate(d, n);
				}
			}
			else {
				jCalendar.setDate(new Date(), n, instance.notEmpty);
			}
		}
		jCalendar.fill(instance.index);
		//$jcal.css('display', 'block');
		$jcal.slideDown();
		if(instance.onShow && instance.loaded) {
			instance.onShow(instance);
		}
	}
};

// hide jCalendar
jCalendar.hide = function(n) {
	var instance = jCalendar.instances[n];
	if(!instance.hidden && !instance.inline) {
		instance.hidden = true;
		jCalendar.current = -1;
		var $jcal = jQuery(instance.node_jcalendar);
		//$jcal.css('display', 'none');
		$jcal.slideUp(200);
		if(instance.onHide && instance.loaded) {
			instance.onHide(instance);
		}
	}
};

// fill jCalendar
jCalendar.fill = function(n) {
	var instance = jCalendar.instances[n];
	var $jcal = jQuery(instance.node_jcalendar);
	var $cells = $jcal.find('.days a');
	var class_days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
	var d = new Date(instance.date.getFullYear(), instance.date.getMonth(), 1, 0, 0, 0);
	$jcal.find('div.header > span.text').html(jCalendar.formatDate(instance.date, instance.titleMask, n));
	var nb = 0, i;
	for(i=0; i<7; i++) {
		$cells.eq(i).html(instance.texts.days[(i+instance.firstDayOfWeek)%7].substr(0, 1)).attr('className', 'header');
	}
	nb = 0;
	while(d.getDay() != instance.firstDayOfWeek) { d.setDate(d.getDate()-1); }
	while(d.getMonth() != instance.date.getMonth()) {
		$cells.eq(7+nb)
			.attr('className', 'out_month '+class_days[d.getDay()])
			.html(d.getDate())
			.attr('id', 'jCalendar'+n+'_'+jCalendar.formatDate(d, 'yyyy-mm-dd', n));
		d.setDate(d.getDate()+1);
		nb++;
	}
	while(d.getMonth() == instance.date.getMonth()) {
		$cells.eq(7+nb)
			.html(d.getDate())
			.attr('id', 'jCalendar'+n+'_'+jCalendar.formatDate(d, 'yyyy-mm-dd', n));
		if(d.getDate() == instance.date.getDate() && d.getMonth() == instance.date.getMonth() && d.getFullYear() == instance.date.getFullYear()) {
			$cells.eq(7+nb).attr('className', 'in_month current '+class_days[d.getDay()]);
		}
		else {
			$cells.eq(7+nb).attr('className', 'in_month '+class_days[d.getDay()]);
		}
		d.setDate(d.getDate()+1);
		nb++;
	}
	while(nb < 42) {
		$cells.eq(7+nb)
			.attr('className', 'out_month '+class_days[d.getDay()])
			.html(d.getDate())
			.attr('id', 'jCalendar'+n+'_'+jCalendar.formatDate(d, 'yyyy-mm-dd', n));
		d.setDate(d.getDate()+1);
		nb++;
	}
	$jcal.find('a.footer').attr('id', 'jCalendar'+n+'_'+jCalendar.formatDate(new Date(), 'yyyy-mm-dd', n));
};

// format date
jCalendar.formatDate = function(mydate, format, n) {
	var instance = jCalendar.instances[n];
	var wday   = mydate.getDay();
	var year   = mydate.getFullYear();
	var day    = mydate.getDate();
	var month  = mydate.getMonth()+1;
	var hour   = mydate.getHours();
	var minute = mydate.getMinutes();
	var second = mydate.getSeconds();
	var res    = format;
	if(res.match(/yyyy/i)) { res = res.replace(/yyyy/gi, year); }
	if(res.match(/mmmm/i)) { res = res.replace(/mmmm/gi, '<>-1-<>'); }
	if(res.match(/dddd/i)) { res = res.replace(/dddd/gi, '<>-2-<>'); }
	if(res.match(/mmm/i)) { res = res.replace(/mmm/gi, '<>-3-<>'); }
	if(res.match(/ddd/i)) { res = res.replace(/ddd/gi, '<>-4-<>'); }
	if(res.match(/yy/i)) { res = res.replace(/yy/gi, Math.floor((parseInt(year)%100)/10).toString()+((parseInt(year)%100)%10)); }
	if(res.match(/mm/i)) { res = res.replace(/mm/gi, Math.floor(parseInt(month)/10).toString()+(parseInt(month)%10)); }
	if(res.match(/dd/i)) { res = res.replace(/dd/gi, Math.floor(parseInt(day)/10).toString()+(parseInt(day)%10)); }
	if(res.match(/hh/i)) { res = res.replace(/hh/gi, Math.floor(parseInt(hour)/10).toString()+(parseInt(hour)%10)); }
	if(res.match(/ii/i)) { res = res.replace(/ii/gi, Math.floor(parseInt(minute)/10).toString()+(parseInt(minute)%10)); }
	if(res.match(/ss/i)) { res = res.replace(/ss/gi, Math.floor(parseInt(second)/10).toString()+(parseInt(second)%10)); }
	if(res.match(/y/i)) { res = res.replace(/y/gi, year%100); }
	if(res.match(/m/i)) { res = res.replace(/m/gi, month); }
	if(res.match(/d/i)) { res = res.replace(/d/gi, day); }
	if(res.match(/h/i)) { res = res.replace(/h/gi, hour); }
	if(res.match(/i/i)) { res = res.replace(/i/gi, minute); }
	if(res.match(/s/i)) { res = res.replace(/s/gi, second); }
	res = res.replace(/<>-1-<>/gi, instance.texts.months[month-1]);
	res = res.replace(/<>-2-<>/gi, instance.texts.days[wday]);
	res = res.replace(/<>-3-<>/gi, instance.texts.monthsAbbr[month-1]);
	res = res.replace(/<>-4-<>/gi, instance.texts.daysAbbr[wday]);
	return res;	
};

// get nb days of the specified month of one year
jCalendar.dateNbdays = function(m, y) {
	var d = new Date(parseInt(y), parseInt(m)-1, 28, 0, 0, 0);
	var nbdays = 30;
	while(d.getMonth()+1 == m) {
		nbdays = d.getDate();
		d.setDate(nbdays+1);
	}
	return nbdays;
};

// return true if same dates
jCalendar.sameDate = function(d1, d2) {
	if(d1.getDate() == d2.getDate() && d1.getMonth() == d2.getMonth() && d1.getFullYear() == d2.getFullYear()) {
		return true;
	}
	return false;
}

// check valid date and return Date object
jCalendar.checkDate = function(text) {
	var today = new Date();
	var d = today.getDate().toString();
	var m = (today.getMonth()+1).toString();
	var y = today.getFullYear().toString();
	var tmp, nbdays;
	if(tmp = text.match(/^\s*([0-9]{4})[^0-9]([0-9]{1,2})[^0-9]([0-9]{1,2})\s*$/)) {
		d = tmp[3]; m = tmp[2]; y = tmp[1];
	}
	else if(tmp = text.match(/^\s*([0-9]{1,2})[^0-9]([0-9]{1,2})[^0-9]([0-9]{4})\s*$/)) {
		d = tmp[1]; m = tmp[2]; y = tmp[3];
	}
	else if(tmp = text.match(/^\s*([0-9]{1,2})[^0-9]([0-9]{1,2})[^0-9]([0-9]{1,2})\s*$/)) {
		d = tmp[1]; m = tmp[2]; y = tmp[3];
	}
	else {
		return false;
	}
	d = parseInt(d.replace(/[^0-9]/, '').replace(/^0+/, ''));
	m = parseInt(m.replace(/[^0-9]/, '').replace(/^0+/, ''));
	y = parseInt(y.replace(/[^0-9]/, '').replace(/^0+/, ''));
	if(m < 1 || m > 12) { return false; }
	if(y < 100) { y += y<50 ? 2000 : 1900; }
	nbdays = jCalendar.dateNbdays(m, y);
	if(d < 1 || d > nbdays) { return false; }
	var new_date = new Date(y, m-1, d);
	if(new_date.getDate() == d && new_date.getFullYear() == y && new_date.getMonth() == m-1) {
		return new_date;
	}
	else {
		return false;
	}
};

// close jCalendar if body clicked
jCalendar.checkExternalClick = function(e) {
	var n = jCalendar.current;
	if(n >= 0) {
		var target = jQuery(e.target);
		if(!e.target.jCalendar && target.parents('#jCalendarObject'+n).length == 0) {
			jCalendar.hide(n);
		}
	}
};

// create dom objects
jCalendar.create = function(n) {
	var instance = jCalendar.instances[n];
	var $input = jQuery(instance.node_input);
	var $jcal;
	var content = 
		'<div class="header">'+
			'<a class="year_prev"></a>'+
			'<a class="month_prev"></a>'+
			'<a class="close"></a>'+
			'<a class="year_next"></a>'+
			'<a class="month_next"></a>'+
			'<span class="text"></span>'+
		'</div>'+
		'<div class="days">'+
			'<a></a><a></a><a></a><a></a><a></a><a></a><a></a>'+
			'<a></a><a></a><a></a><a></a><a></a><a></a><a></a>'+
			'<a></a><a></a><a></a><a></a><a></a><a></a><a></a>'+
			'<a></a><a></a><a></a><a></a><a></a><a></a><a></a>'+
			'<a></a><a></a><a></a><a></a><a></a><a></a><a></a>'+
			'<a></a><a></a><a></a><a></a><a></a><a></a><a></a>'+
			'<a></a><a></a><a></a><a></a><a></a><a></a><a></a>'+
		'</div>'+
		'<a class="footer cell"></a>';
	
	if(instance.inline) {
		$input.html(content).addClass('jCalendarObject');
		instance.node_jcalendar = instance.node_input;
		$jcal = $input;
	}
	else {
		$input.after('<div class="jCalendarObject" id="jCalendarObject'+instance.index+'">'+content+'</div>');
		instance.node_jcalendar = document.getElementById('jCalendarObject'+instance.index);
		instance.node_jcalendar.jCalendar = jCalendar.instances[instance.index];
		$input
			.css('cursor', 'pointer')
			.click(function() {
				jCalendar.show(instance.index);
			})
			.blur(function() {
				var value = jQuery(instance.node_input).attr(instance.attribute);
				var d = jCalendar.checkDate(value);
				if(d) {
					if(!jCalendar.sameDate(d, instance.date)) {
						jCalendar.setDate(d, n);
					}
				}
				else if(instance.notEmpty) {
					jCalendar.setDate(instance.date, n);
				}
			})
			.focus(function() {
				jCalendar.show(instance.index);
			});
		$jcal = jQuery(instance.node_jcalendar);
		$jcal.css({
			'position': 'absolute',
			'z-index': 9999,
			'display': 'none'
		});
		if(instance.setPosition) {
			var pos = $input.offset();
			$jcal.css({
				'left': pos.left + instance.offset.left,
				'top': pos.top + instance.offset.top
			});
		}
	}
	
	$jcal.find('a').attr('href', 'javascript:void(0)').attr('tabindex', 30000);
	if(instance.inline) {
		$jcal.find('a.close').css('display', 'none');
		jCalendar.fill(instance.index);
	}
	else {
		$jcal.find('a.close')
			.html(jCalendar.Symbols.close)
			.attr('title', instance.texts.close)
			.click(function() {
				if(!instance.inline) {
					jCalendar.hide(instance.index);
				}
			});
	}
	
	$jcal.find('a.year_prev')
		.html(jCalendar.Symbols.prevYear)
		.attr('title', instance.texts.prevYear)
		.click(function() {
			instance.date.setFullYear(instance.date.getFullYear()-1);
			jCalendar.fill(instance.index);
		});
	$jcal.find('a.year_next')
		.html(jCalendar.Symbols.nextYear)
		.attr('title', instance.texts.nextYear)
		.click(function() {
			instance.date.setFullYear(instance.date.getFullYear()+1);
			jCalendar.fill(instance.index);
		});
	$jcal.find('a.month_prev')
		.html(jCalendar.Symbols.prevMonth)
		.attr('title', instance.texts.prevMonth)
		.click(function() {
			instance.date.setMonth(instance.date.getMonth()-1);
			jCalendar.fill(instance.index);
		});
	$jcal.find('a.month_next')
		.html(jCalendar.Symbols.nextMonth)
		.attr('title', instance.texts.nextMonth)
		.click(function() {
			instance.date.setMonth(instance.date.getMonth()+1);
			jCalendar.fill(instance.index);
		});
	$jcal.find('a.footer')
		.html(instance.texts.today);
		
	$jcal.find('.days a, a.footer').click(function() {
		var d = jCalendar.checkDate((this.id+'').replace(/^[a-z0-9]+_/i, ''));
		if(d !== false) {
			jCalendar.setDate(d, instance.index);
			instance.hide();
			if(instance.onSelect && instance.loaded) {
				instance.onSelect(instance);
			}
		}
	});
	instance.loaded = true;
};

//set date
jCalendar.setDate = function(d, n, set_input) {
	var instance = jCalendar.instances[n];
	var new_date = null;
	if(typeof(set_input) !== 'boolean') {
		set_input = true;
	}
	if(typeof(d) === 'object' && d.getFullYear()) {
		new_date = new Date(d.getFullYear(), d.getMonth(), d.getDate());
	}
	else if(typeof(d) === 'string') {
		new_date = jCalendar.checkDate(d);
	}
	if(typeof(new_date) === 'object') {
		if(set_input) {
			jQuery(instance.node_input).attr(instance.attribute, jCalendar.formatDate(new_date, instance.dateMask, n));
		}
		else {
			jQuery(instance.node_input).attr(instance.attribute, '');
		}
		instance.date = new_date;
		if(instance.inline) {
			jCalendar.fill(instance.index);
		}
		return true;
	}
	return false;
};

// jCalendar plugin
jQuery.fn.jCalendar = function(options) {
	this.each(function() {
		if(!this.jCalendar) {
			var n = jCalendar.instances.length;
			// settings
			var settings = {};
			var defaults = {
				// optionnal settings
				titleMask      : 'mmm yy',
				dateMask       : 'dd/mm/yyyy',
				attribute      : 'value',
				firstDayOfWeek : 1, // 0 = sunday, 1 = monday, .... 6 = saturday
				setOnLoad      : false,
				inline         : false,
				lang           : null,
				date           : null,
				setPosition    : true,
				notEmpty       : false,
				offset         : { top: 25, left: 0 },
				onShow         : null,
				onHide         : null,
				onSelect       : null,
				// private parameters
				texts          : null,
				hidden         : true,
				loaded         : false,
				node_jcalendar : null,
				node_input     : this,
				index          : n,
				// private functions
				show           : function() { jCalendar.show(n); },
				hide           : function() { jCalendar.hide(n); },
				getDate        : function() {
					var d = jCalendar.instances[n].date;
					return new Date(d.getFullYear(), d.getMonth(), d.getDate());
				},
				getDateFormat  : function() {
					return jCalendar.formatDate(jCalendar.instances[n].date, jCalendar.instances[n].dateMask, n);
				}
			};
			if(typeof(options) == 'object') {
				jQuery.extend(settings, defaults, options);
			}
			else {
				jQuery.extend(settings, defaults);
			}
			jCalendar.instances[n] = settings;
			// lang
			if(!settings.lang) {
				switch(navigator.language ? navigator.language : navigator.userlanguage) {
					case 'fr': settings.lang = 'fr'; break;
					default: settings.lang = 'en';
				}
			}
			switch(settings.lang) {
				case 'fr': settings.texts = jCalendar.langs.fr; break;
				default: settings.texts = jCalendar.langs.en;
			}
			// date
			if(settings.setOnLoad) {
				var d = new Date();
				jCalendar.setDate(d, n);
			}
			else if(settings.date && jCalendar.setDate(settings.date, n)) {
				// nothing to do
			}
			if(!settings.date) {
				settings.date = new Date();
			}
			// create dom
			this.jCalendar = jCalendar.instances[n];
			jCalendar.instances[n].date = new Date();
			jCalendar.create(n);
		}
	});
	return this;
};

// close jCalendar if body is clicked
jQuery(document).ready(function() {
	jQuery('body').mousedown(jCalendar.checkExternalClick);
});
