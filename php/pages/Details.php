<?
$Data = GetReport ($Post['ID']);
$Stacktrace = GetStacktrace ($Post['ID']);
?>
<TABLE>
<TR><TH COLSPAN="2">Crash report details</TH></TR>
<TR><TD>Buildserv command:</TD><TD>!translate file=http://infologs.zydox.se/?Infolog&NoHTML=1&ID=<? echo $Post['ID']; ?></TD></TR>
<TR><TH CLASS="Sub" COLSPAN="2">Stacktrace</TH></TR>
<TR><TD CLASS="NoPad" COLSPAN="2"><TABLE CELLSPACING="0" CELLPADDING="0">
<COL WIDTH="20"><COL WIDTH="90"><COL WIDTH="330"><COL WIDTH="60"><COL WIDTH="100">
<?
foreach ($Stacktrace as $Row)	{
	?>
<TR>
<TD><? echo $Row['line']; ?></TD>
<TD><? echo $Row['file']; ?></TD>
<TD><? echo $Row['functionname']; ?></TD>
<TD><? echo $Row['functionat']; ?></TD>
<TD><? echo $Row['address']; ?></TD>
</TR>
<?
}
?>
</TABLE></TD></TR>
<TR><TH CLASS="Sub" COLSPAN="2">Extensions</TH></TR>
<?
if ($Data['extensions'])
	foreach (explode ("\n", $Data['extensions']) as $Extension)	{	?>
<TR><TD><? echo $Extension; ?></TD></TR>
<?	}
?>
</TABLE>