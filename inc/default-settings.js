/* default values for bxSlider - v 4.1.1 */
/* in this object below, the default value is the first of any tuple listed */
/* disguised as a comment */  window.coop_bx_defaults = {
// GENERAL
mode: 'horizontal,vertical,fade',
slideSelector: '',
infiniteLoop: true,
hideControlOnEnd: false,
speed: 500,
easing: null,
slideMargin: 0,
startSlide: 0,
randomStart: false,
captions: false,
ticker: false,
tickerHover: false,
adaptiveHeight: false,
adaptiveHeightSpeed: 500,
video: false,
useCSS: true,
preloadImages: 'visible,all',
responsive: true,

// TOUCH
touchEnabled: true,
swipeThreshold: 50,
oneToOneTouch: true,
preventDefaultSwipeX: true,
preventDefaultSwipeY: false,

// PAGER
pager: true,
pagerType: 'full,short',
pagerShortSeparator: ' / ',
pagerSelector: null,
buildPager: null,
pagerCustom: null,

// PAGER-LAYOUT (custom attributes)
prevNextCSSFile: '',
verticalThumbsCSSFile: '',
horizontalThumbsCSSFile: '',
currentLayout: 'vertical',

// CONTROLS
controls: true,
nextText: 'Next',
prevText: 'Prev',
nextSelector: null,
prevSelector: null,
autoControls: false,
startText: 'Start',
stopText: 'Stop',
autoControlsCombine: false,
autoControlsSelector: null,

// AUTO
auto: false,
pause: 4000,
autoStart: true,
autoDirection: 'next,prev',
autoHover: false,
autoDelay: 0,

// CAROUSEL
minSlides: 1,
maxSlides: 1,
moveSlides: 0,
slideWidth: 0,

// CALLBACKS
onSliderLoad: function() {},
onSlideBefore: function() {},
onSlideAfter: function() {},
onSlideNext: function() {},
onSlidePrev: function() {}

/* also disguised as a comment */ };