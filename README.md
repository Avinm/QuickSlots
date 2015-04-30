QuickSlots: A web-based timetable management system 
----------


--------------------------------------------------------------------------------
    Features
--------------------------------------------------------------------------------

* Light-Weight: The whole application, including all images,scripts and
  stylesheets, is only 1.35 MB uncompressed and just 714KB when compressed.

* Fully automated installer: QuickSlots comes with a fully automated installer,
  meaning that the administrator just has to run the installer and does not
  have to look into ANY PART OF THE SOURCE CODE AT ALL.

* One-click total system backup and restore: Backup and restore settings and
  databases, even across different servers, just with a single click.

* Generate ready-to-print timetable image snapshots.

* Support for LDAP authentication.

--------------------------------------------------------------------------------
     Files and Descriptions
--------------------------------------------------------------------------------

*  .\setup.php
    -- Initial system setup.
*  .\index.php
    -- Displays the timetable applying the provided filters, if any.
*  .\login.php 
    -- Provides interface and back end routines that handle user logins. 
*  .\logout.php
    -- Logs out the user when visited
*  .\allocate.php
    -- Provides interface and back end routines for allocation of courses to
    time slots
*  .\dean.php
    -- Homepage of dean level users, it provides interface and back end
    routines to edit and save global settings. Access is restricted to dean
    level users.
*  .\manage.php
    -- Provides interface and back end routines to manage departments, faculty,
    batches and rooms. Access is restricted to dean level users.
*  .\faculty.php
    -- Homepage of HOD and faculty level users, provides interfaces to
       add/delete courses. Access requires at least faculty level account.
*  .\register.php
    -- Contains back end routines for user management. It is invoked by
       manage.php and setup.php
*  .\connect_db.php 
    -- Initiates database connections. Required by all files that needs to
    perform database operations
*  .\functions.php
    -- Contains various common helper routines used across most files
*  .\courses.php
    -- Contains back end routines to add/delete courses. It is invoked by
    faculty.php
*  .\batches.php
    -- Contains back end routines to add/delete batches. It is invoked by
    manage.php
*  .\depts.php
    -- Contains back end routines to add/delete batches. It is invoked by
    manage.php
*  .\rooms.php
    -- Contains back end routines to add/delete batches. It is invoked by
    dean.php
*  .\backup.php
    -- Contains back end routines to generate/restore backups, invoked by
    dean.php
* .\download.php
    -- Back end routines that call PhantomJS to generate printable timetable
    snapshot images. 
*  .\js\grid.js
    -- Client side script to render timetable grids
*  .\js\form.js
    -- Handles all form submissions and validation that needs to be done via
    AJAX
*  .\js\capture.js
    -- Contains PhantomJS routines for printable table snapshot generation
*  .\css\styles.css
    -- Defines generic styles shared by the whole QuickSlots UI
*  .\css\table.css
    -- Defines generic styles for QuickSlots table grids
*  .\css\dashboard.css
    -- Defines the stylings for various elements of the dashboard

--------------------------------------------------------------------------------
                    External Libraries and Programs Used
--------------------------------------------------------------------------------

* PhantomJS: PhantomJS web stack (for generating screenshots)
  (https://phantomjs.org/)

* jQuery Core: The jQuery Core javascript library (https://jquery.com/)

* jQuery UI: The jQuery UI library (https://jqueryui.com/)

* jQuery UI Touch Punch: Touch Event Support for jQuery UI
  (http://touchpunch.furf.com/)

* Chosen.js: Select Box Enhancer for jQuery (http://harvesthq.github.io/chosen/)

--------------------------------------------------------------------------------
                           System Requirements
--------------------------------------------------------------------------------

* Apache Web Server: Version 2.4 or higher recommended

* MYSQL Database Engine: Version 5.5 or higher recommended

* PHP Hypertext Preprocessor: Version 5.5 or higher recommended

--------------------------------------------------------------------------------
                         Installation Instructions
--------------------------------------------------------------------------------

* Make sure that the apache user has read/write permissions on the file
  "config.php", and the directory "tmp", used respectively for configuration and
  backup & screen-shot generation.

* Visit setup.php through a web browser and provide the required database and
  LDAP configurations. Thereafter, set up the first dean/admin account
  to complete the setup.

--------------------------------------------------------------------------------
                            Usage Instructions
--------------------------------------------------------------------------------

* Login using the "dean"(admin) account. You will be redirected to the
  "Manage Timetables" page.

* Create a timetable by clicking on the drop-down on top, beside "Configure
  Timetable:"

* Add departments, faculty, courses and rooms.

* HOD of every department can create courses and allocate timetables for all
  faculty of his/her department.

* Faculty is the account with the least set of privileges: creating courses and
  managing, the timetable for his courses. These privileges are shared by the
  HOD and dean as well.

--------------------------------------------------------------------------------
                           Viewing the timetable
--------------------------------------------------------------------------------

The timetable can be viewed by two groups of users:

* Public/guest users (like students):
 - Without needing any login, guest users can either visit the application
   web-root to view the timetable by applying filters on their own, or by
   visiting the links generated by faculty.
 - When they visit the web-root, only the current active timetable is displayed
   by default. As before, users can further apply the various filters such to
   find what they need.
 - The system also displays a link based on these filters which can be
   saved/bookmarked by them for direct display of the filtered timetable.

* Authorized users (Faculty/HOD/Dean):
  - Once authenticated faculty can view, generate links and download snapshots
    for all instances of the timetable, with any set of filters applied.

--------------------------------------------------------------------------------
                        Manage Timetable Instructions
--------------------------------------------------------------------------------

* Multiple instances of timetable can be created. While creating a new
  timetable, number of slots, working days and starting time can be configured.

* In a authenticated users can easily manage timetable with the help of the
  intuitive GUI and instructions that accompany.

* Timetables can be frozen by a dean at any time when a finalized timetable is
  needed. It can be used if you want to avoid any accidental changes on the 
  timetable once finalized. A frozen timetable cannot be edited unless a dean
  choses to de-freeze it.

--------------------------------------------------------------------------------
                               Backup/Restore
--------------------------------------------------------------------------------

* The system allows the dean to download a whole backup of the database.

* System can be restored to the previous state by uploading the backup file
  back.