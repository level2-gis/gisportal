Here are PostgreSQL scripts for gisportal2:

1. 019_update.sql

To upgrade latest gisapp database version 19 to 20. This is big upgrade which includes project_groups, ion_auth
integration and much more. When you upgrade to this version you have to use gisportal2 together with
latest gisapp - https://github.com/uprel/gisapp

2. 020_update.sql

Minor update script from v20 to v21. No structure change, add custom1 and custom2 fields from project group to function
get_project_data().

3. setup_v21.sql

If you are installing from scratch, you can use this script to run on blank database and use it in gisportal2 with
latest gisapp - https://github.com/uprel/gisapp
