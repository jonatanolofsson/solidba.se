<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @package Content
 */
/**
 * The box class contains a number of predefined boxes to add in the template.
 * The objects are unstyled and may thus be used in any generic template
 * @author Jonatan Olofsson [joolo]
 * @package Content
 */
class Box {

    /**
     * When a box is created, it checks to see if it's only argument is a valid
     * method inside the class. If so, then call that method.
     * @param string $type Type of box that should be created
     * @return void
     */
    function __construct() {
        $args = func_get_args();
        $type = array_shift($args);
        global $CONFIG;
        if(method_exists($this, $type)) $this->$type();
    }

    /**
     * Display the administration menu
     * @return unknown_type
     */
    function adminMenu() {
        $a = (string)new Menu('admin_menu', false, false, false, true);
        if(!empty($a))
        echo '
        <div class="box admin_menu">
            <fieldset>
                <legend>'.__('Administrate').'</legend>
                    '.$a.'
            </fieldset>
        </div>';
    }

    function recentChanges() {
        global $USER;
        if($USER->ID == NOBODY) return '';
        $changed = array();
        if(isset($_SESSION['lastLogin'])) {
            global $DB, $Controller;
            $changed = $Controller->max($DB->{'spine,updates'}->get(array('spine.class!' => 'User', 'updates.edited>=' => strtotime($_SESSION['lastLogin'])), 'spine.id', false, '`updates`.`edited` DESC', false, false, true),10);
        }
        if(!$changed) {
            $changed = array(__('None'));
        }
        echo 	'<div class="box recentChanges">'
                    .listify($changed)
                .'</div>';
    }

    /**
     * Displays a few tools associated with the currently viewed page
     * @return string
     */
    function tools($id = false, $extras = true) {
        global $Controller, $ID;
        if(!$id) $id = $ID;
        if(is_object($id)) {
            $obj = $id;
            $id = $obj->ID;
        }
        else {
            $obj = $Controller->get($id);
        }
        if(!$obj) return false;

        $r=array();
        if($extras === true) {
            $r[] = icon('small/eye', __('View'), url(array('id' => $id)), true);
            $extras = false;
        }
        if($obj->mayI(EDIT)) {
            if($editors = $obj->editable) {
                foreach($editors as $editor => $aLevel)
                {
                    if($obj->mayI($aLevel))
                    {
                        $r[] = icon($editor::$edit_icon, __($editor::$edit_text), url(array('edit' => $id, 'with' => $editor)), true);
                    }
                }
            }
        }
        if(is_array($extras)) {
            $r = array_merge($r, $extras);
        }
        elseif($extras) $r[] = $extras;
        return Box::dropdown('small/bullet_wrench', false, $r);
    }

    function dropdown($icon, $text, $rows, $class="") {
        JS::loadjQuery(false);
        JS::lib('dropdowns');
        return '<span class="dropdown'.($class?" ".$class:'').'">'
            .'<span class="dropdown-icon">'.icon($icon, $text, false, true).'</span>'
            .'<ul><li>'
                .(is_array($rows)?implode('</li><li>', $rows):$rows)
            .'</li></ul></span>';
    }

    function toolbox($obj = false) {
        echo self::tools($obj);
    }

    function selectLanguage() {
        global $CONFIG;
        JS::loadjQuery(false);
        JS::raw("$(function(){\$('#".idfy('user_settings::language')."').change(function(e){\$(e.target).closest('form').submit();})});");
        echo Form::quick(url(null, true), false,
            new Select(false, 'user_settings::language', google::languages($CONFIG->Site->languages), @$_COOKIE['user_settings::language'], false, __('Choose language'))
        );
    }

    /**
     * Displays a login form or, if a user is logged in, userinfo about the user.
     * @return void
     */
    function login() {
        global $USER, $SITE;
        if($USER->ID == NOBODY) {
echo '<div class="login">'
            //.'<span class="lable">'.__('Login').'</span>'
            .'<form method="post" action="'.$SITE->url(true, false, true, true).'"><fieldset>'
                .'<label for="username">'.__('Username').':</label>'
                .'<input id="username" name="username" class="text" />'
                .'<label for="password">'.__('Password').':</label>'
                .'<input id="password" type="password" class="text" name="password" />'
                .'<input type="submit" class="submit" value="'.__('Login').'" />'
            .'</fieldset></form>*'.__('A cookie will be saved to remember your login')
        .'</div>';
        } else {
echo '
        <div class="login user">
            <form method="post" action="'.$SITE->URL.'"><fieldset>
                <span class="lable">'.__('User').'</span>
                '.$USER->link().'
                <input type="submit" class="submit" name="logout" value="'.__('Logout').'" />
            </fieldset></form>
        </div>
';
        }
    }


    function newLogin() {
        global $USER, $SITE, $PAGE;
        if($USER->ID == NOBODY) {
            echo '<div id="login2">
                    <a href="javascript:;">Logga in<span>på Yweb</span></a>
                    <div class="login_help">
                        <div class="content_help">
                            <div class="content">
                                <form id="login" method="post" action="'.$SITE->url(true, false, true, true).'"><fieldset>
                                    <label for="username">LiU-ID</label>
                                    <input id="username" name="username" class="text" /><br/>
                                    <label for="password">Lösenord</label>
                                    <input id="password" type="password" class="text" name="password" style="margin-bottom:10px;" /><br />

                                    <input type="submit" class="linkbutton" value="Logga in" />
                                </fieldset></form>
                            </div>
                        </div>
                    </div>
                </div>';

/*				<label for remember">Kom ihåg mig</label>
                <input id="remember" type="Checkbox" name="remember" style="margin-bottom:10px;" /><br />
*/

        } else {
            $uImg = $USER->getImage(64,64);
            $info = '';
            foreach($USER->getInfo() as $key => $data){	$info .= '<p>'.$data.'</p>';}
            echo '<div id="login2">
                    <a href="javascript:;">Mitt konto<span>'.$USER->Name().'</span></a>
                    <div class="login_help">
                        <div class="content_help">
                            <div class="content">'.($uImg?$uImg:icon('large/identity-64')).
                                '<div class="info"><h2>'.$USER->Name(true).'</h2>'.
                                $info.
                                '<p><a href="'.url(array('id' => 'profile')).'">'.__('Change my profile').'</a></p>'.
                                ($PAGE->mayI(EDIT)?'<p><a href="'.url(array('id' => 'admin_area')).'">Admin Page</a></p>':'').
                                '</div><form id="logout" method="post" action="'.$SITE->URL.'"><fieldset>
                                    <input type="submit" class="linkbutton" name="logout" value="'.__('Logout').'" />
                                </fieldset></form>
                            </div>
                        </div>
                    </div>
                </div>';

        }
        JS::raw('$("#login2 a").click(
                    function() {
                        $("#login2 .login_help .content_help")
                        .stop(true)
                        .slideToggle("fast");
                        $("#username").focus();
                    });');

    }

    function feedback() {
        global $USER, $PAGE;
        $feedbackForm = new Form('feedbackform', '/Report', __('Send'),'post',false);
        echo '
        <div class="help_div">
            <div class="help_content">'.($USER->ID == NOBODY?'<div class="disable"><h1>'.__('You must be logged in to send feedback').'</h1><p style="text-align:center"><a href="javascript:;" class="linkbutton" onclick="feedbackLogin();">'.__('Login').'</a></p></div>':'').'
                <div class="report_content">
                    <h2>'.__('Report an error').'</h2>'
                    .$feedbackForm->collection(
                        new RadioSet('Feedback type', 'feedback', array(
                            'error' => __('Bug'),
                            'suggestion' => __('Other feedback')
                        ),false,1),
                        new TextArea(__('Description'), 'description','',false,false,false,20,25)
                    ).
                    '<p style="margin-top:15px;">'.__('Here you can report any bugs you come across. Please include as much information as possible.').'</p>
                </div>
            </div>
            <div id="btn_report"><h1><nobr>'.__('REPORT BUG').'</nobr></h1></div>
        </div>';

        $ajaxReport = true;
        JS::raw('
        $("#btn_report").click(
            function() {
                $(this).siblings(".help_content")
                .stop(true)
                .animate({width:"toggle"},"fast");
            }
        );

        $("#feedbackform").submit(
            function () {
                if($("#feedbackform input[name=feedback]:checked").length != 1){
                    alert("'.__('You have to choose a feedback type').'");
                    return(false);
                } else if($("#feedbackform textarea").val() == ""){
                    alert("'.__('Please describe the problem before sending').'");
                    return(false);
                } else if('.(int)(bool)$ajaxReport.'){
                    $.post("/Report", $("#feedbackform").serialize(),
                        function(data){
                            if(data.length > 0){
                                alert(data);
                            }
                        }
                    );
                    $("#btn_report").siblings(".help_content").stop(true).animate({width:"toggle"},"fast");
                    $("#feedbackform")[0].reset();
                    return(false);
                }
            }
        );

        function feedbackLogin() {
            $("#btn_report").siblings(".help_content").stop(true).animate({width:"toggle"},"fast","linear",
                function(){
                    $("#login2 .login_help .content_help:hidden")
                        .stop(true)
                        .slideToggle("fast");
                    $("#username").focus();
                }
            )
        };
    ');

    }

}
?>
