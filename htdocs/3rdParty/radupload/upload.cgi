#!/usr/bin/perl -w

#
# Perl script to accept file uploads. This script is provided for demonstration 
# purposes only.
# you need to have the CGI module installed.
#
# Copyright Rad Inks (pvt) 2003
# http://www.radinks.com

#
# This sample file does not permanently save uploaded files.
#

use CGI;			

sub bye_bye {
	$mes = shift;
	print "<br>$mes<br>\n";

	exit;
}



print "Content-type: text/html\n\n ";
my $cg = new CGI();


print <<__TABLE__;
<html>
<body  bgcolor="FFFFCC" style="margin: 1px">

<table border="1" cellpadding="5" width="100%" align="center">
<tr><td colspan="2" bgcolor="#0066cc"><font color="#FFFFCC" size="+1" align="center">Files Uploaded</font></td></tr>
<tr  bgcolor="#ffff00"><td style="font-size: 110%;"><nobr>File Name</nobr></td>
	<td style="font-size: 110%"  align="right"><nobr>File size</nobr></td></tr>
__TABLE__

my $size = $cg->param;
for($i=0 ; $i < $size ; $i++)
{
	$file_upload 	= $cg->param("userfile[$i]");

	if($file_upload) {
		my $fh = $cg->upload("userfile[$i]");
		$fsize =(-s $fh);
		print "<tr><td>$fh </td>\n";
		print "<td>$fsize</td></tr>";
	}
}


print <<__TABLE__;
</table>

<p style="text-align:center; font-size: 80%">Sample  Perl Upload handler provided by
 <a href="http://www.radinks.com/?dn">Rad Inks</a></p>
 
<p style="text-align:center; font-size: 80%">have you seen our <a href="http://www.radinks.com/sftp/?dn">Secure FTP Applet</a> or our
<a href="http://www.radinks.com/mms/?dn">Multimedia Messaging Solution</a>?</p>

__TABLE__


