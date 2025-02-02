Here are PostgreSQL scripts for gisportal2:

019_update.sql
- To upgrade latest gisapp database version 19 to 20. This is big upgrade which includes project_groups, ion_auth
  integration and much more. When you upgrade to this version you have to use gisportal2 together with
  latest gisapp - https://github.com/uprel/gisapp

020_update.sql
- Minor update script from v20 to v21. No structure change, add custom1 and custom2 fields from project group to function
  get_project_data().

021_update.sql
- Update script from v21 to v22. Adds more custom fields (custom3, custom4, link1, link2, link3) to project_groups table.
  This is optional and so not included in main setup file.

022_update.sql
- Update script from v22 to v23. Adds fields to projects, plugins and users_print tables. And modifies users_print_view with
  more fields.

023_update.sql
- Update script from v23 to v24. This update is for using dynamic mask filtering.

024_update.sql
- Update script from v24 to v25. This update add aditional user role (limited user, no export)

025_update.sql
- Update script from v25 to v26. This update improves dynamic mask filtering with own table masks

026_update.sql
- Update script from v26 to v27. New field on users table and treat power users also as administrators (access to all
  client projects) and then allow actions depending on tasks table

setup_v30.sql

If you are installing from scratch, you can use this script to run on blank database and use it in gisportal with
latest gisapp - https://github.com/uprel/gisapp
