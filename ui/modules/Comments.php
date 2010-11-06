<?php

/**
 *
 *
 * @version $Id$
 * @copyright 2009
 */
/**
 *
 *
 */
class Comments{
    
    /**
     * Display form for entering comment
     * @param $id Id of the page to comment
     * @return form
     */
    function displayForm($id=false){
        global $USER, $CURRENT, $ID, $CONFIG;
    
        if(!$id) {
            if(!$CURRENT->settings['comments']) return '';
            $id = $ID;
        }
        if(is_object($id)) $id = $id->ID;

        if($USER->ID == NOBODY) {
            if($CONFIG->comments->guest_comments == 'none') return;
        } elseif($CONFIG->comments->user_comments == 'none') return;
        $form = new Form('commentForm');

        $_POST->setType('commentbody', 'any');
        $_POST->setType('author', 'string');

        echo 	$form->collection(
                    new Hidden('pid', $id),
                    new Set(
                        ($USER->ID === NOBODY
                            ? new Input(__('Name'), 'author', $_POST['author'])
                            : new FormText('Name', $USER->settings['displayname'])
                        ),
                        ($USER->ID === NOBODY && $CONFIG->comments->CAPTCHA_for_guests
                            ? reCAPTCHA::view()
                            : null
                        ),
                        new HTMLField(__('Comment'), 'commentbody', $_POST['commentbody'])
                    )
                );
    }

    /**
     * Display the comments for a given page
     * @param int $id The id of the page (or the page itself)
     * @param bool $echo Wether to echo the result or not
     * @return string
     */
    function displayComments($id=false, $echo = false){
        global $CURRENT, $ID, $DB, $Controller, $USER;
        if(!$id) {
            if(!$CURRENT->settings['comments']) return '';
            $id = $ID;
        }
        if(is_object($id)) $id = $id->ID;

        $res = '';
        $comments = $DB->comments->asArray(array('id' => $id, 'authd_by>' => 0, 'language' => $USER->settings['language']), false, false, false, 'created ASC');
        if(count($comments)) {
            $res .= '<ol class="comments">';
            foreach($comments as $comment) {
                $u = false;
                if(is_numeric($comment['author'])) $u = $Controller->{$comment['author']}(OVERRIDE,'User');
                $res .= '<li><span class="authsay"><span class="author">'.($u?$u:$comment['author']).'</span> '.__('says').': </span>'
                    //FIXME: Tools
                    .$comment['comment'].'</li>';
            }
            $res .= '</ol>';
        }
        if($echo) echo $res;
        return $res;
    }
    
    /**
     * Display the form for managing the comments
     * @param int|object $page Id of the page to manage, or the page itself
     * @param string $l What language to manage
     * @return string
     */
    function edit($page, $l) {
        global $DB, $Controller;
        if(is_object($page)) $page = $page->ID;
        $res = $DB->comments->get(array('id' => $page, 'language' => $l), false, false, 'created ASC');
        $c=0;
        $r = '<ol class="comments">';
        while(false !== ($comment = Database::fetchAssoc($res))) {
            $u = false;
            if(is_numeric($comment['author'])) $u = $Controller->{$comment['author']}(OVERRIDE,'User');
            $r .= '<li><span class="authsay'.($comment['authd_by']==0?' unauthorized':'').'"><div class="tools">'
                    .($comment['authd_by']==0 ? icon('small/tick', 'Approve', url(array('approve' => $comment['cid']), true)) : __('Approved by').': ' . $Controller->{$comment['authd_by']} . ' | ')
                    .icon('small/cross', __('Remove'), url(array('remove' => $comment['cid']), true))
                .'</div>'
                    .'<span class="author">'.($u?$u:$comment['author']).'</span> '.__('says').': </span>'
                .$comment['comment'].'</li>';
                $c++;
        }
        $r .= '</ol>';
        return ($c?$r:'');
    }

    /**
     * Save a new comment
     * @return bool
     */
    function save(){
        global $DB, $ID, $USER, $CURRENT, $CONFIG;
        $_POST->setType('commentbody', 'any');
        $_POST->setType('author', 'string');

        if($USER->ID === NOBODY) {
            if($CONFIG->comments->CAPTCHA_for_guests && !reCAPTCHA::verify()) {
                Flash::create(__('CAPTCHA verification failed'), 'warning');
                return false;
            }
            $ctype = $CONFIG->comments->guest_comments;
        }
        else $ctype = $CONFIG->comments->user_comments;

        if($ctype == 'none') return false;

        $DB->comments->insert(array('id' => $ID,
                                    'comment' => $_POST['commentbody'],
                                    'author' => (($_POST['author'] && $USER->ID === NOBODY) ? $_POST['author'] : $USER->ID),
                                    'ip' => $_SERVER['REMOTE_ADDR'],
                                    'authd_by' => ($CURRENT->mayI(EDIT)
                                                    ? $USER->ID
                                                    : ($ctype == 'review'
                                                        ? 0
                                                        : $USER->ID)
                                                    ),
                                    'created' => time()
                                    ));
        $_POST->clear('commentbody', 'author');
        return true;
    }
}
global $CONFIG;
$CONFIG->comments->setType('CAPTCHA_for_guests', 'check');
$CONFIG->comments->setType('guest_comments', 'select', array('none' => 'None', 'review' => 'Review comments', 'allow' => 'Allow all comments (not recommended)'));
$CONFIG->comments->setType('user_comments', 'select', array('none' => 'None', 'review' => 'Review comments', 'allow' => 'Allow all comments'));


$_POST->setType('commentForm', 'any');
if($_POST['commentForm']) Comments::save();

?>