<html>
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Rad Upload Lite</title>
</head>
<body>
    <table border="0" valign="middle" align="center" cellpadding="15">
        <tr><td colspan="2" align="center"><h2 style="color:#000033">Rad Upload</h2></td></tr>
        <tr>
           <td width="290" valign="top">
                   
                <p>Thank you for downloading Rad Upload Lite</p>

                <p>This web page will ask your browser to automatically install the Java plug-in
                (if the correct version is not already installed).</p>

                <p>In the unlikely event that your system does not support this auto installation procedure, you may need to
                visit <a href="http://java.com/en/download/manual.jsp">java.sun.com</a> and download the latest version   of Java.</p>

                <p>Please visit our website <a href="http://www.radinks.com/upload/?dn">radinks.com</a> for
                online documentation, FAQs, and <a href="http://www.radinks.com/contact.php?dn">Support</a>
           </td>
           <td><div style="border: 1px groove #336699; padding:4px">

<?      
    $useApplet=0;
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    if(stristr($user_agent,"konqueror") || stristr($user_agent,"macintosh") || stristr($user_agent,"opera"))
    {
        $useApplet=1;
        echo '<applet name="Rad Upload Lite"
                        archive="dndlite.jar"
                        code="com.radinks.dnd.DNDAppletLite"
                        width="280"
                        height="280">';
            
    }
    else
    {
        if(strstr($user_agent,"MSIE")) {
                echo '<script language="javascript" src="embed.js" type="text/javascript"></script>';
                echo '<script>IELoader()</script>';
        } else {
                echo '<object type="application/x-java-applet;version=1.4.1"
                        width= "290" height= "280"  id="rup" name="rup">';
                echo '  <param name="archive" value="dndlite.jar">
                    <param name="code" value="com.radinks.dnd.DNDAppletLite">
                    <param name="name" value="Rad Upload Lite">';

        }
    }
?>
    <!-- BEGIN APPLET CONFIGURATION PARAMETERS -->            
        <param name="max_upload" value="7000">
            <!-- size in kilobytes (takes effect only in Rad Upload Plus) -->

        <param name = "message" value="Drop your files here.<br> This message can be changed by editing the applet.php file. Check for the message property.">
            <!-- edit the above line to customize the welcome message displayed. example
            value='http://www.radinks.com/upload/init.html' -->
        
        <param name='url' value='http://67.131.250.110/upload.php'>
            <!-- you can pass additional parameters by adding them to the url-->
            <!-- to upload to an ftp server instead of a web server, please specify a url
                    in the following format:
            ftp://username:password@ftp.myserver.com
            replacing username, password and ftp.myserver.com with corresponding entries for your site -->
    <!-- END APPLET CONFIGURATION PARAMETERS -->
<?
		if(isset($_SERVER['PHP_AUTH_USER']))
		{
			printf('<param name="chap" value="%s">',
				base64_encode($_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW']));
		}
		
		if($useApplet == 1)
		{
			echo '</applet>';
		}
		else
		{
            echo '</object>';
        }
?>
                </div>
          </td>
   </tr>
  </table>
  <p>&nbsp;</p>
  <p align="center">A product of <a href="http://www.radinks.com/?dn">Rad Inks</a></p>
 </body>
</html>

