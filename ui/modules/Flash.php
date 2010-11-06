<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @package Content
 */

 /**
  * The flash class is created to view system messages and warnings
 * @author Jonatan Olofsson [joolo]
 * @package Content
  */
class Flash{
    static $__FLASHES = array();

    /**
     * When a flash is created, it is called with a message and an optional type.
     * The type is set as the class on the div (prepended by 'flash_type_')
     * that is later created and displayed to the user.
     * Messages with the same class are grouped and duplicates within a
     * class are not displayed.
     *
     * @param string $message The message to be shown
     * @param string $type The class of the flash
     * @return void
     */
    function create($message, $type=false){
        $type = ($type?$type:'notice');
        if(!isset(self::$__FLASHES[$this->Type]) || !in_array($message, self::$__FLASHES[$this->Type])){
            self::$__FLASHES[$this->Type][] = $message;
        }
    }


    /**
     * Display all flashes
     * @return HTML
     */
    static function display() {
        if(!empty(self::$__FLASHES)) {
            echo '<div class="flash_overlay">';
            foreach(self::$__FLASHES as $type => $flashes) {
                echo '<div class="flash flash_type_'.$type.'"><div class="icon"></div><ul>';
                foreach($flashes as $flash) {
                    echo '<li>'.$flash.'</li>';
                }
                echo '</ul><div class="buttons">'.($type=='question'?'<a id="btn_yes" class="linkbutton btn_yes" href="javascript:;">'.__('Yes').'</a><a id="btn_no" class="linkbutton btn_no" href="javascript:;" style="margin-left:20px;">'.__('No').'</a>':'<a id="btn_ok" class="linkbutton" href="javascript:;">OK</a>').'</div></div>';
            }
            //FIXME: Add function for yes/no buttons
            echo '</div>';
            JS::loadjQuery();
            JS::raw('$(function(){
                var win_h=$(window).height();
                var win_w=$(window).width();
                var fl_h=$(".flash").height();
                var fl_w=$(".flash").width();
                $(".flash").css({top:(win_h/2-fl_h/2),left:(win_w/2-fl_w/2),opacity:0,display:"block"});
                $(".flash_overlay").animate({opacity:1},250,"linear",function(){
                    $(".flash").animate({opacity:1},250,"linear",function(){
                        $("#btn_ok").select().focus();
                    })
                }).bind("click",function(){
                    var col="#F87217";
                    $(".flash").animate({
                        width:"+=4",
                        height:"+=4",
                        top:"-=2",
                        left:"-=2",
                        borderTopColor:col,
                        borderRightColor:col,
                        borderBottomColor:col,
                        borderLeftColor:col
                        },80,"linear",function(){
                            $(this).animate({
                                width:"-=4",
                                height:"-=4",
                                top:"+=2",
                                left:"+=2",
                                borderTopColor:"#aaa",
                                borderRightColor:"#aaa",
                                borderBottomColor:"#aaa",
                                borderLeftColor:"#aaa"
                                },80,"linear",function(){
                                    $("#btn_ok").select().focus();
                                }
                            )
                        }
                    )
                });
                $("#btn_ok").click(function(){
                    $(".flash_overlay").animate({opacity:0},250,"linear",function(){
                        $(".flash_overlay").remove();

                    })
                });
            });');
        }
    }
}
/*
var col = "#F87217";
                        $(".flash").animate({
                            borderTopColor:col,
                            borderRightColor:col,
                            borderBottomColor:col,
                            borderLeftColor:col
                        },200,"linear",function(){
                            $(this).animate({
                                borderTopColor:"#aaa",
                                borderRightColor:"#aaa",
                                borderBottomColor:"#aaa",
                                borderLeftColor:"#aaa"
                            },100,"linear",function(){
                                $(this).animate({
                                    borderTopColor:col,
                                    borderRightColor:col,
                                    borderBottomColor:col,
                                    borderLeftColor:col
                                },200,"linear",function(){
                                    $(this).animate({
                                        borderTopColor:"#aaa",
                                        borderRightColor:"#aaa",
                                        borderBottomColor:"#aaa",
                                        borderLeftColor:"#aaa"
                                    },100,"linear",function(){
                                        $("#btn_ok").select().focus();
                                    })
                                })
                            })
                        })*/
?>
