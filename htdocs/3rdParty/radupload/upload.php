<html>
<head><title>Rad Upload</title></head>


<body  bgcolor="FFFFCC" style="margin: 1px">
<table border="1" cellpadding="5" width="100%" align="center">
<tr><td colspan="2" bgcolor="#0066cc"><font color="#FFFFCC" size="+1" align="center">Files Uploaded</font></td></tr>
<tr  bgcolor="#ffff00"><td><nobr>File name</nobr></td>
	<td align="right"><nobr>File size</nobr></td></tr>
<?

/*
 * SET THE SAVE PATH by editing the line below. Make sure that the path
 * name ends with the correct file system path separator ('/' in linux and
 * '\\' in windows servers (eg "c:\\temp\\uploads\\" )
 */

$save_path="";    


$file = $_FILES['userfile'];
$k = count($file['name']);


for($i=0 ; $i < $k ; $i++)
{
	if($i %2)
	{
		echo '<tr bgcolor="#FFFF99"> ';
	}
	else
	{	
		echo '<tr>';
	}
	
	echo '<td align="left">' . $file['name'][$i] ."</td>\n";
	echo '<td align="right">' . $file['size'][$i] ."</td></tr>\n";

	if(isset($save_path) && $save_path!="")
	{
		$name = split('/',$file['name'][$i]);
		
		move_uploaded_file($file['tmp_name'][$i], $save_path . $name[count($name)-1]);
	}
	
}

echo "<tr style='color: #0066cc'><td>SSL</td><td>". (($_SERVER[HTTPS] != 'on') ? 'Off' : 'On') ."</td></tr>";
if(! isset($save_path) || $save_path =="")
{
	echo '<tr style="color: #0066cc"><td colspan=2 align="left">Files have not been saved, please edit upload.php to match your configuration</td></tr>';
}


?>
</table>
<p>&nbsp;</p>

<p style="text-align:center;">Sample  PHP Upload handler provided by
 <a href="http://www.radinks.com/?dn">Rad Inks</a></p>
 <p>&nbsp;</p>
<p style="text-align:center;">have you seen our <a href="http://www.radinks.com/sftp/?dn">Secure FTP Applet</a> or &nbsp;
our <a href="http://www.radinks.com/mms/?dn">Multimedia Messaging Solution</a>?</p>

</body>
</html>
