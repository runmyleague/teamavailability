<?php

echo '<DIV ID="checklist">
		<DIV CLASS="title">Step-by-Step: Team Availability Set-Up</DIV>
		<CENTER><I>Teams must complete <B>ALL</B> steps. Upon completion of any step, it will change colors (red to green). Some steps are dependent on previous steps and will not become available until those steps are completed.</I></CENTER>
		<OL>';
		//check if schedule has been confirmed for the season
		//if not, show confirm button
		$sql='SELECT confirm FROM SCHEDteamseas WHERE teamID='.$teamID;
		$confirmed=sqlgetone($db,$sql);
		//check if write access to schedules is on
		include_once($toroot.'inc/getwriteflag.php');
		$accesson2=$accesson2 ? $accesson2 : getwriteflag('ta');
		if($accesson2)
		{
			$sql='SELECT maxage FROM agegroups, teams WHERE agegroups.age=teams.age AND teams.teamID='.$teamID;
			$maxage=sqlgetone($db,$sql);
			$youngaccess=getwriteflag('tay');
			if( $maxage<=12 && !$youngaccess )
				$accesson2=false;
		}
		
		//check if Opening Weekend is on
		//$owon=$owon ? $owon : getwriteflag('owuse');
		//check if initialized calendar
		$rs=sqlexecute($db,'SELECT startdt,maxGames FROM SCHEDteamseas WHERE teamID='.$teamID);
		$row=$rs->fetchRow();
		$set=$row['startdt'];
		$maxavail=$row['maxGames'];
		$rs->free();

		if($set)
		{
			echo '<LI CLASS="done"><B>';
			if($accesson2 || $level>=9)
				echo '<form action="tainstructions.php?teamID='.$teamID.'" method="post">
						<U>Enter/Modify Global Inputs</U> &nbsp;<A HREF="tainstructions.php?teamID='.$teamID.'">Click Here to Edit</A> &nbsp;
							<input type="hidden" name="teamID" value="'.$teamID.'">
							<input type="submit" name="clearschedule" value="  Start Over" CLASS="zapbutton"> Max Games = <SPAN ID="maxavail">'.$maxavail.'</SPAN>
						</FORM>
					</B></LI>';
			else
				echo '<U>Enter/Modify Global Inputs</U> &nbsp;<A HREF="tainstructions.php?teamID='.$teamID.'">Click Here to Edit</A> &nbsp;Max Games = <SPAN ID="maxavail">'.$maxavail.'</SPAN>
					</B></LI>';
		}
		else
		{
			echo '<LI CLASS="todo"><B><U>Enter/Modify Global Inputs</U> (below)</B></LI>';
			
		}
	
		//mark individual days for play limitations
		$rdays=sqlgetone($db,'SELECT COUNT(rday) FROM SCHEDrestricdays WHERE teamID='.$teamID);

		if($rdays > 0 || $confirmed)
			echo '<LI CLASS="done">';
		else
			echo '<LI CLASS="todo">';
		echo '<B><U>Set Days with Travel Restrictions</U>';
		if($set)
			echo ' &nbsp;<A HREF="tarestricdays.php?teamID='.$teamID.'">Click Here to Edit</A>';
		echo '</B></LI>';
		
		/*if($owon)
		{
			$owdone=sqlgetone($db, 'SELECT confirm FROM teamopeningdh WHERE teamID='.$teamID);
			if($owdone)
				$owclass='done';
			else
				$owclass='todo';
			if(!$accesson2 && $level<5)
				echo '<LI CLASS="'.$owclass.'"><B><U>Set '.$leaguesettings['owname'].' Preferences</U> - 
						Sorry, you cannot edit at this time. Contact your league administrator.</B></LI>';
			else
				echo'<LI CLASS="'.$owclass.'"><B>
							<U>Set '.$leaguesettings['owname'].' Preferences</U>
							<A HREF="owprefs.php?edit=1&teamID='.$teamID.'">Click Here</A> to set your '.$leaguesettings['owname'].' Days, Time and DHs
						</FORM>
					</B></LI>';
			if($owpage)
			{
				$sql='SELECT ID, confirm, maxDH, rule, dhrule, maxGames FROM teamopeningdh WHERE teamID='.$teamID;
				$rs=sqlexecute($db,$sql);
				$row=$rs->fetchRow();
				$steps['1']=($row['ID']) ? ' CLASS="done"' : ' CLASS="todo"';
				$steps['2']=($row['rule']) ? ' CLASS="done"' : ' CLASS="todo"';
				$steps['3']=($row['confirm']) ? ' CLASS="done"' : ' CLASS="todo"';
				echo '<OL TYPE="a">
						<LI'.$steps['1'].'><B>Specify Daily Availability</B> <A HREF="owprefs.php?edit=1&teamID='.$teamID.'">[edit]</A></LI>
						<LI'.$steps['2'].'><B>Set Other Scheduling Preferences</B> <A HREF="owprefs2.php?teamID='.$teamID.'">[edit]</A></LI>
						<LI'.$steps['3'].'><B>Final Summary - Confirm/Modify</B>';
				if($row['rule'])
					echo ' <A HREF="owprefs.php?teamID='.$teamID.'">[edit]</A>';
				echo '</LI>
					</OL>';
			}
		}*/
		
		$pattern=sqlgetone($db, 'SELECT patternID FROM SCHEDpatterns WHERE teamID='.$teamID);
		$_list_class=$pattern ? ' CLASS="done"' : ' CLASS="todo"';
		echo '<LI'.$_list_class.'><B><U>Construct/Modify Typical Weekly Pattern (default pattern assigned to all weeks as starting point)</U>';
		if($set && !$pattern && ($accesson2 || $level>=9) )
			echo '&nbsp;<A HREF="teamavail.php?teamID='.$teamID.'">Click Here</A>';
		echo '</B></LI>';
				
		if($confirmed)
			echo '<LI CLASS="done">';
		else
			echo '<LI CLASS="todo">';
		echo '<B><U>Change Pattern For Any Given Week</U> &nbsp;';
		if($set && ($accesson2 || $level>=9) )
		{
			echo '<A HREF="teamavail.php?teamID='.$teamID.'">Click Here to Build a New Pattern or Edit Existing Pattern</A> ';
			if($_SERVER['PHP_SELF']=='/members/team/teamavailcal.php')
				echo 'OR Change the pattern assigned to any week by clicking on the edit icon (<IMG SRC="'.$toroot.'images/pencil.png">) on the calendar below.';
			else
				echo 'OR <A HREF="teamavailcal.php?teamID='.$teamID.'">Go To Calendar</A> to assign patterns to weeks.';
			echo '</B></LI>';
		}
		else
			echo '</B></LI>';
		
		if($confirmed)
			echo '<LI CLASS="done"><B><U>E-Verify Availability Calendar</U> - Congratulations, You\'re Done!</B></LI>';
		else
		{
			$noconfirmyet=($set && $pattern && ($owdone || !$owon)) ? '' : ' DISABLED';
			echo '<LI CLASS="todo"><B>
				<form action="teamavailcal.php?teamID='.$teamID.'" method="post">
					<U>E-Verify Availability Calendar</U>
						After reviewing and verifying the accuracy of your Team Availabilty Schedule, please				
					<INPUT TYPE="hidden" NAME="confirmtype" VALUE="ros">
					<input type="hidden" name="teamID" value="'.$teamID.'">
					<input type="submit" name="confirm" value="E-Verify by Clicking Here"'.$noconfirmyet.'>
					</form>
				</B></LI>';
		}
		echo '</OL>
		</DIV>';
?>