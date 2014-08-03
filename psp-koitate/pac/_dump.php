<?php

require 'inc.functions.php';

define ('OUTPUT_TEXTMODE', false);

define ('SUCCEEDED', "SUCCEEDED");
define ('SUCCEEDED_WITH_WARNING', "SUCCEEDED_WITH_WARNING");
define ('FAILED', "FAILED");

class Leave extends Exception {}
class LocalException extends Exception {}
class NotEnoughInputException extends LocalException {}
class ContinuableLocalException extends LocalException {}
###########################################################################################################

$asmscript_inputs = $_SERVER['argv'];

$asmscript_inputs = array_slice($_SERVER['argv'], 1);
$asmscript_inputs = expand_argspec($asmscript_inputs, $base_dir = '.');

natcasesort($asmscript_inputs);

// No matching file
if ( count($asmscript_inputs) < 1 )
{
	usage(sprintf("No matching input\r\nphp %s [<filespec.*pac>...|<filespec>...]", $_SERVER['argv'][0]));
	exit;
}
$succeeded = 0;
$failed = 0;

begin_output();
$scrcounter = 0;
while ( count($asmscript_inputs) > 0 )
{
	if ( $scrcounter % 20 == 0 )
	{
		output_header();
	}
	$asmscript_filepath = array_shift($asmscript_inputs);
	process_input_path($asmscript_filepath);
	++$scrcounter;
}
end_output();

fprintf(STDERR, "Processed: %d inputs, %d SUCCESS, %d FAILURE\r\n",
	$succeeded + $failed,
	$succeeded,
	$failed);

//#####################################################################################################################
// end driver program


function process_input_path($asmscript_filepath)
{
	global $succeeded;
	global $failed;
	list($base, $ext) = break_filename($asmscript_filepath);
	$scriptname = basename($base);
	fprintf(STDERR, "* %s\r\n", basename($asmscript_filepath));

	try
	{
		$databin = @file_get_contents($asmscript_filepath);
		if ( $databin === FALSE )
		{
			fprintf(STDERR, "%s: Cannot open specified file\r\n", basename($asmscript_filepath));
			throw new Leave();
		}
		
		$data = array();
		$current_offset = 0;
		$data = array_merge($data,
		array(
		  current(unpack("V", substr($databin, $current_offset, 4))),
		  current(unpack("V", substr($databin, $current_offset + 4, 4))),
		  current(unpack("v", substr($databin, $current_offset + 0x0C, 2))),
		  current(unpack("v", substr($databin, $current_offset + 0x0E, 2))),
		));

		$current_offset = 0x10;
		$data = array_merge($data,
		array(
		  current(unpack("V", substr($databin, $current_offset, 4))),
		  current(unpack("V", substr($databin, $current_offset + 4, 4))),
		  current(unpack("V", substr($databin, $current_offset + 8, 4))),
		  current(unpack("V", substr($databin, $current_offset + 0xC, 4))),
		));

		$data = array_merge($data,
		array(
		  $data[6] - $data[4] + 1,
		  $data[7] - $data[5] + 1,
		));

		$current_offset = 0x20;
		$data = array_merge($data,
		array(
		  current(unpack("v", substr($databin, $current_offset + 4, 2))),
		  current(unpack("v", substr($databin, $current_offset + 6, 2))),
		));

//if($data[0] != 1 || $data[1] != 1 ) 			throw new Leave();
if($data[8] != $data[2] || $data[9] != $data[3] ) 			throw new Leave();
//if($data[2] != 480 || $data[3] != 272 ) 			throw new Leave();
rename($asmscript_filepath, dirname($asmscript_filepath). "/ok/$base.$ext");


		if ( OUTPUT_TEXTMODE )
		{
		}
		else
		{
			$val = array_merge((array)$scriptname, $data);
			static $fmt = '
			<tr>
			<td>%1$s</td>
			<td>0x%2$08X</td>
			<td>0x%3$08X</td>
			<td>%4$d</td>
			<td>%5$d</td>
			<td>0x%6$08X (%6$d)</td>
			<td>0x%7$08X (%7$d)</td>
			<td>%10$d</td>
			<td>%11$d</td>
			<td>0x%12$08X (%12$d)</td>
			<td>0x%13$08X (%13$d)</td>
			</tr>
			';

			echo vsprintf($fmt, $val);
		}
		++$succeeded;
	}
	catch(Leave $e)
	{
		++$failed;
	}

}




function begin_output()
{
	if ( OUTPUT_TEXTMODE )
	{
	}
	else
	{
		@header ( "Content-Type: text/html; charset=utf-8'" );
		echo "<html>
		<head><title></title>
		<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
		<style>
		table {
			border: 0;
			text-align: center;
			width: 90%;
			margin: 0 50px;
		}
		table.thin { border-spacing: 0; }
		td {
			border: 1px inset yellow;
			padding:.3em 5px;
		}
		.header { background: #ccf; }
		</style>
		</head><body>
	<table class='thin'>
	";
	}
}

function end_output()
{
	if ( OUTPUT_TEXTMODE )
	{
	}
	else
	{
		echo "</table>
	</body></html>";
	}
}

function output_header()
{
	if ( OUTPUT_TEXTMODE )
	{
	}
	else
	{
		static $header = "
		<tr class='header'>
		<th>Name</th>
		<th>@00</th>
		<th>@04</th>
		<th>W</th>
		<th>H</th>
		<th>@10</th>
		<th>@14</th>
		<th>DiffX</th>
		<th>DiffY</th>
		<th>@24</th>
		<th>@26</th>
		</tr>
		";
		echo $header;
	}
}

?>