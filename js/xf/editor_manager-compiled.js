/*
 * XenForo dragula.min.js
 * Copyright 2010-2018 XenForo Ltd.
 * Released under the XenForo License Agreement: https://xenforo.com/license-agreement
 */
(function(L){"object"===typeof exports&&"undefined"!==typeof module?module.exports=L():"function"===typeof define&&define.amd?define([],L):("undefined"!==typeof window?window:"undefined"!==typeof global?global:"undefined"!==typeof self?self:this).dragula=L()})(function(){return function d(l,k,m){function e(b,x){if(!k[b]){if(!l[b]){var A="function"==typeof require&&require;if(!x&&A)return A(b,!0);if(a)return a(b,!0);x=Error("Cannot find module '"+b+"'");throw x.code="MODULE_NOT_FOUND",x;}x=k[b]={exports:{}};
l[b][0].call(x.exports,function(a){var d=l[b][1][a];return e(d?d:a)},x,x.exports,d,l,k,m)}return k[b].exports}for(var a="function"==typeof require&&require,b=0;b<m.length;b++)e(m[b]);return e}({1:[function(l,k,m){function d(a){var b=e[a];b?b.lastIndex=0:e[a]=b=new RegExp("(?:^|\\s)"+a+"(?:\\s|$)","g");return b}var e={};k.exports={add:function(a,b){var e=a.className;e.length?d(b).test(e)||(a.className+=" "+b):a.className=b},rm:function(a,b){a.className=a.className.replace(d(b)," ").trim()}}},{}],2:[function(l,
k,m){(function(d){function e(h,a,b,f){var e={mouseup:"touchend",mousedown:"touchstart",mousemove:"touchmove"},y={mouseup:"pointerup",mousedown:"pointerdown",mousemove:"pointermove"},M={mouseup:"MSPointerUp",mousedown:"MSPointerDown",mousemove:"MSPointerMove"};if(d.navigator.pointerEnabled)D[a](h,y[b],f);else if(d.navigator.msPointerEnabled)D[a](h,M[b],f);else D[a](h,e[b],f),D[a](h,b,f)}function a(h){if(void 0!==h.touches)return h.touches.length;if(void 0!==h.which&&0!==h.which)return h.which;if(void 0!==
h.buttons)return h.buttons;h=h.button;if(void 0!==h)return h&1?1:h&2?3:h&4?2:0}function b(h,a){return"undefined"!==typeof d[a]?d[a]:y.clientHeight?y[h]:E.body[h]}function A(h,a,b){h=h||{};var f=h.className;h.className+=" gu-hide";a=E.elementFromPoint(a,b);h.className=f;return a}function x(){return!1}function K(){return!0}function v(a){return a.parentNode===E?null:a.parentNode}function C(a){return"INPUT"===a.tagName||"TEXTAREA"===a.tagName||"SELECT"===a.tagName||m(a)}function m(a){return a&&"false"!==
a.contentEditable?"true"===a.contentEditable?!0:m(v(a)):!1}function z(a){var b;if(!(b=a.nextElementSibling)){do a=a.nextSibling;while(a&&1!==a.nodeType);b=a}return b}function w(a,b){b=b.targetTouches&&b.targetTouches.length?b.targetTouches[0]:b.changedTouches&&b.changedTouches.length?b.changedTouches[0]:b;var f={pageX:"clientX",pageY:"clientY"};a in f&&!(a in b)&&f[a]in b&&(a=f[a]);return b[a]}var M=l("contra/emitter"),D=l("crossvent"),f=l("./classes"),E=document,y=E.documentElement;k.exports=function(d,
l){function h(c){return-1!==p.containers.indexOf(c)||g.isContainer(c)}function k(c){c=c?"remove":"add";e(y,c,"mousedown",ha);e(y,c,"mouseup",N)}function m(c){e(y,c?"remove":"add","mousemove",ia)}function F(c){c=c?"remove":"add";D[c](y,"selectstart",T);D[c](y,"click",T)}function T(c){G&&c.preventDefault()}function ha(c){U=c.clientX;V=c.clientY;if(1===a(c)&&!c.metaKey&&!c.ctrlKey){var n=c.target,b=O(n);b&&(G=b,m(),"mousedown"===c.type&&(C(n)?n.focus():c.preventDefault()))}}function ia(c){if(G)if(0===
a(c))N({});else if(void 0===c.clientX||c.clientX!==U||void 0===c.clientY||c.clientY!==V){if(g.ignoreInputTextSelection){var n=w("clientX",c);var H=w("clientY",c);n=E.elementFromPoint(n,H);if(C(n))return}n=G;m(!0);F();W();X(n);H=u.getBoundingClientRect();n=H.left+b("scrollLeft","pageXOffset");H=H.top+b("scrollTop","pageYOffset");Y=w("pageX",c)-n;Z=w("pageY",c)-H;f.add(r||u,"gu-transit");t||(n=u.getBoundingClientRect(),t=u.cloneNode(!0),t.style.width=(n.width||n.right-n.left)+"px",t.style.height=(n.height||
n.bottom-n.top)+"px",f.rm(t,"gu-transit"),f.add(t,"gu-mirror"),g.mirrorContainer.appendChild(t),e(y,"add","mousemove",P),f.add(g.mirrorContainer,"gu-unselectable"),p.emit("cloned",t,u,"mirror"));P(c)}}function O(c){if(!(p.dragging&&t||h(c))){for(var a=c;v(c)&&!1===h(v(c));){if(g.invalid(c,a))return;c=v(c);if(!c)return}var b=v(c);if(b&&!g.invalid(c,a)&&g.moves(c,b,a,z(c)))return{item:c,source:b}}}function X(c){if("boolean"===typeof g.copy?g.copy:g.copy(c.item,c.source))r=c.item.cloneNode(!0),p.emit("cloned",
r,c.item,"copy");q=c.source;u=c.item;J=I=z(c.item);p.dragging=!0;p.emit("drag",u,q)}function ja(){return!1}function W(){if(p.dragging){var c=r||u;aa(c,v(c))}}function N(c){G=!1;m(!0);F(!0);if(p.dragging){var a=r||u,b=w("clientX",c);c=w("clientY",c);var f=A(t,b,c);(b=ba(f,b,c))&&(r&&g.copySortSource||!r||b!==q)?aa(a,b):g.removeOnSpill?ca():da()}}function aa(c,a){var b=v(c);r&&g.copySortSource&&a===q&&b.removeChild(u);Q(a)?p.emit("cancel",c,q,q):p.emit("drop",c,a,q,I);R()}function ca(){if(p.dragging){var c=
r||u,a=v(c);a&&a.removeChild(c);p.emit(r?"cancel":"remove",c,a,q);R()}}function da(c){if(p.dragging){var a=0<arguments.length?c:g.revertOnSpill,b=r||u,f=v(b),d=Q(f);!1===d&&a&&(r?f&&f.removeChild(r):q.insertBefore(b,J));d||a?p.emit("cancel",b,q,q):p.emit("drop",b,f,q,I);R()}}function R(){var c=r||u;G=!1;m(!0);F(!0);t&&(f.rm(g.mirrorContainer,"gu-unselectable"),e(y,"remove","mousemove",P),v(t).removeChild(t),t=null);c&&f.rm(c,"gu-transit");S&&clearTimeout(S);p.dragging=!1;B&&p.emit("out",c,B,q);p.emit("dragend",
c);q=u=r=J=I=S=B=null}function Q(c,a){a=void 0!==a?a:t?I:z(r||u);return c===q&&a===J}function ba(c,a,b){function f(){if(!1===h(n))return!1;var f=ea(n,c);f=fa(n,f,a,b);return Q(n,f)?!0:g.accepts(u,n,q,f)}for(var n=c;n&&!f();)n=v(n);return n}function P(a){if(t){a.preventDefault();var c=w("clientX",a);a=w("clientY",a);var b=a-Z;t.style.left=c-Y+"px";t.style.top=b+"px";b=r||u;var f=A(t,c,a),d=ba(f,c,a),h=null!==d&&d!==B;if(h||null===d)B&&p.emit("out",b,B,q),B=d,h&&p.emit("over",b,B,q);var e=v(b);if(d===
q&&r&&!g.copySortSource)e&&e.removeChild(b);else{f=ea(d,f);if(null!==f)c=fa(d,f,c,a);else{if(!0!==g.revertOnSpill||r){r&&e&&e.removeChild(b);return}c=J;d=q}if(null===c&&h||c!==b&&c!==z(b))I=c,d.insertBefore(b,c),p.emit("shadow",b,d,q)}}}function ka(a){f.rm(a,"gu-hide")}function la(a){p.dragging&&f.add(a,"gu-hide")}function ea(a,b){for(;b!==a&&v(b)!==a;)b=v(b);return b===y?null:b}function fa(a,b,f,d){function c(){var b=a.children.length,c;for(c=0;c<b;c++){var g=a.children[c];var e=g.getBoundingClientRect();
if(h&&e.left+e.width/2>f||!h&&e.top+e.height/2>d)return g}return null}function e(){var a=b.getBoundingClientRect();return h?f>a.left+(a.width||a.right-a.left)/2?z(b):b:d>a.top+(a.height||a.bottom-a.top)/2?z(b):b}var h="horizontal"===g.direction;return b!==a?e():c()}1===arguments.length&&!1===Array.isArray(d)&&(l=d,d=[]);var t,q,u,Y,Z,U,V,J,I,r,S,B=null,G,g=l||{};void 0===g.moves&&(g.moves=K);void 0===g.accepts&&(g.accepts=K);void 0===g.invalid&&(g.invalid=ja);void 0===g.containers&&(g.containers=
d||[]);void 0===g.isContainer&&(g.isContainer=x);void 0===g.copy&&(g.copy=!1);void 0===g.copySortSource&&(g.copySortSource=!1);void 0===g.revertOnSpill&&(g.revertOnSpill=!1);void 0===g.removeOnSpill&&(g.removeOnSpill=!1);void 0===g.direction&&(g.direction="vertical");void 0===g.ignoreInputTextSelection&&(g.ignoreInputTextSelection=!0);void 0===g.mirrorContainer&&(g.mirrorContainer=E.body);var p=M({containers:g.containers,start:function(a){(a=O(a))&&X(a)},end:W,cancel:da,remove:ca,destroy:function(){k(!0);
N({})},canMove:function(a){return!!O(a)},dragging:!1});if(!0===g.removeOnSpill)p.on("over",ka).on("out",la);k();return p}}).call(this,"undefined"!==typeof global?global:"undefined"!==typeof self?self:"undefined"!==typeof window?window:{})},{"./classes":1,"contra/emitter":5,crossvent:6}],3:[function(l,k,m){k.exports=function(d,e){return Array.prototype.slice.call(d,e)}},{}],4:[function(l,k,m){var d=l("ticky");k.exports=function(e,a,b){e&&d(function(){e.apply(b||null,a||[])})}},{ticky:9}],5:[function(l,
k,m){var d=l("atoa"),e=l("./debounce");k.exports=function(a,b){var l=b||{},k={};void 0===a&&(a={});a.on=function(b,d){k[b]?k[b].push(d):k[b]=[d];return a};a.once=function(b,d){d._once=!0;a.on(b,d);return a};a.off=function(b,d){var e=arguments.length;if(1===e)delete k[b];else if(0===e)k={};else{e=k[b];if(!e)return a;e.splice(e.indexOf(d),1)}return a};a.emit=function(){var b=d(arguments);return a.emitterSnapshot(b.shift()).apply(this,b)};a.emitterSnapshot=function(b){var m=(k[b]||[]).slice(0);return function(){var k=
d(arguments),A=this||a;if("error"===b&&!1!==l.throws&&!m.length)throw 1===k.length?k[0]:k;m.forEach(function(d){l.async?e(d,k,A):d.apply(A,k);d._once&&a.off(b,d)});return a}};return a}},{"./debounce":4,atoa:3}],6:[function(l,k,m){(function(d){function e(a,b,d){return a.attachEvent("on"+b,m(a,b,d))}function a(a,b,d){if(d=x(a,b,d))return a.detachEvent("on"+b,d)}function b(a,b,f){return function(b){var e=b||d.event;e.target=e.target||e.srcElement;e.preventDefault=e.preventDefault||function(){e.returnValue=
!1};e.stopPropagation=e.stopPropagation||function(){e.cancelBubble=!0};e.which=e.which||e.keyCode;f.call(a,e)}}function m(a,d,e){var f=x(a,d,e)||b(a,d,e);w.push({wrapper:f,element:a,type:d,fn:e});return f}function x(a,b,d){a:{var e;for(e=0;e<w.length;e++){var f=w[e];if(f.element===a&&f.type===b&&f.fn===d){a=e;break a}}a=void 0}if(a)return b=w[a].wrapper,w.splice(a,1),b}var K=l("custom-event"),v=l("./eventmap"),C=d.document,F=function(a,b,d,e){return a.addEventListener(b,d,e)},z=function(a,b,d,e){return a.removeEventListener(b,
d,e)},w=[];d.addEventListener||(F=e,z=a);k.exports={add:F,remove:z,fabricate:function(a,b,d){if(-1===v.indexOf(b))d=new K(b,{detail:d});else{if(C.createEvent){var e=C.createEvent("Event");e.initEvent(b,!0,!0)}else C.createEventObject&&(e=C.createEventObject());d=e}a.dispatchEvent?a.dispatchEvent(d):a.fireEvent("on"+b,d)}}}).call(this,"undefined"!==typeof global?global:"undefined"!==typeof self?self:"undefined"!==typeof window?window:{})},{"./eventmap":7,"custom-event":8}],7:[function(l,k,m){l="undefined"!==
typeof global?global:"undefined"!==typeof self?self:"undefined"!==typeof window?window:{};m=[];var d="",e=/^on/;for(d in l)e.test(d)&&m.push(d.slice(2));k.exports=m},{}],8:[function(l,k,m){(function(d){var e=d.CustomEvent;k.exports=function(){try{var a=new e("cat",{detail:{foo:"bar"}});return"cat"===a.type&&"bar"===a.detail.foo}catch(b){}return!1}()?e:"function"===typeof document.createEvent?function(a,b){var d=document.createEvent("CustomEvent");b?d.initCustomEvent(a,b.bubbles,b.cancelable,b.detail):
d.initCustomEvent(a,!1,!1,void 0);return d}:function(a,b){var d=document.createEventObject();d.type=a;b?(d.bubbles=!!b.bubbles,d.cancelable=!!b.cancelable,d.detail=b.detail):(d.bubbles=!1,d.cancelable=!1,d.detail=void 0);return d}}).call(this,"undefined"!==typeof global?global:"undefined"!==typeof self?self:"undefined"!==typeof window?window:{})},{}],9:[function(l,k,m){k.exports="function"===typeof setImmediate?function(d){setImmediate(d)}:function(d){setTimeout(d,0)}},{}]},{},[2])(2)});

!function($, window, document, _undefined)
{
	"use strict";

	XF.EditorManager = XF.Element.newHandler({
		options: {
			dragListClass: '.js-dragList',
			commandTrayClass: '.js-dragList-commandTray'
		},

		$lists: null,
		trayElements: [],
		listElements: [],
		isScrollable: true,
		dragula: null,

		init: function()
		{
			this.$lists = this.$target.find(this.options.dragListClass);
			this.$lists.each(XF.proxy(this, 'prepareList'));

			this.initDragula();
		},

		prepareList: function(i, list)
		{
			if ($(list).is(this.options.commandTrayClass))
			{
				this.trayElements.push(list);
			}
			else
			{
				this.listElements.push(list);
			}

			this.rebuildValueCache(list);
		},

		initDragula: function()
		{
			// the following is code to workaround an issue which makes the
			// page scroll while dragging elements.
			var t = this;
			document.addEventListener('touchmove', function(e)
			{
				if (!t.isScrollable)
				{
					e.preventDefault();
				}
			}, { passive:false });

			var lists = this.listElements;

			var i;
			for (i in this.trayElements)
			{
				lists.unshift(this.trayElements[i]);
			}

			this.dragula = dragula(lists, {
				direction: 'horizontal',
				removeOnSpill: true,
				copy: function (el, source)
				{
					return t.isTrayElement(source);
				},
				accepts: function (el, target)
				{
					return !t.isTrayElement(target);
				},
				moves: function (el, source, handle, sibling)
				{
					return !$(el).hasClass('toolbar-addDropdown') && !$(el).hasClass('fr-separator');
				}
			});

			this.dragula.on('drag', XF.proxy(this, 'drag'));
			this.dragula.on('dragend', XF.proxy(this, 'dragend'));
			this.dragula.on('drop', XF.proxy(this, 'drop'));
			this.dragula.on('cancel', XF.proxy(this, 'cancel'));
			this.dragula.on('remove', XF.proxy(this, 'remove'));
			this.dragula.on('over', XF.proxy(this, 'over'));
			this.dragula.on('out', XF.proxy(this, 'out'));
		},

		drag: function(el, source)
		{
			this.isScrollable = false;

			var $el = $(el),
				$source = $(source);

			if ($el.hasClass('toolbar-separator') && !$source.hasClass('js-dragList-commandTray'))
			{
				$el.next('.fr-separator').remove();
			}
		},

		dragend: function(el)
		{
			this.isScrollable = true;
			$('.js-dropTarget').remove();
		},

		drop: function(el, target, source, sibling)
		{
			var $el = $(el),
				$target = $(target),
				cmd = $el.data('cmd');

			// prevent adding duplicate buttons (unless it's a separator)
			if ($target.find('[data-cmd="' + cmd + '"]').length > 1
				&& !$el.hasClass('toolbar-separator')
			)
			{
				$el.remove();
				XF.flashMessage(XF.phrase('buttons_menus_may_not_be_duplicated'), 1500);
			}

			if ($el.hasClass('toolbar-separator'))
			{
				this.appendSeparator($el);
			}
			else
			{
				if ($el.next().is('.fr-separator'))
				{
					$el.insertAfter($el.next());
				}
			}

			// if dragged from our dropdown tray, remove the menu click attr
			if ($el.attr('data-xf-click') === 'menu')
			{
				$el.attr('data-xf-click', null);
			}

			if (!this.isTrayElement(source))
			{
				this.rebuildValueCache(source);
			}
			if (!this.isTrayElement(target))
			{
				this.rebuildValueCache(target);
			}
		},

		cancel: function(el, container, source)
		{
			var $el = $(el),
				$source = $(source);

			if ($el.hasClass('toolbar-separator') && !$source.hasClass('js-dragList-commandTray'))
			{
				this.appendSeparator($el);
			}
		},

		remove: function(el, container, source)
		{
			if (!this.isTrayElement(source))
			{
				XF.flashMessage(XF.phrase('button_removed'), 1500);
				this.rebuildValueCache(source);
			}
		},

		over: function(el, container, source)
		{
		},

		out: function(el, container, source)
		{
		},

		rebuildValueCache: function(list)
		{
			var $list = $(list),
				$cache = $list.find('.js-dragListValue'),
				value = [];

			if (!$cache.length)
			{
				return;
			}

			$list.children().each(function(i, cmd)
			{
				var $cmd = $(cmd);

				if (!$cmd.data('cmd'))
				{
					return;
				}

				value.push($cmd.data('cmd'));
			});

			$cache.val(JSON.stringify(value));
		},

		appendSeparator: function($el)
		{
			var $sep = $('<div />')
				.addClass('fr-separator')
				.addClass('fr' + $el.data('cmd'));

			$sep.insertAfter($el);
		},

		isTrayElement: function(el)
		{
			return (this.trayElements.indexOf(el) !== -1);
		}
	});

	XF.Element.register('editor-manager', 'XF.EditorManager');
}
(jQuery, window, document);