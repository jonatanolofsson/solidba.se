<?php 
/**
 * @package Templates
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 */
?>
<?='<?xml version="1.0" encoding="utf-8"?>' ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <title><?=$PAGE->Name ?></title>
    <meta http-equiv="content-type"
        content="text/html;charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta name="description" content="<?=$PAGE->description ?>" />
    <link href="templates/popup/style.css" type="text/css" rel="STYLESHEET" />
</head>

<body>
<div id="container">
    <div id="content">
        <?php
            new Section('main');
        ?>
    </div>
</div>
<?PHP Flash::display();?>
</body>
</html>