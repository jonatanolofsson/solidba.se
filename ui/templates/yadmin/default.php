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
    <link href="templates/yweb/style.css" type="text/css" rel="STYLESHEET" />
    <link href="templates/yadmin/style.css" type="text/css" rel="STYLESHEET" />
</head>

<body class="dark">
<div id="wrapper">

<?php include('../ui/templates/yweb/elements/header.php'); ?>

<div id="content">
    <div class="cols">
        <div class="col four first">
            <?php new Box('adminMenu'); ?>
        </div>
        <div class="col twelve">
            <h1><?php new Section('header'); ?></h1>
            <?php new Section('main'); ?>
        </div>
    </div>
</div>

<?php include('../ui/templates/yweb/elements/footer.php'); ?>

</div>
<?php Flash::display(); ?>
</body>
</html>
