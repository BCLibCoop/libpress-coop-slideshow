/**
*	Shim to adjust vertical aspect of text-only slides in bxslider
*	to fit the 1000x300 aspect ratio at various widths.
*
**/!function(a,t){var i=function(){this.init()};i.prototype={init:function(){},onload:function(t){var i=a(".bx-viewport").width(),e=parseInt(.3*i)+"px";a(".slide.text").css("height",e),a(".bx-wrapper").css("height",e),a(".bx-viewport").css("height",e);var s=a(".alpha-pager.vertical").css("display");a(".alpha-pager.vertical").css("display","none"),a(".alpha-pager.vertical").css("top","-"+e),a(".alpha-pager.vertical").css("height",e),a(".alpha-pager.vertical").css("display",s)},reset:function(t,i,e){if(t.hasClass("text")){var s=parseInt(.3*t.width())+"px";t.css("height",s),a(".bx-wrapper").css("height",s)}}},a(function(){t.coop_slider=new i})}(jQuery,window);
