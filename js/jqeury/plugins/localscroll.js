/**
 * jQuery.LocalScroll - Same page links auto scrolling.
 * Copyright (c) 2007 Ariel Flesler - aflesler(at)gmail(dot)com
 * Licensed under GPL license (http://www.opensource.org/licenses/gpl-license.php).
 * Date: 10/31/2007
 * @author Ariel Flesler
 * @version 1.1.1
 **/
(function($){$.localScroll=function(a){$('body').localScroll(a)};$.localScroll.defaults={target:'html,body',filter:'*',speed:1000,axis:'y'};$.fn.localScroll=function(b){b=$.extend({},$.localScroll.defaults,b);var c=$(b.target);return this.find('a[hash]').filter(b.filter).click(function(e){if(p(this)){var a=this.hash.slice(1),r=document.getElementById(a)||$('[name='+a+']')[0];if(r){if(b.onBefore)b.onBefore.call(this,e,r,c);c.scrollTo(r,b);return false}}}).end().end()};function p(a){return location.href.replace(/#.*/,'')==a.href.replace(a.hash,'')}})(jQuery);