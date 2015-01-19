# teamavailability
Team availability input for scheduler app

tables.sql includes all tables specific to the scheduling application, as well as two other tables that are required in the existing code before we start the redesign

teamavailcal.html and the associated files in teamavailcal_files is an example pure html version of the existing team availability calendar page. There are so many dependencies that I did not have time to pull apart and piece back together. So I included this instead. Which I hope will provide a basis for a starting point in combination with the *.php files included.

PHP files - should be all existing php files used for team availability, in other words all the functionality you are recreating should exist in these pages already in their old form
- these will not work as is because of the many includes and depencies that query other database tables
- one possible way to proceed is to create include files for the header and footer from the teamavailcal.html file and replace existing php code with that temporarily so that you can just focus on the central content of the page
