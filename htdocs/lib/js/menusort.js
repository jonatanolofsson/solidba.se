//Deps: jquery

$(function(){
    $("#whichpagetomove").hide().closest("#pagemoveform").hide();
    $(".pagemove").click(
    function(e){
        var o,p,wp,dim,vp;
        $(this).append(
            $("#pagemoveform")
                    .find("select:eq(0)").val($(this).closest("li").attr('id').substring(1))
                .end()
        );
        o = $("#pagemoveform").show();
        p = $(this).offset();
        wp = {left:p.left-$(window).scrollLeft(),top:p.top-$(window).scrollTop()};
        dim = {width:o.width()+20,height:o.height()+20};
        vp = {width:$(window).width(),height:$(window).height()};
        o.css({
            left: Math.min(0, vp.width - wp.left - dim.width),
            top: Math.min(10, vp.height - wp.top - dim.height)
        });
        e.stopPropagation();
    });
    $("html").click(function(){
        $("#pagemoveform").hide();
    });
});
