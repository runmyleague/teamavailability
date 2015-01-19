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

#lockout!
$level = getUserLevel();
/*$allowchanges = getwriteflag('ta');
if ($level < 9 && $allowchanges != 1) {
	print "Currently only Administrators can edit team availability.\n";
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

if ($teamID != 0) {
  $sql = "select teams.ownerID, teams.name, teams.teamID from teams where teamID=$teamID";
  $rs = sqlexecute($db,$sql);
	$row = $rs->fetchRow();
  $ownerID = $row['ownerID'];
  $teamID = $row['teamID'];
  $teamname = $row['name'];
  $rs->free();
}
$oneorother=$_REQUEST['oneorother'];
$patternID=$_POST['patternID'] ? $_POST['patternID'] : $_GET['patternID'];

$dowMap = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
if($_POST['saveooo']) //DH constraint inputs
{
	//save one or other prefs
	if($_POST['saveooo'] && $_POST['patternID'])
	{
		sqlexecute($db, 'DELETE FROM SCHEDoneorother WHERE pattID='.$_POST['patternID']);
		if($_POST['frisat'])
		{
			$fields=array('pattID', 'day1', 'day2');
			$values=array($_POST['patternID'], 5, 6);
			$sql=buildinsertinto('SCHEDoneorother', $fields, $values);
			sqlexecute($db,$sql);
		}
		if($_POST['satsun'])
		{
			$fields=array('pattID', 'day1', 'day2');
			$values=array($_POST['patternID'], 6, 0);
			$sql=buildinsertinto('SCHEDoneorother', $fields, $values);
			sqlexecute($db,$sql);
		}
		if($_POST['sunmon'])
		{
			$fields=array('pattID', 'day1', 'day2');
			$values=array($_POST['patternID'], 0, 1);
			$sql=buildinsertinto('SCHEDoneorother', $fields, $values);
			sqlexecute($db,$sql);
		}
		$host=$_SERVER['HTTP_HOST'];
		header("Location: http://$host/members/team/teamavaildh.php?teamID=$teamID&patternID=$patternID");
	}
}
elseif($_POST['saveavail'])
{
	//validate input
	include_once($toroot.'inc/class.validateforms.php');
	$valid= new FormValidator;
	$valid->isEmptyArrayAny('avail', $dowMap, 'ERROR: You must select your availability for ');
	if($valid->isError())
		$errors=$valid->getErrorList();
	$adaysavail=$bdaysavail=0;
	foreach($_POST['avail'] AS $_day=>$_avail)
	{
		if($_avail > 0)
		{
			if(in_array($_day, array(2,3,4)))
				$bdaysavail++;
			else
				$adaysavail++;
		}
	}
	if($_POST['Amaxgames'] > $adaysavail*2)
		$errors['adays']='<DIV CLASS="error">ERROR: Your max games in sub-pattern A is '.$_POST['Amaxgames'].' but you have only indicated that you can play '.$adaysavail.' of the days in that sub-pattern. You can only play two games per day.</DIV>';
	if($_POST['Bmaxgames'] > $bdaysavail*2)
		$errors['bdays']='<DIV CLASS="error">ERROR: Your max games in sub-pattern B is '.$_POST['Bmaxgames'].' but you have only indicated that you can play '.$bdaysavail.' of the days in that sub-pattern. You can only play two games per day.</DIV>';

	if(empty($errors) && $_POST['confirm'])
	{
		//$fields=('teamID','Amaxgames','Amaxconsec','Bmaxgames','Bmaxconsec');
		//$values=($_POST['teamID',$_POST['Amaxgames'],$_POST['Amaxconsec'],$_POST['Bmaxgames'],$_POST['Bmaxconsec']);
		include_once($toroot.'inc/class.processforms.php');
		$up=new processforms;
		if($_POST['patternID'] > 0)
		{
			$up->UpdateDB('SCHEDpatterns',$_POST['patternID'],'patternID');
			if(is_array($_POST['avail']))
			{
				foreach($_POST['avail'] AS $_day=>$_avail)
				{
					$fields=array('avail');
					$values=array($_avail);
					$sql=buildupdateset('SCHEDdayavail',$fields,$values);
					$sql.=' WHERE pattID='.$_POST['patternID'].' AND weekday='.$_day;
					sqlexecute($db,$sql);
				}
			}
			$oneorother=true;
		}
		else
		{
			unset($_POST['patternID']); //need to get rid of possible -1 here
			$sql='SELECT MAX(patnum) FROM SCHEDpatterns WHERE teamID='.$teamID;
			$lastpattern=sqlgetone($db,$sql);
			$_POST['patnum']=($lastpattern) ? $lastpattern+1 : 1;
			if($_POST['patnum']==1 && empty($_POST['name']))
				$_POST['name']='Default Pattern';
			$up->AddToDB('SCHEDpatterns');
			//get patternID and create dayavail records for each of week
			$patternID=sqlgetone($db,'SELECT LAST_INSERT_ID()');
			if(is_array($_POST['avail']))
			{
				foreach($_POST['avail'] AS $_day=>$_avail)
				{
					$fields=array('pattID', 'weekday', 'avail');
					$values=array($patternID, $_day, $_avail);
					if($_POST['copypattern'])
					{
						$sql='SELECT DHavail, start, end, night FROM SCHEDdayavail WHERE pattID='.$_POST['copypattern'].' AND weekday='.$_day;
						$rs=sqlexecute($db,$sql);
						$row=$rs->fetchRow();
						array_push($fields, 'DHavail', 'start', 'end', 'night');
						array_push($values, $row['DHavail'], "'".$row['start']."'", "'".$row['end']."'", "'".$row['night']."'");
						$rs->free();
					}
					$sql=buildinsertinto('SCHEDdayavail',$fields,$values);
					sqlexecute($db,$sql);
				}
			}
			//copy all other settings from other pattern
			if($_POST['copypattern'])
			{
				$sql='SELECT AmaxDH, AconsecDH, BmaxDH, BconsecDH FROM SCHEDpatterns WHERE patternID='.$_POST['copypattern'];
				$rs=sqlexecute($db,$sql);
				$row=$rs->fetchRow();
				$fields=array('AmaxDH', 'AconsecDH', 'BmaxDH', 'BconsecDH');
				$values=array($row['AmaxDH'], $row['AconsecDH'], $row['BmaxDH'], $row['BconsecDH']);
				$sql=buildupdateset('SCHEDpatterns', $fields, $values);
				$sql.=' WHERE patternID='.$patternID;
				sqlexecute($db,$sql);

				$sql='SELECT day1, day2 FROM SCHEDoneorother WHERE pattID='.$_POST['copypattern'];
				$rs=sqlexecute($db,$sql);
				while($row=$rs->fetchRow())
				{
					$fields=array('day1','day2','pattID');
					$values=array($row['day1'], $row['day2'], $patternID);
					$sql=buildinsertinto('SCHEDoneorother', $fields, $values);
					sqlexecute($db,$sql);
				}
				$rs->free();

				/*NO, THIS CREATES DUPLICATE ASSIGNMENTS FOR EACH WEEK!
				$sql='SELECT fri, thurs FROM SCHEDLINKweekpattern WHERE pattID='.$_POST['copypattern'];
				$rs=sqlexecute($db,$sql);
				while($row=$rs->fetchRow())
				{
					$fields=array('fri','thurs','pattID');
					$values=array("'".$row['fri']."'", "'".$row['thurs']."'", $patternID);
					$sql=buildinsertinto('SCHEDLINKweekpattern', $fields, $values);
					sqlexecute($db,$sql);
				}
				$rs->free();*/
			}
			$oneorother=true;
		}
	}
	else
		$needtoconfirm=true;
}

if (isset($_POST['clearschedule']))
  $modename = "Start Over";
elseif( isset($_POST['dhpattern']) )
	$modename = "Opening Weekend Availability";
else
  $modename = "Edit Schedule";


$testing=false;


?>
<!DOCTYPE HTML>
<HTML>
	<head>
		<title>My Team Availability <?php print "- $modename"; ?></title>
<?php
//put scripts here-> head.php include closes head and opens body

echo '<LINK HREF="'.$toroot.'javascripts/datestuff/datepicker.css" REL="stylesheet" TYPE="text/css">
<link rel="stylesheet" href="'.$toroot.'javascripts/iphone-checkbox.css" type="text/css" media="screen">';
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
if(($accesson || $level>=9) && $printscreen)
{
?>

<h1>My Team Availability <?php print "- $modename"; ?></H1>
<h2><?php print $teamname; ?></H2>
<?php
	if($testing)
	{
		echo '<PRE>';
		print_r($_POST);
		echo '</PRE>';
		echo "PATTERNID=$patternID";
	}	

	require('teamavailheadnew.php');
	if($errors)
	{
		foreach($errors AS $_err)
			echo '<BR>'.$_err;
	}
	//TEMP: ensures you load one or other options
	//$oneorother=true;
	//$patternID=2;

	//need to select a pattern to edit
	if(empty($patternID))
	{

		$sql='SELECT patternID,patnum,name FROM SCHEDpatterns WHERE teamID='.$teamID;
		$rs=sqlexecute($db,$sql);
		while($row=$rs->fetchRow())
		{
			$patterns[$row['patternID']]=array('patnum'=>$row['patnum'], 'name'=>$row['name']);
			$lp=$row['patternID'];
		}
		$rs->free();
		if(count($patterns) > 0)
		{
			echo 'Please select the pattern you would like to edit:<UL>';
			foreach($patterns AS $_pID=>$_pinfo)
			{
				echo '<LI>
						<A HREF="teamavpattern.php?teamID='.$teamID.'&patternID='.$_pID.'">Pattern '.$_pinfo['patnum'].' - '.$_pinfo['name'].'</A></LI>';
			}
			echo '<LI>
					<A HREF="teamavail.php?teamID='.$teamID.'&patternID=-1">
						Build New Pattern
					</A>
				</LI>
			</UL>';
		}
	
	}
	/*what the hell is this?
	elseif($patterns && $lp)
		$patternID=$lp;*/
	
	//if no patterns created yet, or pattern to edit selected then go on to settings
	if(empty($patterns) || $patternID || $_REQUEST['copypattern'])
	{
		if($oneorother)	//set one day or other preferences
		{
			if($_POST['submitooo']) //confirm one day or other selections
			{
				echo '<DIV CLASS="noticebox">
						<FORM ACTION="" METHOD="post" CLASS="colored">
							<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
							<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">';
				if($_POST['frisat'])
					echo '<INPUT TYPE="hidden" NAME="frisat" VALUE="1">You have indicated that you don\'t want to play on both Friday and Saturday.<BR>';
				if($_POST['satsun'])
					echo '<INPUT TYPE="hidden" NAME="satsun" VALUE="1">You have indicated that you don\'t want to play on both Saturday and Sunday.<BR>';
				if($_POST['sunmon'])
					echo '<INPUT TYPE="hidden" NAME="sunmon" VALUE="1">You have indicated that you don\'t want to play on both Sunday and Monday.<BR>';
				if(!$_POST['frisat'] && !$_POST['satsun'] && !$_POST['sunmon'])
					echo 'You have indicated no pairwise "one or the other" limitations in this pattern.<BR>';
				echo 'Is that correct?
						<INPUT TYPE="submit" NAME="saveooo" VALUE="YES">
					</FORM>
					<FORM METHOD="post" ACTION="">
						<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
						<INPUT TYPE="submit" NAME="oneorother" VALUE="NO">
						<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
					</FORM>
				</DIV>';

			}
			else  // one day or other input form
			{
				if(!isset($_POST['avail']))
				{
					$sql='SELECT weekday, avail FROM SCHEDdayavail WHERE pattID='.$patternID;
					$rs=sqlexecute($db,$sql);
					while($row=$rs->fetchRow())
					{
						$data['avail'][$row['weekday']]=$row['avail'];
					}
					$rs->free();
				}
				else
					$data['avail']=$_POST['avail'];
				//get existing settings
				$sql='SELECT day1,day2 FROM SCHEDoneorother WHERE pattID='.$patternID;
				$rs=sqlexecute($db,$sql);
				while($row=$rs->fetchRow())
				{
					if($row['day1']==5 && $row['day2']==6)
						$frisat=' CHECKED';
					if($row['day1']==6 && $row['day2']==0)
						$satsun=' CHECKED';
					if($row['day1']==0 && $row['day2']==1)
						$sunmon=' CHECKED';
				}
				$rs->free();
				echo '<FORM ACTION="" METHOD="post" CLASS="colored">
						<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
						<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
						<INPUT TYPE="hidden" NAME="oneorother" VALUE="1">
					<FIELDSET>
						<LEGEND>If One Day, Not The Other Constraints</LEGEND>
						
						<P>Pattern A Constraints On Playing On back-to-back to days: Select ON for as many as apply. NOTE: If you select all three, you could be limiting yourself to a MAX play of two days in that four day period.</P>';
				if($data['avail'][5] && $data['avail'][6])
				{
					echo '<LABEL FOR="frisat">Friday/Saturday:</LABEL><INPUT TYPE="checkbox" NAME="frisat" CLASS="switch" ID="frisat"'.$frisat.'><BR>';
					$oooptions=true;
				}
				if($data['avail'][6] && $data['avail'][0])
				{
					echo '<LABEL FOR="satsun">Saturday/Sunday:</LABEL><INPUT TYPE="checkbox" NAME="satsun" CLASS="switch" ID="satsun"'.$satsun.'><BR>';
					$oooptions=true;
				}
				if($data['avail'][0] && $data['avail'][1])
				{
					echo '<LABEL FOR="sunmon">Sunday/Monday:</LABEL><INPUT TYPE="checkbox" NAME="sunmon" CLASS="switch" ID="sunmon"'.$sunmon.'><BR>';
					$oooptions=true;
				}
				if(!$oooptions)
					echo '<DIV CLASS="error">You did not select any contiguous days in the A portion of this pattern, so you may continue to the next page by clicking the SAVE button.</DIV>';
				echo '</FIELDSET>
						<INPUT TYPE="submit" NAME="submitooo" VALUE="SAVE">
					</FORM>';
			}
		}
		elseif(empty($errors) && $needtoconfirm)  //confirm daily availability settings
		{
			if($_POST['saveavail'])
			{
				if($_POST['confirm1'])
				{
					if($_POST['confirm2'])
					{
						echo '<DIV CLASS="noticebox">You also have indicated that you do not want to play more than '.$_POST['Amaxconsec'].' consecutive days from FRI to MON of this week.<BR>You also have indicated that you do not want to play more than '.$_POST['Bmaxconsec'].' consecutive days from TUE to THU of this week.
							<FORM ACTION="" METHOD="post">Is this correct?';
						foreach($_POST AS $_a=>$_b)
						{
							if($_a=='avail')
							{
								foreach($_b AS $_day=>$_avail)
									echo '<INPUT TYPE="hidden" NAME="avail['.$_day.']" VALUE="'.$_avail.'">';
							}
							else
								echo '<INPUT TYPE="hidden" NAME="'.$_a.'" VALUE="'.$_b.'">';
						}
						echo '<INPUT TYPE="hidden" NAME="confirm1" VALUE="Yes">
							<INPUT TYPE="hidden" NAME="confirm2" VALUE="Yes">
							<INPUT TYPE="submit" NAME="confirm" VALUE="Yes">
							</FORM>
							<FORM ACTION="" METHOD="post">
								<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
								<INPUT TYPE="submit" NAME="editavail" VALUE="No">
							</FORM>
							</DIV>';

					}
					else
					{
						echo '<DIV CLASS="noticebox">You also have indicated that you would like to be scheduled for '.($_POST['Amaxgames']+$_POST['Bmaxgames']).' total games per week, '.$_POST['Amaxgames'].' in Sub-Pattern A-1 (FRI thru MON) and '.$_POST['Bmaxgames'].' in Sub-Pattern B-1 (TUES thru THUR).
							<FORM ACTION="" METHOD="post">Is this correct?';
						foreach($_POST AS $_a=>$_b)
						{
							if($_a=='avail')
							{
								foreach($_b AS $_day=>$_avail)
									echo '<INPUT TYPE="hidden" NAME="avail['.$_day.']" VALUE="'.$_avail.'">';
							}
							else
								echo '<INPUT TYPE="hidden" NAME="'.$_a.'" VALUE="'.$_b.'">';
						}
						echo '<INPUT TYPE="hidden" NAME="confirm1" VALUE="Yes">
							<INPUT TYPE="submit" NAME="confirm2" VALUE="Yes">
							</FORM>
							<FORM ACTION="" METHOD="post">
								<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
								<INPUT TYPE="submit" NAME="editavail" VALUE="No">
							</FORM>
							</DIV>';
					}
				}
				else
				{
					$p1=$p2=$p3=$no=array();
					foreach($_POST['avail'] AS $_day=>$_avail)
					{
						switch($_avail)
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
						echo 'You have indicated your primary preference is to be scheduled on the following days: '.implode(', ',$p1).'.<BR>';
					if($p2)
						echo 'You also indicated that if your primary preference can\'t be met, you are willing to play on the following additional days: '.implode(', ',$p2).'.<BR>';
					if($p3)
						echo 'You also indicated your team can play on on the following additional days IF and ONLY IF necessary: '.implode(', ',$p3).'.<BR>';
					if($no)
						echo 'You also indicated that your team can not play on the following day-type, no matter what: '.implode(', ',$no).'.<BR>';
					echo '<FORM ACTION="" METHOD="post">Are these statements correct?';
					foreach($_POST AS $_a=>$_b)
					{
						if($_a=='avail')
						{
							foreach($_b AS $_day=>$_avail)
								echo '<INPUT TYPE="hidden" NAME="avail['.$_day.']" VALUE="'.$_avail.'">';
						}
						else
							echo '<INPUT TYPE="hidden" NAME="'.$_a.'" VALUE="'.$_b.'">';
					}
					echo '<INPUT TYPE="submit" NAME="confirm1" VALUE="Yes"> <INPUT TYPE="submit" NAME="editavail" VALUE="No"></FORM>
						</DIV>';

				}

			}
		}
		else //set daily availability settings
		{
			$typect=array(1=>0,2=>0,3=>0,0=>0);
			foreach($_POST AS $_a=>$_b)
			{
				if($_a=='avail')
				{
					foreach($_b AS $_day=>$_avail)
					{
						$data['avail'][$_day][$_avail]=' CHECKED';
					}
				}
				else
					$data[$_a]=$_b;
			}
			//create a new week pattern
			echo '<P>Please label each daytype in this weekly pattern according to your ability/preferences for playing on that daytype. Each daytype needs a single selection from the four possible options. Inputs for the last two columns apply to the full period encompassed by each respective sub-pattern.</P>';
			echo '<P>Understand that during self-scheduling mode, teams will be allowed to schedule their home games on all days except those designated NOT POSSIBLE. However, away games are scheduled by your opponents and shall be restricted to those daytypes you have designated as P1. When not in self-scheduling mode i.e., ENYTB admin is scheduling, P1 will be given top preference, P2 will be given 2nd preference and OIN will be used only as a last resort, both for home and away games.</P>';
			if(empty($data))
			{
				if($_REQUEST['copypattern'])
				{
					$sql='SELECT SCHEDpatterns.patnum, SCHEDpatterns.name, SCHEDpatterns.Amaxgames, SCHEDpatterns.Amaxconsec, SCHEDpatterns.Bmaxgames, SCHEDpatterns.Bmaxconsec FROM SCHEDpatterns WHERE patternID='.$_REQUEST['copypattern'];
					$rs=sqlexecute($db,$sql);
					while($row=$rs->fetchRow())
					{
						$data=$row;
					}
					//don't copy name
					$data['name']='';
					$rs->free();
					$sql='SELECT weekday, avail FROM SCHEDdayavail WHERE pattID='.$_REQUEST['copypattern'];
					$rs=sqlexecute($db,$sql);
					while($row=$rs->fetchRow())
					{
						$data['avail'][$row['weekday']][$row['avail']]=' CHECKED';;
					}
					$rs->free();					
				}
				if($patternID==-1)
				{
					$sql='SELECT MAX(patnum) FROM SCHEDpatterns WHERE teamID='.$teamID;
					$lastpattern=sqlgetone($db,$sql);
					$data['patnum']=($lastpattern) ? $lastpattern+1 : 1;
				}
				elseif($patternID)
				{
					$sql='SELECT SCHEDpatterns.patnum, SCHEDpatterns.name, SCHEDpatterns.Amaxgames, SCHEDpatterns.Amaxconsec, SCHEDpatterns.Bmaxgames, SCHEDpatterns.Bmaxconsec FROM SCHEDpatterns WHERE patternID='.$patternID;
					$rs=sqlexecute($db,$sql);
					while($row=$rs->fetchRow())
					{
						$data=$row;
					}
					$rs->free();
					$sql='SELECT weekday, avail FROM SCHEDdayavail WHERE pattID='.$patternID;
					$rs=sqlexecute($db,$sql);
					while($row=$rs->fetchRow())
					{
						$data['avail'][$row['weekday']][$row['avail']]=' CHECKED';;
					}
					$rs->free();
				}
			}
			if($data['avail'])
			{
				foreach($data['avail'] AS $_day=>$_avail)
				{
					foreach($_avail AS $_val=>$_x)
						$typect[$_val]++;
				}
			}
			if($testing)
			{
				echo '<PRE>';
				print_r($data);
				echo '</PRE>';
			}
				echo '<FORM ACTION="teamavail.php" METHOD="post">
						<H4>Weekly Availability Pattern '.$data['patnum'].' - '.$data['name'].'</H4>Pattern name:<INPUT TYPE="text" NAME="name" VALUE="'.$data['name'].'"><BR>';
			echo '<TABLE CELLSPACING="0" CELLPADDING="3">
					<TR CLASS="head">
						<TD></TD>
						<TD></TD>
						<TD>Primary Preference (P1)</TD>
						<TD>Secondary Preference (P2)</TD>
						<TD>If & Only if Necessary (P3)</TD>
						<TD>NOT Possible (NO)</TD>
						<TD>MAX Games</TD>
						<TD>Will Play # Days in a Row</TD>
					</TR>
					<TR CLASS="head"><TD COLSPAN="8"></TD></TR>
					<TR CLASS="row">		
						<TD ROWSPAN="4"><H5>A-'.$data['patnum'].'</H5></TD>
						<TD>FRI</TD>
						<TD><INPUT TYPE="radio" NAME="avail[5]" VALUE="1" CLASS="P1"'.$data['avail'][5][1].'></TD>
						<TD><INPUT TYPE="radio" NAME="avail[5]" VALUE="2" CLASS="P2"'.$data['avail'][5][2].'></TD>
						<TD><INPUT TYPE="radio" NAME="avail[5]" VALUE="3" CLASS="P3"'.$data['avail'][5][3].'></TD>
						<TD><INPUT TYPE="radio" NAME="avail[5]" VALUE="0" CLASS="NO"'.$data['avail'][5][0].'></TD>
						<TD ROWSPAN="4"><SELECT NAME="Amaxgames" ID="Amaxgames">';
			for($i=0;$i<=8;$i++)
			{
				echo '<OPTION';
				if($data['Amaxgames']==$i)
					echo ' SELECTED';
				echo' >'.$i.'</OPTION>';
			}
			echo '			</SELECT></TD>
							<TD ROWSPAN="4"><SELECT NAME="Amaxconsec" ID="Amaxconsec">';
			for($i=1;$i<=4;$i++)
			{
				echo '<OPTION';
				if($data['Amaxconsec']==$i)
					echo ' SELECTED';
				echo '>'.$i.'</OPTION>';
			}
			echo '			</SELECT><DIV CLASS="notes">incl. Tues and Thurs</DIV></TD>
						</TR>
						<TR CLASS="row">
							<TD>SAT</TD>
							<TD><INPUT TYPE="radio" NAME="avail[6]" VALUE="1" CLASS="P1"'.$data['avail'][6][1].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[6]" VALUE="2" CLASS="P2"'.$data['avail'][6][2].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[6]" VALUE="3" CLASS="P3"'.$data['avail'][6][3].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[6]" VALUE="0" CLASS="NO"'.$data['avail'][6][0].'></TD>
						</TR>
						<TR CLASS="row">
							<TD>SUN</TD>
							<TD><INPUT TYPE="radio" NAME="avail[0]" VALUE="1" CLASS="P1"'.$data['avail'][0][1].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[0]" VALUE="2" CLASS="P2"'.$data['avail'][0][2].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[0]" VALUE="3" CLASS="P3"'.$data['avail'][0][3].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[0]" VALUE="0" CLASS="NO"'.$data['avail'][0][0].'></TD>
						</TR>
						<TR CLASS="row">
							<TD>MON</TD>
							<TD><INPUT TYPE="radio" NAME="avail[1]" VALUE="1" CLASS="P1"'.$data['avail'][1][1].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[1]" VALUE="2" CLASS="P2"'.$data['avail'][1][2].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[1]" VALUE="3" CLASS="P3"'.$data['avail'][1][3].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[1]" VALUE="0" CLASS="NO"'.$data['avail'][1][0].'></TD>
						</TR>
						<TR CLASS="head"><TD COLSPAN="8"></TD></TR>
						<TR CLASS="rowalt">
							<TD ROWSPAN="3"><H5>B-'.$data['patnum'].'</H5></TD>
							<TD>TUES</TD>
							<TD><INPUT TYPE="radio" NAME="avail[2]" VALUE="1" CLASS="P1"'.$data['avail'][2][1].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[2]" VALUE="2" CLASS="P2"'.$data['avail'][2][2].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[2]" VALUE="3" CLASS="P3"'.$data['avail'][2][3].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[2]" VALUE="0" CLASS="NO"'.$data['avail'][2][0].'></TD>
							<TD ROWSPAN="3"><SELECT NAME="Bmaxgames" ID="Bmaxgames">';
			for($i=0;$i<=6;$i++)
			{
				echo '<OPTION';
				if($data['Bmaxgames']==$i)
					echo ' SELECTED';
				echo' >'.$i.'</OPTION>';
			}
			echo '			</SELECT></TD>
							<TD ROWSPAN="3"><SELECT NAME="Bmaxconsec" ID="Bmaxconsec">';
			for($i=1;$i<=3;$i++)
			{
				echo '<OPTION';
				if($data['Bmaxconsec']==$i)
					echo ' SELECTED';
				echo '>'.$i.'</OPTION>';
			}
			echo '			</SELECT><DIV CLASS="notes">incl. Mon and Fri</DIV></TD>
						</TR>
						<TR CLASS="rowalt">
							<TD>WED</TD>
							<TD><INPUT TYPE="radio" NAME="avail[3]" VALUE="1" CLASS="P1"'.$data['avail'][3][1].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[3]" VALUE="2" CLASS="P2"'.$data['avail'][3][2].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[3]" VALUE="3" CLASS="P3"'.$data['avail'][3][3].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[3]" VALUE="0" CLASS="NO"'.$data['avail'][3][0].'></TD>
						</TR>
						<TR CLASS="rowalt">
							<TD>THUR</TD>
							<TD><INPUT TYPE="radio" NAME="avail[4]" VALUE="1" CLASS="P1"'.$data['avail'][4][1].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[4]" VALUE="2" CLASS="P2"'.$data['avail'][4][2].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[4]" VALUE="3" CLASS="P3"'.$data['avail'][4][3].'></TD>
							<TD><INPUT TYPE="radio" NAME="avail[4]" VALUE="0" CLASS="NO"'.$data['avail'][4][0].'></TD>
						</TR>
						<TR CLASS="head"><TD COLSPAN="8"></TD></TR>
						<TR CLASS="totals">
							<TD COLSPAN="2">TOTAL</TD>
							<TD ID="P1-total">'.$typect[1].'</TD>
							<TD ID="P2-total">'.$typect[2].'</TD>
							<TD ID="P3-total">'.$typect[3].'</TD>
							<TD ID="NO-total">'.$typect[0].'</TD>
							<TD ID="Max-total">'.($data['Amaxgames']+$data['Bmaxgames']).'</TD>
							<TD></TD>
						</TR>
						<TR>
							<TD COLSPAN="4"></TD>
							<TD>
								<INPUT TYPE="hidden" NAME="copypattern" VALUE="'.$_REQUEST['copypattern'].'">
								<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
								<INPUT TYPE="hidden" NAME="patternID" VALUE="'.$patternID.'">
								<INPUT TYPE="submit" NAME="saveavail" VALUE="SAVE">
							</TD>
							<TD COLSPAN="3"></TD>
						</TR>
					</TABLE>
					</FORM>';
		}
	}//close check for patternID
}// close test for teamID 
elseif($level<9)
	echo '<DIV CLASS="errorbox"> Availability can no longer be edited.</DIV>';
	
$loadscriptsatend.='<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/effects.js"></SCRIPT>
<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/style.js"></SCRIPT>
<SCRIPT TYPE="text/javascript" SRC="ajax/teamavail.js"></SCRIPT>
<script src="'.$toroot.'javascripts/iphone-style-checkboxes.js" type="text/javascript"></script>
<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/datepicker.js"></SCRIPT>';
require($toroot.'template/foot.php');
?>
