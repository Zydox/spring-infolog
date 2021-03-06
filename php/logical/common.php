<?
global $Global;
require ("site.config.php");
$Global['BaseURL'] = $_SERVER['HTTP_HOST'] . dirname ($_SERVER['REQUEST_URI']);

function DoxTime ()	{
	$Time = microtime ();
	return (strstr ($Time, " ") + str_replace (strstr ($Time, " "), " ", $Time));
}


function DB_Query ($Query)	{
	global $Config;
	global $MySQL_Connection;
	if (!$MySQL_Connection)	{
		$MySQL_Connection = mysql_connect ($Config['MySQL']['host'], $Config['MySQL']['user'], $Config['MySQL']['passwd']);
		mysql_select_db ($Config['MySQL']['db'], $MySQL_Connection);
	}
	$Return = mysql_query ($Query, $MySQL_Connection);
	return ($Return);
}


function GetReport ($ID)	{
	$MySQL_Result = DB_Query ("SELECT * FROM records WHERE id='" . mysql_escape_string ($ID) . "'");
	$Return = mysql_fetch_assoc ($MySQL_Result);
	foreach (array_keys ($Return) as $Key)
		if (substr ($Key, -2) == "id" && $Key != "id" && $Return[$Key])
			$KeyID[substr ($Key, 0, -2)] = $Return[$Key];
	$MySQL_Result = DB_Query ("SELECT field, data FROM recordsdata WHERE ID='" . join ("' OR ID='", $KeyID) . "'");
	while ($Data = mysql_fetch_assoc ($MySQL_Result))
		$Return[$Data['field']] = $Data['data'];
	return ($Return);
}


function GetSettingsList ($ID = NULL)	{
	$MySQL_Result = DB_Query ("SELECT id, data FROM settingsdata WHERE type='setting'");
	while ($Data = mysql_fetch_assoc ($MySQL_Result))
		$Return[$Data['id']] = $Data['data'];
	asort ($Return);
	if ($ID)
		return ($Return[$ID]);
	return ($Return);
}


function GetSettings ($ID)	{
	$MySQL_Result = DB_Query ("SELECT settingsdata.setting, settingsdata.value FROM settings LEFT JOIN settingsdata ON settings.settingid=settingsdata.id WHERE reportid='" . mysql_escape_string ($ID) . "'");
	while ($Data = mysql_fetch_assoc ($MySQL_Result))
		$Return[$Data['setting']] = $Data['Value'];
	return ($Return);
}


function GetStacktrace ($ID)	{
	$MySQL_Result = DB_Query ("SELECT stacktrace.orderid, stacktrace.raw, stacktracedata.*, stacktracetranslated.file AS cppfile, stacktracetranslated.line AS cppline FROM stacktrace LEFT JOIN stacktracedata ON stacktrace.stacktraceid=stacktracedata.id LEFT JOIN stacktracetranslated ON stacktrace.translatedid=stacktracetranslated.id WHERE reportid='" . mysql_escape_string ($ID) . "'");
	while ($Data = mysql_fetch_assoc ($MySQL_Result))
		$Return[$Data['orderid']] = $Data;
	return ($Return);
}


function GetCrashes ()	{
	$MySQL_Result = DB_Query ("SELECT COUNT(id) FROM records WHERE crashed='1'");
	$Crashed = join ("", mysql_fetch_assoc ($MySQL_Result));
	$MySQL_Result = DB_Query ("SELECT Data.id as settingid, Data.data AS setting, Value.id as valueid, Value.data AS value, COUNT(records.id) AS Crashes
FROM records 
LEFT JOIN settings ON records.id=settings.reportid
LEFT JOIN settingsdata AS Data on settings.settingid=Data.id
LEFT JOIN settingsdata AS Value on settings.valueid=Value.id
WHERE records.crashed='1'
GROUP BY settings.settingid, settings.valueid");
	while ($Data = mysql_fetch_assoc ($MySQL_Result))	{
		$Return['Data'][$Data['settingid']][$Data['valueid']] = array ("ID" => $Data['valueid'], "Reports" => $Data['Crashes'], "Percentage" => number_format ($Data['Crashes'] / $Crashed * 100, 1, ".", ""));
		$Return['Index']['Settings'][$Data['settingid']] = $Data['setting'];
		$Return['Index']['Values'][$Data['valueid']] = $Data['value'];
	}
	ksort ($Return['Data']);
	foreach (array_keys ($Return['Data']) as $Setting)
		ksort ($Return['Data'][$Setting]);
	return ($Return);
}


function GetCrashes2 ()	{
	$MySQL_Result = DB_Query ("SELECT COUNT(id) FROM records WHERE crashed='1'");
	$Crashed = join ("", mysql_fetch_assoc ($MySQL_Result));
	$MySQL_Result = DB_Query ("SELECT stacktracedata.*, COUNT(records.id) AS Crashes FROM records LEFT JOIN stacktrace ON records.id=stacktrace.reportid AND stacktrace.orderid='1' LEFT JOIN stacktracedata ON stacktrace.stacktraceid=stacktracedata.id WHERE crashed='1' GROUP BY stacktrace.stacktraceid");
	while ($Data = mysql_fetch_assoc ($MySQL_Result))	{
		$Return['Data'][$Data['file']][$Data['address']]['Reports'] += $Data['Crashes'];
		$Return['Data'][$Data['file']][$Data['address']]['Percentage'] = number_format ($Return['Settings'][$Data['file']][$Data['address']]['Reports'] / $Crashed * 100, 1, ".", "");
		$Return['Data'][$Data['file']][$Data['address']]['ID'] = $Data['id'];
	}
	ksort ($Return['Data']);
	foreach (array_keys ($Return['Data']) as $Setting)
		ksort ($Return['Data'][$Setting]);
	return ($Return);
}


function Download ($URL)	{
	$TempFile = "/tmp/DL-" . uniqid ("");
	exec ("/usr/bin/wget --quiet --timeout=30 --dns-timeout=30 --connect-timeout=30 --output-document=" . $TempFile . " \"" . $URL . "\"");
	if (filesize ($TempFile) > 0)	{
		$FP = fopen ($TempFile, "r");
		$Data = fread ($FP, filesize ($TempFile));
		fclose ($FP);
		if (file_exists ($TempFile))	{
			unlink ($TempFile);
			return ($Data);
		}
	}	elseif (file_exists ($TempFile))	{
		unlink ($TempFile);
		sleep (5);
		if ($Loop < 3)
			return (Download ($URL, $Loop + 1));
	}
}


function GetExtensionMapping ()	{
	$MySQL_Result = DB_Query ("SELECT Data, Updated FROM cache WHERE Field='ExtensionMapping'");
	$Data = mysql_fetch_assoc ($MySQL_Result);
	if ($Data && $Data['Updated'] >= time () - 86400)	{	// Uses the cache table if available and results are less than one day old...
		return (unserialize ($Data['Data']));
	}	else	{
		$URL = "http://www.opengl.org/registry/";
		$Page = Download ($URL);
		foreach (explode ("\n", $Page) as $Line)	{
			if (strstr ($Line, "\">GL"))	{
				$Line = substr (strstr ($Line, "<a href=\""), 9);
				$Return[str_replace (array ("&amp;"), array ("&"), substr ($Line, strpos ($Line, "\">") + 2, -4))] = $URL . substr ($Line, 0, strpos ($Line, "\">"));
			}
		}
		DB_Query ("REPLACE INTO cache SET Field='ExtensionMapping', Data='" . mysql_escape_string (serialize ($Return)) . "', Updated='" . time () . "'");
		return ($Return);
	}
}
?>