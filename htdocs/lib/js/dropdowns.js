$(function(){$('.dropdown').hover(function(){
    var o,p,wp,dim,vp;
    o = $(this).find('ul');
    p = $(this).offset();
    wp = {left:p.left-$(window).scrollLeft(),top:p.top-$(window).scrollTop()};
    dim = {width:o.width(),height:o.height()+20};
    vp = {width:$(window).width(),height:$(window).height()};
    o.css({
        left: Math.min(0, vp.width - wp.left - dim.width),
        top: Math.min(10, vp.height - wp.top - dim.height)
    });
});});
