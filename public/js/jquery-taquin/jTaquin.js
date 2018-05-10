var jTaquin = {
	instances: [],
	options: [],
	lang: {
		'finish': 'Bravo, vous avez gagné',
		'restart': 'Recommencer',
		'coups': 'Coups',
		'time': 'Temps'
	},
	styles: {
		creux: {
			'border': '1px solid #222',
			'border-bottom': '1px solid #ccc',
			'border-right': '1px solid #ccc'
		},
		relief: {
			'border': '1px solid #ccc',
			'border-bottom': '1px solid #222',
			'border-right': '1px solid #222'
		},
		board: {
			'background-color': '#ccc',
			'font-family': 'Verdana, Arial',
			'font-size': '12px'
		},
		numbers: {
			'text-align': 'center',
			'font-family': 'Verdana, Arial',
			'font-size': '24px',
			'font-weight': 'bold'
		},
		empty_cell: {
			'background-color': '#ccc'
		}
	},
	create_cell: function(idx, i, opt) {
		var img;
		var div = document.createElement('div');
		var n = opt.size * opt.size;
		div.id = 'jTaquin'+idx+'_'+i;
		jQuery(div).css({
			'position': 'absolute',
			'top': Math.floor(i/opt.size)*(opt.cell_size + opt.bwidths),
			'left': (i%opt.size)*(opt.cell_size + opt.bwidths),
			'width': opt.cell_size+'px',
			'height': opt.cell_size+'px',
			'overflow': 'hidden',
			'display': 'block',
			'z-index': 9
		});
		if(i == n-1) {
			jQuery(div).css(jTaquin.styles.empty_cell).css({
				'z-index': 5
			});
		}
		jQuery(div).css(jTaquin.styles.creux);
		// events
		div.onclick = function() {
			jTaquin.click(idx, jTaquin.getIndex(idx, i));
		};
		div.onmouseover = function() {
			var instance = jTaquin.instances[idx];
			var index = jTaquin.getIndex(idx, i);
			if(!instance.finish && jTaquin.is_near(instance.empty, index, opt.size)) {
				div.style.cursor = 'pointer';
			}
			else {
				div.style.cursor = 'default';
			}
		};
		div.onselectstart = function() {
			return false;
		};
		// si numeros
		if(opt.numbers && i<n-1) {
			div.innerHTML = '<table cellpadding="0" cellspacing="0"><tr><td>'+i+'</td></tr></table>';
			jQuery(div).find('table').css({
				'position': 'absolute',
				'top': 0,
				'left': 0,
				'border': '0px',
				'width': '100%',
				'height': '100%'
			});
			jQuery(div).find('td').css(jTaquin.styles.numbers);
		}
		// si image
		if(opt.image && i<n-1) {
			img = document.createElement('img');
			img.id = 'jTaquin'+idx+'_img_'+i;
			img.src = opt.image;
			jQuery(img).css({
				'position': 'absolute',
				'top': -Math.floor(i/opt.size)*opt.cell_size,
				'left': -(i%opt.size)*opt.cell_size,
				'width': opt.width+'px',
				'height': opt.width+'px'
			});
			div.appendChild(img);
		}
		return div;
	},
	is_near: function(i_empty, i, size) {
		var dxy = jTaquin.get_dxy(i_empty, i, size);
		if(dxy.dy == 0 && (dxy.dx == 1 || dxy.dx == -1)) {
			return dxy;
		}
		if(dxy.dx == 0 && (dxy.dy == 1 || dxy.dy == -1)) {
			return dxy;
		}
		return false;
	},
	getIndex: function(idx, i) {
		var cells = jTaquin.instances[idx].cells;
		var n = cells.length;
		var index;
		for(index=0; index<n; index++) {
			if(cells[index] == i) {
				return index;
			}
		}
		return false;
	},
	getCell: function(idx, i) {
		return document.getElementById('jTaquin'+idx+'_'+i);
	},
	isFinish: function(idx) {
		var cells = jTaquin.instances[idx].cells;
		var n = cells.length;
		var i;
		for(i=0; i<n; i++) {
			if(cells[i] != i) {
				return false;
			}
		}
		return true;
	},
	click: function(idx, i) {
		var instance = jTaquin.instances[idx];
		var options = jTaquin.options[idx];
		var dxy = jTaquin.is_near(instance.empty, i, options.size);
		var cells = instance.cells;
		var cell = jTaquin.getCell(idx, cells[i]);
		var new_cell, save;
		var new_i = i;
		if(dxy && !instance.finish) {
			if(!instance.shuffle && instance.coups.length == 0) {
				instance.start = new Date();
			}
			if(dxy.dx != 0) {
				new_i = i - dxy.dx;
			}
			else {
				new_i = i - dxy.dy*options.size;
			}
			if(new_i == instance.empty) {
				if(!instance.shuffle) {
					instance.coups.push(new_i);
				}
				
				new_cell = jTaquin.getCell(idx, cells[new_i]);
				
				var pos = {top: cell.style.top, left: cell.style.left};
				var pos_new = {top: new_cell.style.top, left: new_cell.style.left};
				if(!instance.shuffle) {
					jQuery(new_cell).css(pos);
					jQuery(cell).animate(pos_new, 150, 'linear', function() {
						if(!instance.shuffle && jTaquin.isFinish(idx)) {
							alert(jTaquin.lang.finish);
						}
					});
				}
				else {
					jQuery(new_cell).css(pos);
					jQuery(cell).css(pos_new);
				}
				
				save = cells[new_i];
				cells[new_i] = cells[i];
				cells[i] = save;
				
				instance.empty = i;
			}
			if(!instance.shuffle && jTaquin.isFinish(idx)) {
				window.clearInterval(instance.interval);
				if(options.onFinish) {
					options.onFinish();
				}
				instance.finish = true;
			}
		}
	},
	shuffle: function(idx) {
		var instance = jTaquin.instances[idx];
		var options = jTaquin.options[idx];
		var cells = instance.cells;
		var n = options.size*options.size;
		var i, rand, empty;
		for(i=0; i<options.size; i++) {
			jTaquin.shuffle_snake(idx);
		}
		i = 0;
		while(i < 30*n) {
			empty = instance.empty;
			rand = Math.floor(Math.random()*4);
			switch(rand) {
				case 0: empty--; break;
				case 1: empty++; break;
				case 2: empty -= options.size; break;
				case 3: empty += options.size; break;
			}
			if(empty >= 0 && empty < n) {
				jTaquin.click(idx, empty);
				i++;
			}
		}
		jTaquin.shuffle_end(idx);
	},
	shuffle_snake: function(idx) {
		var instance = jTaquin.instances[idx];
		var options = jTaquin.options[idx];
		var i, k;
		var empty = instance.empty;
		var sens = -1;
		for(i=0; i<options.size; i++) {
			for(k=0; k<options.size-1; k++) {
				empty += sens;
				jTaquin.click(idx, empty);
			}
			if(i < options.size-1) {
				sens = -sens;
				empty -= 4;
				jTaquin.click(idx, empty);
			}
		}
		jTaquin.shuffle_end(idx);
	},
	shuffle_end: function(idx) {
		var instance = jTaquin.instances[idx];
		var options = jTaquin.options[idx];
		var empty, pt;
		empty = instance.empty;
		pt = jTaquin.get_xy(empty, options.size);
		if(options.size%2 == 0) {
			while(pt.x < options.size - 1) {
				pt.x++;
				empty++;
				jTaquin.click(idx, empty);
			}
		}
		while(pt.y < options.size - 1) {
			pt.y++;
			empty += options.size;
			jTaquin.click(idx, empty);
		}
		if(options.size%2 != 0) {
			while(pt.x < options.size - 1) {
				pt.x++;
				empty++;
				jTaquin.click(idx, empty);
			}
		}
	},
	get_xy: function(i, size) {
		return { x: i%size, y: Math.floor(i/size) };
	},
	get_dxy: function(i_empty, i, size) {
		var y_empty = Math.floor(i_empty/size);
		var x_empty = i_empty%size;
		var y = Math.floor(i/size);
		var x = i%size;
		return { dx: x - x_empty, dy: y - y_empty };
	},
	init: function(taquin, options, idx) {
		var i, n, opt, divs;
		// init
		jTaquin.options[idx] = {};
		jTaquin.instances[idx] = {};
		
		// options
		opt = {
			width: 400,
			size: 4,
			image: null,
			numbers: true,
			onFinish: null,
			help: true,
			help_size: 150,
			board_height: 70
		};
		if(typeof(options) == 'object') {
			for(i in options) {
				opt[i] = options[i];
			}
		}
		
		// others options
		if(opt.image && !options.numbers) {
			opt.numbers = false;
		}
		
		if(jQuery.browser.msie) {
			opt.bwidths = 0;
		}
		else {
			opt.bwidths = 2;
		}
		
		if(opt.size < 2) {
			opt.size = 2;
		}
		else if(opt.size > 10) {
			opt.size = 10;
		}
		
		// instance
		n = opt.size * opt.size;
		jTaquin.instances[idx] = {
			coups: [],
			empty: n-1,
			cells: [],
			interval: null,
			board: null,
			start: null,
			shuffle: true,
			finish: false,
			taquin: taquin,
			dom_coups: null,
			dom_time: null
		}
		
		// cree les cases
		opt.cell_size = Math.floor(opt.width/opt.size);
		opt.taquin_size = (opt.cell_size+opt.bwidths)*opt.size+2-opt.bwidths;
		taquin.innerHTML = '';
		jQuery(taquin).css({
			'position': 'relative',
			'width': opt.taquin_size+'px',
			'height': opt.taquin_size+'px'
		}).css(jTaquin.styles.empty_cell);
		jQuery(taquin).css(jTaquin.styles.relief);
		divs = [];
		for(i=0; i<n; i++) {
			divs.push(jTaquin.create_cell(idx, i, opt));
			jTaquin.instances[idx].cells.push(i);
		}
		
		// store options
		jTaquin.options[idx] = opt;
		
		// fill
		taquin.innerHTML = '';
		if(opt.help && opt.image) {
			var help = document.createElement('div');
			jQuery(help).css({
				'position': 'absolute',
				'top': opt.board_height+4,
				'left': opt.taquin_size+4,
				'width': opt.help_size+'px',
				'height': opt.help_size+'px',
				'overflow': 'hidden'
			});
			jQuery(help).css(jTaquin.styles.relief);
			var help_img = document.createElement('img');
			help_img.src = opt.image;
			jQuery(help_img).css({
				'width': (opt.help_size-4+opt.bwidths)+'px',
				'height': (opt.help_size-4+opt.bwidths)+'px',
				'overflow': 'hidden'
			});
			jQuery(help_img).css(jTaquin.styles.creux);
			help.appendChild(help_img);
			taquin.appendChild(help);
		}
		
		// board
		var board = document.createElement('div');
		jQuery(board).css({
			'position': 'absolute',
			'top': -1,
			'left': opt.taquin_size+4,
			'width': opt.help_size+'px',
			'height': opt.board_height+'px',
			'overflow': 'hidden'
		});
		jQuery(board).css(jTaquin.styles.relief);
		var board_div = document.createElement('div');
		jQuery(board_div).css({
			'width': (opt.help_size-2)+'px',
			'height': (opt.board_height-2)+'px',
			'overflow': 'hidden'
		});
		jQuery(board_div).css(jTaquin.styles.creux);
		jQuery(board_div).css(jTaquin.styles.board);
		board_div.innerHTML = '<div>'+jTaquin.lang.coups+': <strong>0</strong></div><div>'+jTaquin.lang.time+': <strong>0</strong> s</div><div style="text-align:center"><button onclick="jTaquin.reset('+idx+');">'+jTaquin.lang.restart+'</button></div>';
		jQuery(board_div).find('div').css({'padding':'2px'});
		jTaquin.instances[idx].dom_coups = jQuery(board_div).find('strong').get(0);
		jTaquin.instances[idx].dom_time = jQuery(board_div).find('strong').get(1);
		board.appendChild(board_div);
		taquin.appendChild(board);
		jTaquin.instances[idx].board = board_div;
		
		// cells
		for(i=0; i<n; i++) {
			taquin.appendChild(divs[i]);
		}
		
		// shuffle
		jTaquin.shuffle(idx);
		
		jTaquin.instances[idx].shuffle = false;
		jTaquin.instances[idx].interval = window.setInterval(function() {
			var instance = jTaquin.instances[idx];
			if(instance.board) {
				var n = jTaquin.instances[idx].coups.length;
				var d = new Date();
				instance.dom_coups.innerHTML = n;
				if(jTaquin.instances[idx].start) {
					d = Math.round((d-jTaquin.instances[idx].start)/100)/10;
					if(Math.floor(d) == d) {
						d = d+'.0';
					}
					instance.dom_time.innerHTML = d;
				}
			}
		}, 100);
	},
	reset: function(idx) {
		var instance = jTaquin.instances[idx];
		var options = jTaquin.options[idx];
		jTaquin.stop(idx);
		jTaquin.init(instance.taquin, options, idx);
		return true;
	},
	stop: function(idx) {
		var interval = jTaquin.instances[idx].interval;
		if(interval) {
			window.clearInterval(interval);
		}
		jTaquin.options[idx] = {};
		jTaquin.instances[idx] = {};
	}
};
/******************************************************************************/
jQuery.fn.Taquin = function(options) {
	return this.each(function() {
		jTaquin.init(this, options, jTaquin.instances.length);
	});
};
