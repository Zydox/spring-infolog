<?
//print_r ($Post);
if (!$Post['Selected'])
	$Post['Selected'] = array ("index@crashed");

$HitsPerPage = 100;
function ZydHash ($Value, $Type, $DispValue = NULL)	{
	global $ZydHashCache;
	if ($Type == "Table")
		$Return = "T_";
	elseif ($Type == "SubTable")
		$Return = "S_";
	elseif ($Type == "Value")
		$Return = "V_";
	elseif ($Type == "Source")
		return ($ZydHashCache[1][substr ($Value, 2)]);
	
	if (!$ZydHashCache[0][md5 ($Value)])	{
		$ZydHashCache[0][md5 ($Value)] = count ($ZydHashCache[0]) + 1;
		$ZydHashCache[1][count ($ZydHashCache[0])] = ($DispValue ? $DispValue : $Value);
	}
	$Return .= $ZydHashCache[0][md5 ($Value)];
	return ($Return);
}

unset ($Filter, $Where, $Join, $Select, $PageURL);
if ($Post['Filter'])	{
	foreach ($Post['Filter'] as $Data)	{
		if ($Data)	{
			$Data = explode ("@", $Data);
			$Temp = $Data;
			unset ($Temp[0], $Temp[1]);
			$Filter[$Data[0]][$Data[1]][join ("@", $Temp)] = join ("@", $Temp);
		}
	}
//	print_r ($Filter);
	if (is_array ($Filter['settingid']))	{
		unset ($NewWhere);
		foreach (array_keys ($Filter['settingid']) as $Setting)	{
			if (is_array ($Filter['settingid'][$Setting]))	{
				foreach ($Filter['settingid'][$Setting] as $ID)
					if ($ID || is_numeric ($ID))	{
						if (substr ($ID, 0, 6) == "REGEX_")	{
							if (substr ($ID, 6))
								$NewWhere[$Setting][] = ZydHash ($Setting, "SubTable", GetSettingsList ($Setting)) . ".data REGEXP '" . mysql_escape_string (substr ($ID, 6)) . "'";
						}	else	{
							$NewWhere[$Setting][] = ZydHash ($Setting, "Table", GetSettingsList ($Setting)) . ".valueid='" . mysql_escape_string ($ID) . "'";
						}
					}
				if (is_array ($NewWhere[$Setting]))	{
					$NewWhere[$Setting] = "(" . join (" OR ", $NewWhere[$Setting]) . ")";
					$Join[ZydHash ($Setting, "Table")] = "LEFT JOIN settings AS " . ZydHash ($Setting, "Table") . " ON records.id=" . ZydHash ($Setting, "Table") . ".reportid AND " . ZydHash ($Setting, "Table") . ".settingid='" . mysql_escape_string ($Setting) . "'";
					$Join[ZydHash ($Setting, "SubTable")] = "LEFT JOIN settingsdata AS " . ZydHash ($Setting, "SubTable") . " ON " . ZydHash ($Setting, "Table") . ".valueid=" . ZydHash ($Setting, "SubTable") . ".id";
				}
			}
		}
		if (is_array ($NewWhere))
			$Where[] = "(" . join (" AND ", $NewWhere) . ")";
	}
	if (is_array ($Filter['stacktraceid']))	{
		unset ($NewWhere);
		foreach ($Filter['stacktraceid'] as $ID)
			$NewWhere[] = "stacktrace.stacktraceid='" . mysql_escape_string ($ID) . "'";
		$Join['LEFT JOIN stacktrace ON records.id=stacktrace.reportid'] = "LEFT JOIN stacktrace ON records.id=stacktrace.reportid";
		$Where[] = "(" . join (" OR ", $NewWhere) . ")";
	}
	if (is_array ($Filter['translatedstacktraceid']))	{
		unset ($NewWhere);
		foreach ($Filter['translatedstacktraceid'] as $ID)
			$NewWhere[] = "stacktrace.translatedid='" . mysql_escape_string ($ID) . "'";
		$Join['LEFT JOIN stacktrace ON records.id=stacktrace.reportid'] = "LEFT JOIN stacktrace ON records.id=stacktrace.reportid";
		$Where[] = "(" . join (" OR ", $NewWhere) . ")";
	}
	if (is_array ($Filter['index']))	{
		unset ($NewWhere);
		foreach (array_keys ($Filter['index']) as $Setting)	{
			if (is_array ($Filter['index'][$Setting]))	{
				foreach ($Filter['index'][$Setting] as $ID)
					if ($ID || is_numeric ($ID))	{
						if (substr ($ID, 0, 6) == "REGEX_")	{
							if (substr ($ID, 6))
								$NewWhere[$Setting][] = "records." . mysql_escape_string ($Setting) . " REGEXP '" . mysql_escape_string (substr ($ID, 6)) . "'";
						}	else	{
							$NewWhere[$Setting][] = "records." . mysql_escape_string ($Setting) . "='" . mysql_escape_string ($ID) . "'";
						}
					}
				if (is_array ($NewWhere[$Setting]))
					$NewWhere[$Setting] = "(" . join (" OR ", $NewWhere[$Setting]) . ")";
			}
		}
		if (is_array ($NewWhere))
			$Where[] = "(" . join (" AND ", $NewWhere) . ")";
	}
	if (is_array ($Filter['indexid']))	{
		unset ($NewWhere);
		foreach (array_keys ($Filter['indexid']) as $Setting)	{
			if (is_array ($Filter['indexid'][$Setting]))	{
				foreach ($Filter['indexid'][$Setting] as $ID)
					if ($ID || is_numeric ($ID))	{
						if (substr ($ID, 0, 6) == "REGEX_")	{
							if (substr ($ID, 6))
								$NewWhere[$Setting][] = ZydHash ($Setting, "Table", GetSettingsList ($Setting)) . ".data REGEXP '" . mysql_escape_string (substr ($ID, 6)) . "'";
						}	else	{
							$NewWhere[$Setting][] = "records." . mysql_escape_string ($Setting) . "id='" . mysql_escape_string ($ID) . "'";
						}
					}
				if (is_array ($NewWhere[$Setting]))	{
					$NewWhere[$Setting] = "(" . join (" OR ", $NewWhere[$Setting]) . ")";
					$Join[ZydHash ($Setting, "Table")] = "LEFT JOIN recordsdata AS " . ZydHash ($Setting, "Table") . " ON records." . $Setting . "id=" . ZydHash ($Setting, "Table") . ".id AND " . ZydHash ($Setting, "Table") . ".field='" . mysql_escape_string ($Setting) . "'";
				}
			}
		}
		if (is_array ($NewWhere))
			$Where[] = "(" . join (" AND ", $NewWhere) . ")";
	}
	if (is_array ($Filter['stacktrace']))	{
		unset ($NewWhere);
		foreach (array_keys ($Filter['stacktrace']) as $Setting)	{
			if (is_array ($Filter['stacktrace'][$Setting]))	{
				foreach ($Filter['stacktrace'][$Setting] as $ID)
					if ($ID || is_numeric ($ID))	{
						if (substr ($ID, 0, 6) == "REGEX_")	{
							if (substr ($ID, 6) || is_numeric (substr ($ID, 6)))
								$Join[ZydHash ("stacktrace", "Table")] = "LEFT JOIN stacktrace ON records.id=stacktrace.reportid";
								if ($Setting == "translated")	{
									$NewWhere[$Setting][] = "CONCAT(stacktracetranslated.file, ' (', stacktracetranslated.line, ')') REGEXP '" . mysql_escape_string (substr ($ID, 6)) . "'";
									$Join[ZydHash ("stacktracetranslated", "Table")] = "LEFT JOIN stacktracetranslated ON stacktrace." . $Setting . "id=stacktracetranslated.id";
								}	else	{
									$NewWhere[$Setting][] = "CONCAT(stacktracedata.file, ' [', stacktracedata.address, ']') REGEXP '" . mysql_escape_string (substr ($ID, 6)) . "'";
									$Join[ZydHash ("stacktracedata", "Table")] = "LEFT JOIN stacktracedata ON stacktrace." . $Setting . "id=stacktracedata.id";
								}
						}	else	{
							$NewWhere[$Setting][] = "stacktrace." . mysql_escape_string ($Setting) . "id='" . mysql_escape_string ($ID) . "'";
						}
					}
				if (is_array ($NewWhere[$Setting]))	{
					$NewWhere[$Setting] = "(" . join (" OR ", $NewWhere[$Setting]) . ")";
					$Join[ZydHash ("stacktrace", "Table")] = "LEFT JOIN stacktrace ON records.id=stacktrace.reportid";
				}
			}
		}
		if (is_array ($NewWhere))
			$Where[] = "(" . join (" AND ", $NewWhere) . ")";
	}
	
	if (is_array ($Filter))
		foreach (array_keys ($Filter) as $Key)
			foreach (array_keys ($Filter[$Key]) as $Key2)
				foreach ($Filter[$Key][$Key2] as $ID)
					$PageURL .= "&Filter[]=" . $Key . "@" . $Key2 . "@" . $ID;
//	print_r ($Filter);
}

unset ($Select);
foreach ($Post['Selected'] as $Selected)	{
	$Selected = explode ("@", $Selected);
	if ($Selected[0] == "settingid")	{
		$Join[ZydHash ($Selected[1], "Table", GetSettingsList ($Selected[1]))] = "LEFT JOIN settings AS " . ZydHash ($Selected[1], "Table") . " ON records.id=" . ZydHash ($Selected[1], "Table") . ".reportid AND " . ZydHash ($Selected[1], "Table") . ".settingid='" . mysql_escape_string ($Selected[1]) . "'";
		$Join[ZydHash ($Selected[1], "SubTable")] = "LEFT JOIN settingsdata AS " . ZydHash ($Selected[1], "SubTable") . " ON " . ZydHash ($Selected[1], "Table") . ".valueid=" . ZydHash ($Selected[1], "SubTable") . ".id";
		$Select[ZydHash ($Selected[1], "Value")] = ZydHash ($Selected[1], "SubTable") . ".data AS " . ZydHash ($Selected[1], "Value");
	}	elseif ($Selected[0] == "index")	{
		$Select[ZydHash ($Selected[1], "Value")] = "records." . mysql_escape_string ($Selected[1]) . " AS " . ZydHash ($Selected[1], "Value");
	}	elseif ($Selected[0] == "indexid")	{
		$Join[ZydHash ($Selected[1], "Table")] = "LEFT JOIN recordsdata AS " . ZydHash ($Selected[1], "Table") . " ON records." . mysql_escape_string ($Selected[1]) . "id=" . ZydHash ($Selected[1], "Table") . ".id";
		$Select[ZydHash ($Selected[1], "Value")] = ZydHash ($Selected[1], "Table") . ".data AS " . ZydHash ($Selected[1], "Value");
	}	elseif ($Selected[0] == "stacktrace")	{
		$Join[ZydHash ("stacktrace", "Table")] = "LEFT JOIN stacktrace ON records.id=stacktrace.reportid";
		if ($Selected[1] == "translated")	{
			$Join[ZydHash ("stacktracetranslated", "Table")] = "LEFT JOIN stacktracetranslated ON stacktrace.translatedid=stacktracetranslated.id";
			$Select[ZydHash ($Selected[1], "Value")] = "GROUP_CONCAT(stacktracetranslated.file, ' (', stacktracetranslated.line, ')') AS " . ZydHash ($Selected[1], "Value");
		}	elseif ($Selected[1] == "stacktrace")	{
			$Join[ZydHash ("stacktracedata", "Table")] = "LEFT JOIN stacktracedata ON stacktrace.stacktraceid=stacktracedata.id";
			$Select[ZydHash ($Selected[1], "Value")] = "GROUP_CONCAT(stacktracedata.file, ' [', stacktracedata.address, ']') AS " . ZydHash ($Selected[1], "Value");
		}	else	{
			$Join[ZydHash ("stacktracetranslated", "Table")] = "LEFT JOIN stacktracetranslated ON stacktrace.translatedid=stacktracetranslated.id";
			$Join[ZydHash ("stacktracedata", "Table")] = "LEFT JOIN stacktracedata ON stacktrace.stacktraceid=stacktracedata.id";
			$Select[ZydHash ($Selected[1], "Value")] = "GROUP_CONCAT(stacktracedata.file, ' [', stacktracedata.address, '] ', stacktracetranslated.file, ' (', stacktracetranslated.line, ')') AS " . ZydHash ($Selected[1], "Value");
		}
		$SelectValue[ZydHash ($Selected[1], "Value")] = array ("REPLACE", ",", "<BR>");
	}
	$PageURL .= "&Selected[]=" . join ("@", $Selected);
}

//echo "\n\nSELECT records.id, date" . ($Select ? ", " . join (", ", $Select) : "") . " FROM records" . ($Join ? " " . join (" ", $Join) : NULL) . ($Where ? " WHERE (" . join (") AND (", $Where) . ")" : NULL), "\n\n\n<BR>";
$MySQL_Result = DB_Query ("SELECT COUNT(DISTINCT records.id) AS Rows FROM records" . ($Join ? " " . join (" ", $Join) : NULL) . ($Where ? " WHERE (" . join (") AND (", $Where) . ")" : NULL));
$Rows = join ("", mysql_fetch_assoc ($MySQL_Result));
$MySQL_Result = DB_Query ("SELECT records.id, date" . ($Select ? ", " . join (", ", $Select) : "") . " FROM records" . ($Join ? " " . join (" ", $Join) : NULL) . ($Where ? " WHERE (" . join (") AND (", $Where) . ")" : NULL) . " GROUP BY records.id ORDER BY records.date, records.id LIMIT " . mysql_escape_string ($Post['Limit'] ? $Post['Limit'] : 0) . ", " . $HitsPerPage);
?>
<TABLE WIDTH="100%">
<FORM METHOD="POST" NAME="List">
<INPUT TYPE="HIDDEN" NAME="Page" VALUE="List">
<INPUT TYPE="HIDDEN" NAME="Limit" VALUE="0">
<TR><TD><TABLE CLASS="Box">
<TR><TH>Visible columns <FONT ONCLICK="InfoBox ('This is the visivle columns section', 'Here you can choose which fields that should be displayed in the results list.<BR>Multiple options are allowed.');">[?]</FONT></TH></TR>
<TR><TD><SELECT NAME="Selected[]" SIZE="10" MULTIPLE><? echo FilterOptions ($Post['Selected']); ?></SELECT></TD></TR>
</TABLE></TD>
<TD ALIGN="RIGHT"><TABLE CLASS="Box">
<TH COLSPAN="100">Filters <FONT ONCLICK="InfoBox ('This is the filter section', 'Here you can add filters which limits the list rows...<BR><BR>To add a new filter, selected the field which you wish to filter on in the \'\' ==[ Add new Filter ]==\'\' box,the page will then reload and you can choose to limit the results by selecting multiple options or using RegEx (' + ZydURL ('http://www.wellho.net/regex/mysql.html') + ').<BR><BR>To remove a filter, simple click on the &quot;X&quot; in the filters box.');">[?]</FONT></TH></TR>
<TR>
<?
if (is_array ($Filter))	{
	foreach (array_keys ($Filter) as $Type)
		echo Filter ($Type, $Filter[$Type]);
}
?>
</TR>
<TR><TD COLSPAN="100"><SELECT NAME="Filter[]" ONCHANGE="document.List.submit ();"><OPTION VALUE="">==[ Add new filter ]==</OPTION><? echo FilterOptions (); ?></SELECT></TD></TR>
</TABLE>
</TD></TR>
<TR><TD><INPUT TYPE="SUBMIT" VALUE="Refresh"></TD></TR>
</FORM>
</TABLE><BR>


<TABLE>
<TR><TD COLSPAN="<? echo 2 + count ($Select); ?>">Pages <?
for ($iPage = 1; $iPage <= ceil ($Rows / $HitsPerPage); $iPage++)	{
?>
<A HREF="?List<? echo $PageURL; ?>&Limit=<? echo ($iPage - 1) * $HitsPerPage; ?>"><? echo $iPage; ?></A>
<?
}
?></TD></TR>
<TR><TH COLSPAN="<? echo 2 + count ($Select); ?>">Results (<? echo ($Post['Limit'] + 1) . "-", min ($Post['Limit'] + $HitsPerPage, $Rows), " of " . $Rows; ?>)</TH></TR>
<TR>
<TH CLASS="LineM0">ID</TH>
<TH CLASS="LineM1">Date</TH>
<?	if (is_array ($Select))	foreach (array_keys ($Select) as $Field)	{	?>
<TH CLASS="LineM<? echo abs (++$iRowH % 2 - 1) ?>"><? echo ZydHash ($Field, "Source"); ?></TH>
<?	}	?>
</TR>
<?
while ($Data = mysql_fetch_assoc ($MySQL_Result))	{
	$CSS = "LineM" . (++$iRow % 2) . "S";
	?>
<TR>
<TD CLASS="<? echo $CSS, abs (++$iCol[$iRow] % 2 - 1); ?>"><A HREF="?Details&ID=<? echo $Data['id']; ?>"><? echo $Data['id']; ?></A></TD>
<TD CLASS="<? echo $CSS, abs (++$iCol[$iRow] % 2 - 1); ?>"><? echo $Data['date']; ?></TD>
<?	if (is_array ($Select))	{	foreach (array_keys ($Select) as $Field)	{	?><TD CLASS="<? echo $CSS, abs (++$iCol[$iRow] % 2 - 1); ?>"><? echo DisplayValue ($Data[$Field], $SelectValue[$Field]); ?></TD><?	}	}	?></TR>
<?
}
?>
</TABLE>
<?

global $ZydHashCache;
echo "\n<!--\n";
print_r ($Post);
echo "-->";

function FilterOptions ($Selected = NULL)	{
	$Options['index@crashed'] = "index - crashed";
	$Options['indexid@platform'] = "index - platform";
	$Options['indexid@spring'] = "index - spring";
	$Options['indexid@gamemod'] = "index - gamemod";
	$Options['indexid@sdl_version'] = "index - sdl_version";
	$Options['indexid@glew_version'] = "index - glew_version";
	$Options['indexid@al_vendor'] = "index - al_vendor";
	$Options['indexid@al_version'] = "index - al_version";
	$Options['indexid@al_renderer'] = "index - al_renderer";
	$Options['indexid@al_extensions'] = "index - al_extensions";
	$Options['indexid@alc_extensions'] = "index - alc_extensions";
	$Options['indexid@al_device'] = "index - al_device";
	$Options['indexid@gl_version'] = "index - gl_version";
	$Options['indexid@gl_vendor'] = "index - gl_vendor";
	$Options['indexid@gl_renderer'] = "index - gl_renderer";
	$Options['indexid@lobby_client_version'] = "index - lobby_client_version";
	$Options['index@contains_demo'] = "index - contains_demo";
	$Options['indexid@first_crash_line'] = "index - first crash line";
	$Options['indexid@first_crash_line_translated'] = "index - first crash line translated";
	$Options['indexid@player'] = "index - player";
	$Options['stacktrace@stacktrace'] = "stacktrace - stacktrace";
	$Options['stacktrace@translated'] = "stacktrace - translated";
	$Options['stacktrace@complete'] = "stacktrace - complete (no-filter)";
	$Data = GetSettingsList ();
	foreach (array_keys ($Data) as $ID)
		$Options["settingid@" . $ID] = "setting - " . $Data[$ID];

	foreach (array_keys ($Options) as $Key)
		$Options[$Key] = "<OPTION VALUE=\"" . $Key . "\"" . (is_array ($Selected) && is_numeric (array_search ($Key, $Selected)) ? " SELECTED" : "") . ">" . $Options[$Key] . "</OPTION>";
	return (join ("\n", $Options));
}

function Filter ($Type, $Selected)	{
	$Template = "<TD CLASS=\"NoPad\"><TABLE CLASS=\"Box\">
<TR><TH CLASS=\"Sub\">%TYPE% &nbsp; &nbsp; &nbsp; <FONT ONCLICK=\"GetObjFromID('%FILTERID%').value=''; GetObjFromID('S%FILTERID%').length=0;document.List.submit ();\">[X]</FONT></TH></TR>
<TR><TD>RegEx <INPUT NAME=\"Filter%FILTERID%\" VALUE=\"%REGEXVALUE%\" ONKEYUP=\"GetObjFromID('%FILTERID%').value='%KEY%REGEX_' + this.value;\"></TD></TR>
<INPUT TYPE=\"HIDDEN\" NAME=\"Filter[]\" ID=\"%FILTERID%\" VALUE=\"%KEY%REGEX_%REGEXVALUE%\">
<TR><TD><SELECT NAME=\"Filter[]\" SIZE=\"6\" MULTIPLE ID=\"S%FILTERID%\">%LIST%</SELECT></TD></TR>
</TABLE></TD>";
	foreach (array_keys ($Selected) as $SubType)	{
		unset ($Options, $RegExValue);
		foreach ($Selected[$SubType] as $Value)
			if (substr ($Value, 0, 6) == "REGEX_")
				$RegExValue = substr ($Value, 6);
		if ($Type == "settingid")	{
			$MySQL_Result = DB_Query ("SELECT settings.valueid, settingsdata.data FROM settings LEFT JOIN settingsdata ON settings.valueid=settingsdata.id WHERE settings.settingid='" . mysql_escape_string ($SubType) . "' GROUP BY settings.valueid ORDER BY settingsdata.data");
			while ($Data = mysql_fetch_assoc ($MySQL_Result))
				$Options[] = array ($Data['valueid'], $Data['data']);
			$DisplayType = GetSettingsList ($SubType);
		}	elseif ($Type == "index")	{
			$MySQL_Result = DB_Query ("SELECT " . mysql_escape_string ($SubType) . " FROM records GROUP BY " . mysql_escape_string ($SubType) . " ORDER BY " . mysql_escape_string ($SubType));
			while ($Data = mysql_fetch_assoc ($MySQL_Result))
				$Options[] = array ($Data[$SubType], $Data[$SubType]);
		}	elseif ($Type == "indexid")	{
			$MySQL_Result = DB_Query ("SELECT id, data FROM recordsdata WHERE field='" . mysql_escape_string ($SubType) . "' ORDER BY data");
			while ($Data = mysql_fetch_assoc ($MySQL_Result))
				$Options[] = array ($Data['id'], $Data['data']);
		}	elseif ($Type == "stacktrace" && $SubType == "translated")	{
			$MySQL_Result = DB_Query ("SELECT id, file, line FROM stacktracetranslated ORDER BY file, line");
			while ($Data = mysql_fetch_assoc ($MySQL_Result))
				$Options[] = array ($Data['id'], $Data['file'] . " (" . $Data['line'] . ")");
		}	elseif ($Type == "stacktrace" && $SubType == "stacktrace")	{
			$MySQL_Result = DB_Query ("SELECT id, file, address FROM stacktracedata ORDER BY file, address");
			while ($Data = mysql_fetch_assoc ($MySQL_Result))
				$Options[] = array ($Data['id'], $Data['file'] . " [" . $Data['address'] . "]");
		}
		foreach (array_keys ($Options) as $Option)
			$Options[$Option] = "<OPTION VALUE=\"" . $Type . "@" . $SubType . "@" . $Options[$Option][0] . "\"" . (is_numeric ($Selected[$SubType][$Options[$Option][0]]) || $Selected[$SubType][$Options[$Option][0]] ? " SELECTED" : "") . ">" . $Options[$Option][1] . "</OPTION>";
		$Return[] = str_replace (array ("%TYPE%", "%LIST%", "%KEY%", "%REGEXVALUE%", "%FILTERID%"), array (($DisplayType ? $DisplayType : $SubType), join ("\n", $Options), $Type . "@" . $SubType . "@", $RegExValue, uniqid ()), $Template);
	}
	return (join ("\n\n\n", $Return));
}


function DisplayValue ($Value, $Setup = NULL)	{
	if (!is_array ($Setup))
		return ($Value);
	if ($Setup[0] == "REPLACE")
		return (str_replace ($Setup[1], $Setup[2], $Value));
}
?>
<SCRIPT>
function GetObjFromID (id)	{
	if (document.getElementById)
		return (document.getElementById (id)); 
	else	if (document.all)
		return (document.all[id]);
	else	if (document.layers)
		return (document.layers[id]);
}


function CreateDiv (c, i, l, t)	{
	if (!i)
		i = 'DivID';
	if (!l)
		l = (window.MousePosX ? window.MousePosX + 15 : 300);
	if (!t)
		t = (window.MousePosY ? window.MousePosY - 5 : 300);
	var Div = document.createElement('div');
	Div.id=i;
	Div.style.position='absolute';
	Div.style.left=l + 'px';
	Div.style.top=t + 'px';
	Div.innerHTML=c;
	document.body.appendChild(Div);
}


function RemoveDiv (i)	{
	if (!i)
		i = 'DivID';
	if (GetObjFromID (i))
		document.body.removeChild  (GetObjFromID (i));
}


function InfoBox (title, content)	{
	CreateDiv ('<TABLE CLASS="InfoBox" ONCLICK="RemoveDiv (\'DivInfoBox\');"><TR><TH CLASS="InfoBox">' + title + '</TH></TR><TR><TD CLASS="InfoBox">' + content + '</TD></TR></TABLE>', 'DivInfoBox')
}


function ZydURL (url)	{
	return ('<A HREF="' + url + '">' + url + '</A>')
}
</SCRIPT>