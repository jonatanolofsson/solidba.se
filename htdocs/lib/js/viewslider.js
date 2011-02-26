(function( $ ){
    $.fn.viewslider = function(options) {
        return this.each(function(){
            $this = $(this);
            if($this.find(".viewslider-view").size()<2) return;
            var o = {
                width: 500,
                btnWidth: 50
            };
            if(options) $.extend(o, options);

            // The "current" var points to the active view
            var current = 0;

            $this.wrapInner('<div class="viewsliderwrapper"><div class="viewswrap"><div class="views" /></div></div>');
            var wrapper = $('.viewsliderwrapper', $this);
            var nav = $('<div class="viewslidernav" />').prependTo(wrapper);

            // Count the number of views.. and some more things while at it
            var nr = $this.css({width:(o.width+2*o.btnWidth)+"px"})
                .find(".viewslider-view").css({width:o.width+"px"}).size();

            // Make a slide-function that changes view to the one
            // pointed to by "current"
            slide = function(){
                wrapper.find('.views').animate({marginLeft: -current*o.width+"px"}).end()
                .find(".activeviewslidecrumb").removeClass("activeviewslidecrumb").end()
                .find(".viewslidecrumb").eq(current).addClass("activeviewslidecrumb");
            }
            // Previous view
            $('<div class="prev_view">&nbsp;</div>').css("width", o.btnWidth+"px")
                .click(function(){
                    if(current > 0) {
                        --current;
                        slide();
                    }
                })
                .appendTo(nav);

            // Crumbs
            crumbs = $('<div class="slidecrumbs"></div>').appendTo(nav);

            // Next view
            $('<div class="next_view">&nbsp;</div>').css("width", o.btnWidth+"px")
                .click(function(){
                    if(current < nr-1) {
                        ++current;
                        slide();
                    }
                })
                .appendTo(nav);

            // Crumbs
            wrapper.find(".viewslider-view").each(function(i,e){
                $('<span class="viewslidecrumb" />')
                    .text($(e).find('h3:first-child').text())
                    .click(function(){current=i;slide($(this).closest('.viewslider'));})
                    .appendTo(crumbs);
            });
            $('.viewslidecrumb:first-child').addClass("activeviewslidecrumb");
        });
    };
})( jQuery );
