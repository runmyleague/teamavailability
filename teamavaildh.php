<?php
$pageAccessLevel = 3;
$toroot = "../../";
require_once $toroot."inc/secure.php";
if (securitycheck($pageAccessLevel, $toroot) != 1) {
  exit;
}
include_once($toroot."inc/sql.php");
include($toroot."inc/timeslots.php");
include($toroot."inc/getwriteflag.php");
include($toroot.'inc/leagueinfo.php'); // creates $leaguename, $leagueabbr, $leagueaddy,$leaguecity,$leaguestate,$leaguezip,$leaguetitle

$db = &sqlconnect();

$testing=false;

#lockout!
$level = getUserLevel();
/*$allowchanges = getwriteflag('ta');
if ($level < 9 && $allowchanges != 1) {
	print "Currently only Administrators can edit team DHavailability.\n";
	exit;
}*/
if($level<=3)
	$teamID=myID();
else
{
	$teamID = (!empty($_POST['team'])) ? $_POST['team'] : '';
	$teamID = (!empty($_POST['teamID'])) ? $_POST['teamID'] : $teamID;
	$teamID = (!empty($_GET['team'])) ? $_GET['team'] : $teamID;
	$teamID = (!empty($_GET['teamID'])) ? $_GET['teamID'] : $teamID;
}
//get team info
if ($teamID != 0) {
  $sql = "select teams.ownerID, teams.name, teams.teamID from teams where teamID=$teamID";
  $rs = sqlexecute($db,$sql);
	$row = $rs->fetchRow();
  $ownerID = $row['ownerID'];
  $teamID = $row['teamID'];
  $teamname = $row['name'];
  $rs->free();
}

$patternID=$_REQUEST['patternID'];
$settimes=$_REQUEST['settimes'];
//$dowMap = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$dowMap = array(5=>'Fri', 6=>'Sat', 0=>'Sun', 1=>'Mon', 2=>'Tue', 3=>'Wed', 4=>'Thu');
$dowuse=array();

//get existing settings for this pattern
if($patternID)
{
	$sql='SELECT patnum, name, AmaxDH, AconsecDH, BmaxDH, BconsecDH FROM SCHEDpatterns WHERE patternID='.$patternID;
	$rs=sqlexecute($db,$sql);
	$data=$rs->fetchRow();
	$totaldhs=$data['AmaxDH']+$data['BmaxDH'];

	$sql='SELECT weekday, DHavail FROM SCHEDdayavail WHERE pattID='.$patternID;
	$rs=sqlexecute($db,$sql);
	while($row=$rs->fetchRow())
	{
		$data['DHavail'][$row['weekday']][$row['DHavail']]=' CHECKED';
		$cts[$row['DHavail']]++;
	}
	$rs->free();

	//list of days we need DH availability for
	$sql='SELECT weekday,avail FROM SCHEDdayavail WHERE pattID='.$patternID;
	$rs=sqlexecute($db,$sql);
	while($row=$rs->fetchRow())
	{
		if($row['avail'])
			$dowuse[$row['weekday']]=$dowMap[$row['weekday']];
		
		//DH=P1 okay when avail < P1
		switch($row['avail'])
		{
			case 0:
				$disable[$row['weekday']][3]=' DISABLED';
			//case 3:
				$disable[$row['weekday']][2]=' DISABLED';
			//case 2:
				$disable[$row['weekday']][1]=' DISABLED';
		}
	}
	$rs->free();
}


//save
if($_POST['saveDHavail'])
{
	//validate input
	include_once($toroot.'inc/class.validateforms.php');
	$valid= new FormValidator;
	$valid->isEmptyArrayAny('DHavail', $dowuse, 'ERROR: You must select your DHavailability for available days.');
	if($valid->isError())
		$errors=$valid->getErrorList();

	if(empty($errors) && $_POST['confirm'])
	{
		//$fields=('teamID','Amaxgames','Amaxconsec','Bmaxgames','Bmaxconsec');
		//$values=($_POST['teamID',$_POST['Amaxgames'],$_POST['Amaxconsec'],$_POST['Bmaxgames'],$_POST['Bmaxconsec']);
		include_once($toroot.'inc/class.processforms.php');
		$up=new processforms;
		if($patternID)
		{
			$up->UpdateDB('SCHEDpatterns',$patternID,'patternID');
			if(is_array($_POST['DHavail']))
			{
				foreach($_POST['DHavail'] AS $_day=>$_DHavail)
				{
					$fields=array('DHavail');
					$values=array($_DHavail);
					$sql=buildupdateset('SCHEDdayavail',$fields,$values);
					$sql.=' WHERE pattID='.$patternID.' AND weekday='.$_day;
					sqlexecute($db,$sql);
				}
			}
			$settimes=true;
		}
		else
			$errors['patternID']='ERROR: Your selections cannot be saved because the pattern ID is missing.';
	}
	else
		$needtoconfirm=true;
}
elseif($_POST['savetimes'])
{
	//validate input
	include_once($toroot.'inc/class.validateforms.php');
	$valid= new FormValidator;
	$valid->isEmptyArrayAny('start', $dowuse, 'ERROR: You must select your earliest possible start time for all available days.');
	$valid->isEmptyArrayAny('end', $dowuse, 'ERROR: You must select your latest possible start time for all available days.');
	if($valid->isError())
		$errors=$valid->getErrorList();
	foreach($_POST['start'] AS $_day=>$_st)
	{
		if(strtotime($_st) > strtotime($_POST['end'][$_day]))
			$errors[$_day]='<FONT CLASS="error">ERROR: Earliest Possible Start time must be before Latest Possible Start Time.</FONT>';
	}

	if(empty($errors) && $_POST['confirmtimes'])
	{
		include_once($toroot.'inc/class.processforms.php');
		$up=new processforms;
		if($patternID)
		{
			if(is_array($_POST['start']))
			{
				foreach($_POST['start'] AS $_day=>$_start)
				{
					if($_POST['night'][$_day]=='on')
					{
						//night avail always ends at 8:30PM
						$_POST['end'][$_day]='20:30';
						$night=1;
					}
					else
					{
						$night=0;
						//7:00PM latest avail for non-night games
						if(strtotime($_POST['end'][$_day])>strtotime('19:00'))
							$_POST['end'][$_day]='19:00';
					}
					$fields=array('start','end','night');
					$values=array("'$_start'","'".$_POST['end'][$_day]."'","'".$night."'");
					$sql=buildupdateset('SCHEDdayavail',$fields,$values);
					$sql.=' WHERE pattID='.$patternID.' AND weekday='.$_day;
					sqlexecute($db,$sql);
					if($testing)
						echo $sql.'<BR>';
				}
				$host=$_SERVER['HTTP_HOST'];
				if(!$testing)
					header("Location: http://$host/members/team/teamavpattern.php?teamID=$teamID&patternID=$patternID");
			}
		}
		else
			$errors['patternID']='ERROR: Your selections cannot be saved because the pattern ID is missing.';
	}
	else
		$data=$_POST;
	$settimes=true;
}

if (isset($_POST['clearschedule']))
  $modename = "Start Over";
else
  $modename = "Edit Schedule - ".$data['name'];



?>
<!DOCTYPE HTML>
<HTML>
	<head>
		<title>My Team DHavailability <?php print "- $modename"; ?></title>
<?php
//put scripts here-> head.php include closes head and opens body

/*echo '<LINK HREF="'.$toroot.'javascripts/datestuff/datepicker.css" REL="stylesheet" TYPE="text/css">*/
echo '<link rel="stylesheet" href="'.$toroot.'javascripts/iphone-checkbox.css" type="text/css" media="screen">';
 include_once($toroot.'template/head.php'); 

//check that teamID is set
include_once($toroot.'inc/chooseteam.php');
$printscreen=teamSelected($teamID,$level);

if ($printscreen) 
{
	if($level>=5)
		switchLink('Team');
	//check if write access to schedules is on
	include_once($toroot.'inc/getwriteflag.php');
	$accesson=getwriteflag('ta');
	if($accesson)
	{
		$sql='SELECT maxage FROM agegroups, teams WHERE agegroups.age=teams.age AND teams.teamID='.$teamID;
		$maxage=sqlgetone($db,$sql);
		$youngaccess=getwriteflag('tay');
		if( $maxage<=12 && !$youngaccess )
			$accesson=false;
	}
}
if($accesson || ($level>=9 && $printscreen))
{
?>

<h1>My Team availability <?php print "- $modename"; ?></H1>
<h2><?php print $teamname; ?></H2>
<?php
	if($testing)
	{
		echo '<PRE>';
		print_r($_POST);
		echo '</PRE>';
	}	

	require('teamavailheadnew.php');
	if($errors)
	{
		foreach($errors AS $_err)
			echo '<BR>'.$_err;
	}

	if(empty($patternID))
	{
		echo '<DIV CLASS="errorbox">The weekly pattern you want to edit was not indicated, please <A HREF="teamavail.php?teamID='.$teamID.'">click here to select a pattern to edit</A>.</DIV>';
	}
	elseif($settimes)
	{
		if(empty($errors) && $_POST['savetimes']) //confirm time selections
		{
			if(empty($_POST['confirmtimes']))
			{
				echo '<DIV CLASS="noticebox">
						<FORM ACTION="" METHOD="post" CLASS="colored">
							<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
							<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">';
				foreach($dowMap AS $_day=>$_dayn)
				{

					echo '<U>'.$_dayn.'</U>
						<INPUT TYPE="hidden" NAME="start['.$_day.']" VALUE="'.$_POST['start'][$_day].'">
							<INPUT TYPE="hidden" NAME="end['.$_day.']" VALUE="'.$_POST['end'][$_day].'">
							<INPUT TYPE="hidden" NAME="night['.$_day.']" VALUE="'.$_POST['night'][$_day].'">';
					if($_POST['start'][$_day] && $_POST['start'][$_day]<>'NULL')
						echo ' - You have indicated your team can start its first game as early as '.date('g:i A', strtotime($_POST['start'][$_day])).' and as late as '.date('g:i A', strtotime($_POST['end'][$_day])).' (night game = '.($_POST['night'][$_day] ? 'yes' : 'no').'). If you are scheduled to play a DH, the 2nd game could start as late as '.date('g:i A', strtotime($_POST['end'][$_day])).'<BR>';
					else
						echo ' - You have indicated your team is not available to play on this day, no matter what.<BR>';
				}
				echo '<BR>Is that correct?
						<INPUT TYPE="hidden" NAME="savetimes" VALUE="SAVE">
						<INPUT TYPE="submit" NAME="confirmtimes" VALUE="YES">
					</FORM>
					<FORM METHOD="post" ACTION="">
						<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
						<INPUT TYPE="submit" NAME="settimes" VALUE="NO">
					</FORM>
					</DIV>';
			}
			else
			{
				echo '<DIV CLASS="errorbox">Time preferences saved.</DIV>';
			}
		}
		else  // time input form
		{
			if(empty($data['start']))
			{
				$sql='SELECT weekday, avail, DHavail, start, end, night FROM SCHEDdayavail WHERE pattID='.$patternID;
				$rs=sqlexecute($db,$sql);
				while($row=$rs->fetchRow())
				{
					$data['avail'][$row['weekday']]=$row['avail'];
					$data['DHavail'][$row['weekday']]=$row['DHavail'];
					$data['start'][$row['weekday']]=$row['start'];
					$data['end'][$row['weekday']]=$row['end'];
					$data['night'][$row['weekday']]=$row['night'];
				}
				$rs->free();
			}
			else
			{
				$sql='SELECT weekday, avail, DHavail FROM SCHEDdayavail WHERE pattID='.$patternID;
				$rs=sqlexecute($db,$sql);
				while($row=$rs->fetchRow())
				{
					$data['avail'][$row['weekday']]=$row['avail'];
					$data['DHavail'][$row['weekday']]=$row['DHavail'];
				}
				$rs->free();
			}

			function time_row($day,$data,$dowMap)
			{
				if($data['avail'][$day]==0)
					$class="grey-dark";
				elseif(in_array($day, array(5,6,0,1)))
					$class='rowsimple';
				else
					$class='rowsimple2';
				$rt='<TR CLASS="'.$class.'">';
				if($day==5)
					$rt.='<TD ROWSPAN="4">A-'.$data['patnum'].'</TD>';
				elseif($day==2)
					$rt.='<TD ROWSPAN="3">B-'.$data['patnum'].'</TD>';
				$rt.= '<TD>'.$dowMap[$day].'</TD>';

				if($data['avail'][$day]==0)
					$rt.= '<TD>N/A<INPUT TYPE="hidden" NAME="start['.$day.']" VALUE="NULL"></TD>
						<TD>N/A<INPUT TYPE="hidden" NAME="end['.$day.']" VALUE="NULL"></TD>
						<TD>N/A<INPUT TYPE="hidden" NAME="night['.$day.']" VALUE="NULL"></TD>';
				else
				{
					//night avail
					$rt.= '<TD><INPUT TYPE="checkbox" NAME="night['.$day.']" CLASS="switch" ID="night-'.$day.'"'.(!empty($data['night'][$day]) ? ' CHECKED' : '').'></TD>';
					$etime1= '17:30:00';
					$etime2= '19:00:00';
					//start time
					$rt.= '<TD><SELECT NAME="start['.$day.']">
							<OPTION VALUE="">SELECT';
					$t=new DateTime("09:00:00");
					while($t->format('H:i:s') <= $etime1)
					{
						$rt.= '<OPTION VALUE="'.$t->format('H:i:s').'"';
						if($data['start'][$day]==$t->format('H:i:s'))
							$rt.= ' SELECTED';
						$rt.='>'.$t->format('g:i A');
						if($t->format('H:i:s')=='17:30:00')
						{
							$rt.= '<OPTION VALUE="17:45:00"';
							if($data['start'][$day]=="17:45:00")
								$rt.= ' SELECTED';
							$rt.='>5:45 PM';
						}
						$t->add(date_interval_create_from_date_string('30 minutes'));
					}
					$rt.= '</SELECT></TD>';
					//end time
					$rt.= '<TD><SELECT NAME="end['.$day.']" ID="end-'.$day.'">
							<OPTION VALUE="">SELECT';
					if($data['night'][$day])
						$rt.= '<OPTION VALUE="20:30" SELECTED>8:30 PM';
					else
					{
						$t=new DateTime("09:00:00");
						while($t->format('H:i:s') <= $etime2)
						{
							$rt.= '<OPTION VALUE="'.$t->format('H:i:s').'"';
							if($data['end'][$day]==$t->format('H:i:s'))
								$rt.= ' SELECTED';
							$rt.='>'.$t->format('g:i A');
							$t->add(date_interval_create_from_date_string('30 minutes'));
						}
					}
					$rt.= '</SELECT></TD></TR>';
				}
				return $rt;
			}

			echo '<H2>Team Availability by Time of Day</H2>					
					<P>On days where you have indicated it is poissible for your team to play (P1, P2 or P3) a single game only, please select the earliest possible start time and the latest possible start time. For days where a DH is possible, please select the earliest possible start time for your team\'s first game of the day and the latest possible start time for the 2nd game of the DH. You will not be scheduled to start a game outside of this time range.</P>
			<FORM ACTION="" METHOD="post" CLASS="colored">
				<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
				<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
				<INPUT TYPE="hidden" NAME="settimes" VALUE="1">
				<TABLE CELLSPACING="0" CELLPADDING="3">
					<TR CLASS="head">
						<TD></TD>
						<TD></TD>
						<TD>Availability for Night Games</TD>
						<TD>Earliest Possible Start Time for First Game on this Day</TD>
						<TD>Latest Possible Start Time for Last Game on this Day</TD>
					</TR>'.time_row(5,$data,$dowMap).
							time_row(6,$data,$dowMap).
							time_row(0,$data,$dowMap).
							time_row(1,$data,$dowMap).
							time_row(2,$data,$dowMap).
							time_row(3,$data,$dowMap).
							time_row(4,$data,$dowMap).
				'<TR><TD COLSPAN="5" ALIGN="left" CLASS="notes">NOTE: If daytype corresponds to a daytype that your team is eligible for a possible DH, then the minimum time range must be 3 hours.   <B>Example:</B> Saturday is a possible DH therefore time preference must extend for at least 3 hours. If 9AM and 12PM is selected it means DH will begin at 9AM.</TD></TR>
					<TR><TD COLSPAN="5" ALIGN="center">
					<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
					<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
					<INPUT TYPE="submit" NAME="savetimes" VALUE="SAVE">
					</TD></TR>
				</TABLE>
			</FORM>';
		}
	}
	elseif(empty($errors) && $needtoconfirm)  //confirm daily DHavailability settings
	{
		if($_POST['saveDHavail'])
		{
			if($_POST['confirm1'])
			{
				if($_POST['confirm2'])
				{
					echo '<DIV CLASS="noticebox">You have indicated that you are willing to play DH on '.$_POST['AconsecDH'].' consecutive days from FRI to MON of this week.<BR>
						You also indicated that you willing to play DH on '.$_POST['BconsecDH'].' consectutive days from TUE to THU of this week.
						<FORM ACTION="" METHOD="post">Is this correct?';
					foreach($_POST AS $_a=>$_b)
					{
						if($_a=='DHavail')
						{
							foreach($_b AS $_day=>$_DHavail)
								echo '<INPUT TYPE="hidden" NAME="DHavail['.$_day.']" VALUE="'.$_DHavail.'">';
						}
						else
							echo '<INPUT TYPE="hidden" NAME="'.$_a.'" VALUE="'.$_b.'">';
					}
					echo '<INPUT TYPE="hidden" NAME="confirm1" VALUE="Yes">
						<INPUT TYPE="hidden" NAME="confirm2" VALUE="Yes">
						<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
						<INPUT TYPE="submit" NAME="confirm" VALUE="Yes">
						</FORM>
						<FORM ACTION="" METHOD="post">
							<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
							<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
							<INPUT TYPE="submit" NAME="editDHavail" VALUE="No">
						</FORM>
						</DIV>';

				}
				else
				{
					echo '<DIV CLASS="noticebox">You have indicated that you do NOT want more than '.$_POST['AmaxDH'].' DH scheduled from FRI to MON of this week.<BR>
						You also indicated that you do NOT want more than '.$_POST['BmaxDH'].' DH scheduled from TUE to THU of this week.<BR>
						So for this week as a whole you want a MAX of '.($_POST['AmaxDH']+$_POST['BmaxDH']).' DH.
						<FORM ACTION="" METHOD="post">Is this correct?';
					foreach($_POST AS $_a=>$_b)
					{
						if($_a=='DHavail')
						{
							foreach($_b AS $_day=>$_DHavail)
								echo '<INPUT TYPE="hidden" NAME="DHavail['.$_day.']" VALUE="'.$_DHavail.'">';
						}
						else
							echo '<INPUT TYPE="hidden" NAME="'.$_a.'" VALUE="'.$_b.'">';
					}
					echo '<INPUT TYPE="hidden" NAME="confirm1" VALUE="Yes">
						<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
						<INPUT TYPE="submit" NAME="confirm2" VALUE="Yes">
						</FORM>
						<FORM ACTION="" METHOD="post">
							<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
							<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
							<INPUT TYPE="submit" NAME="editDHavail" VALUE="No">
						</FORM>
						</DIV>';
				}
			}
			else
			{
				$p1=$p2=$p3=$no=array();
				foreach($_POST['DHavail'] AS $_day=>$_DHavail)
				{
					switch($_DHavail)
					{
						case 1:
							$p1[]=$dowMap[$_day];
							break;
						case 2:
							$p2[]=$dowMap[$_day];
							break;
						case 3:
							$p3[]=$dowMap[$_day];
							break;
						default:
							$no[]=$dowMap[$_day];

					}

				}
				echo '<DIV CLASS="noticebox">';
				if($p1)
					echo 'You have indicated your primary preference is to play a DH on the following days: '.implode(', ',$p1).'.<BR>';
				if($p2)
					echo 'You have indicated that if your primary preference can\'t be met, you are willing to play a DH on: '.implode(', ',$p2).'.<BR>';
				if($p3)
					echo 'You have indicated that as a last resort only, you are willing to play a DH on: '.implode(', ',$p3).'.<BR>';
				if($no)
					echo 'You have indicated that is not possible for your team to play a DH on: '.implode(', ',$no).'.<BR>';
				echo '<FORM ACTION="" METHOD="post">Are these statements correct?';
				foreach($_POST AS $_a=>$_b)
				{
					if($_a=='DHavail')
					{
						foreach($_b AS $_day=>$_DHavail)
							echo '<INPUT TYPE="hidden" NAME="DHavail['.$_day.']" VALUE="'.$_DHavail.'">';
					}
					else
						echo '<INPUT TYPE="hidden" NAME="'.$_a.'" VALUE="'.$_b.'">';
				}
				echo '<INPUT TYPE="submit" NAME="confirm1" VALUE="Yes">
					</FORM>
					<FORM ACTION="" METHOD="post">
							<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
							<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
							<INPUT TYPE="submit" NAME="editDHavail" VALUE="No">
					</FORM>
					</DIV>';

			}

		}
	}
	else //set daily DH DHavailability settings
	{
		foreach($_POST AS $_a=>$_b)
		{
			if($_a=='DHavail')
			{
				foreach($_b AS $_day=>$_DHavail)
					$data['DHavail'][$_day][$_DHavail]=' CHECKED';
			}
			else
				$data[$_a]=$_b;
		}
		if($testing)
		{
			echo '<PRE>';
			print_r($data);
			echo '</PRE>';
		}
		echo '<P>Please label each daytype in this weekly pattern according to your ability/preferences for playing a DH on that daytype. Each daytype needs a single selection from the four possible options. Inputs for the last two columns apply to the full period encompassed by each respective sub-pattern.</P>
			<U>TIPS:</U><UL>
			<LI>NOTE: If you have no limit, simply enter the # of days your team is available to play in each period.
			<LI>NOTE: Teams selecting zero DHs in sub-pattern A are required to play DH when scheduled (home/away) against non-local teams requiring a long distance drive (usually 60 to 100 miles).
			<LI>NOTE: Understand that during self-scheduling mode, teams will be allowed to schedule HOME DH on all days except those designated NOT POSSIBLE. However, away DH (scheduled by your opponents) shall be restricted to those daytypes you have designated as DH-P1.
			<LI>NOTE: When not in self-scheduling mode i.e., ENYTB admin is scheduling, P1 will be given top preference, P2 will be given 2nd preference and OIN will be used only as a last resort, both for home and away DH.
			</UL>';

		echo '<H4>DH Constraints Pattern '.$data['patnum'];
		if($data['name'])
			echo ' - '.$data['name'];
		echo '</H4>';
		echo '<FORM ACTION="" METHOD="post">
				<TABLE CELLSPACING="0" CELLPADDING="3">
					<TR CLASS="head">
						<TD></TD>
						<TD></TD>
						<TD>Primary Preference (P1)</TD>
						<TD>Secondary Preference (P2)</TD>
						<TD>If & Only if Necessary (P3)</TD>
						<TD>NOT Possible (NO)</TD>
						<TD>MAX DH</TD>
						<TD>Will Play # Days in a Row with DH</TD>
					</TR>
					<TR CLASS="head"><TD COLSPAN="8"></TD></TR>
					<TR CLASS="row">		
						<TD ROWSPAN="4"><H5>A-'.$data['patnum'].'</H5></TD>
						<TD>FRI</TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[5]" VALUE="1" CLASS="P1"'.$data['DHavail'][5][1].$disable[5][1].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[5]" VALUE="2" CLASS="P2"'.$data['DHavail'][5][2].$disable[5][2].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[5]" VALUE="3" CLASS="P3"'.$data['DHavail'][5][3].$disable[5][3].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[5]" VALUE="0" CLASS="NO"'.$data['DHavail'][5][0].'></TD>
						<TD ROWSPAN="4"><SELECT NAME="AmaxDH" ID="AmaxDH">';
		for($i=0;$i<=4;$i++)
		{
			echo '<OPTION';
			if($data['AmaxDH']==$i)
				echo ' SELECTED';
			echo' >'.$i.'</OPTION>';
		}
		echo '			</SELECT></TD>
						<TD ROWSPAN="4"><SELECT NAME="AconsecDH" ID="AconsecDH">';
		for($i=0;$i<=4;$i++)
		{
			echo '<OPTION';
			if($data['AconsecDH']==$i)
				echo ' SELECTED';
			echo '>'.$i.'</OPTION>';
		}
		echo '			</SELECT></TD>
					</TR>
					<TR CLASS="row">
						<TD>SAT</TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[6]" VALUE="1" CLASS="P1"'.$data['DHavail'][6][1].$disable[6][1].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[6]" VALUE="2" CLASS="P2"'.$data['DHavail'][6][2].$disable[6][2].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[6]" VALUE="3" CLASS="P3"'.$data['DHavail'][6][3].$disable[6][3].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[6]" VALUE="0" CLASS="NO"'.$data['DHavail'][6][0].'></TD>
					</TR>
					<TR CLASS="row">
						<TD>SUN</TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[0]" VALUE="1" CLASS="P1"'.$data['DHavail'][0][1].$disable[0][1].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[0]" VALUE="2" CLASS="P2"'.$data['DHavail'][0][2].$disable[0][2].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[0]" VALUE="3" CLASS="P3"'.$data['DHavail'][0][3].$disable[0][3].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[0]" VALUE="0" CLASS="NO"'.$data['DHavail'][0][0].'></TD>
					</TR>
					<TR CLASS="row">
						<TD>MON</TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[1]" VALUE="1" CLASS="P1"'.$data['DHavail'][1][1].$disable[1][1].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[1]" VALUE="2" CLASS="P2"'.$data['DHavail'][1][2].$disable[1][2].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[1]" VALUE="3" CLASS="P3"'.$data['DHavail'][1][3].$disable[1][3].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[1]" VALUE="0" CLASS="NO"'.$data['DHavail'][1][0].'></TD>
					</TR>
					<TR CLASS="head"><TD COLSPAN="8"></TD></TR>
					<TR CLASS="rowalt">
						<TD ROWSPAN="3"><H5>B-'.$data['patnum'].'</H5></TD>
						<TD>TUES</TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[2]" VALUE="1" CLASS="P1"'.$data['DHavail'][2][1].$disable[2][1].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[2]" VALUE="2" CLASS="P2"'.$data['DHavail'][2][2].$disable[2][2].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[2]" VALUE="3" CLASS="P3"'.$data['DHavail'][2][3].$disable[2][3].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[2]" VALUE="0" CLASS="NO"'.$data['DHavail'][2][0].'></TD>
						<TD ROWSPAN="3"><SELECT NAME="BmaxDH" ID="BmaxDH">';
		for($i=0;$i<=3;$i++)
		{
			echo '<OPTION';
			if($data['BmaxDH']==$i)
				echo ' SELECTED';
			echo' >'.$i.'</OPTION>';
		}
		echo '			</SELECT></TD>
						<TD ROWSPAN="3"><SELECT NAME="BconsecDH" ID="BconsecDH">';
		for($i=0;$i<=3;$i++)
		{
			echo '<OPTION';
			if($data['BconsecDH']==$i)
				echo ' SELECTED';
			echo '>'.$i.'</OPTION>';
		}
		echo '			</SELECT></TD>
					</TR>
					<TR CLASS="rowalt">
						<TD>WED</TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[3]" VALUE="1" CLASS="P1"'.$data['DHavail'][3][1].$disable[3][1].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[3]" VALUE="2" CLASS="P2"'.$data['DHavail'][3][2].$disable[3][2].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[3]" VALUE="3" CLASS="P3"'.$data['DHavail'][3][3].$disable[3][3].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[3]" VALUE="0" CLASS="NO"'.$data['DHavail'][3][0].'></TD>
					</TR>
					<TR CLASS="rowalt">
						<TD>THUR</TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[4]" VALUE="1" CLASS="P1"'.$data['DHavail'][4][1].$disable[4][1].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[4]" VALUE="2" CLASS="P2"'.$data['DHavail'][4][2].$disable[4][2].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[4]" VALUE="3" CLASS="P3"'.$data['DHavail'][4][3].$disable[4][3].'></TD>
						<TD><INPUT TYPE="radio" NAME="DHavail[4]" VALUE="0" CLASS="NO"'.$data['DHavail'][4][0].'></TD>
					</TR>
					<TR CLASS="head"><TD COLSPAN="8"></TD></TR>
					<TR CLASS="totals">
						<TD COLSPAN="2">TOTAL</TD>
						<TD ID="P1-total">'.($c2s[1] ? $cts[1] : 0).'</TD>
						<TD ID="P2-total">'.($c3s[2] ? $cts[2] : 0).'</TD>
						<TD ID="P3-total">'.($cts[3] ? $cts[3] : 0).'</TD>
						<TD ID="NO-total">'.($cts[0] ? $cts[0] : 0).'</TD>
						<TD ID="Max-total">'.$totaldhs.'</TD>
						<TD></TD>
					</TR>
					<TR>
						<TD COLSPAN="4"></TD>
						<TD>
							<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
							<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
							<INPUT TYPE="submit" NAME="saveDHavail" VALUE="SAVE">
						</TD>
						<TD COLSPAN="3"></TD>
					</TR>
				</TABLE>
				</FORM>';
	}
}// close test for teamID 
elseif($level<9)
	echo '<DIV CLASS="errorbox"> Availability can no longer be edited.</DIV>';
	
$loadscriptsatend.='<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/effects.js"></SCRIPT>
<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/style.js"></SCRIPT>
<SCRIPT TYPE="text/javascript" SRC="ajax/teamavaildh.js"></SCRIPT>
<script src="'.$toroot.'javascripts/iphone-style-checkboxes.js" type="text/javascript"></script>
<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/datepicker.js"></SCRIPT>';
require($toroot.'template/foot.php');
?>
