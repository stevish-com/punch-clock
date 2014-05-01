Stevish Punch-Clock
===================

Fairly simple punch clock app to keep track of hours spent on different jobs

I am developing this for my personal use, but would like to make it usable for others who need a quick and simple web-based punch-clock option. A web-hosting account is required for setup.

License
=======
    Copyright (C) 2014 Stephen Narwold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


Current Features
================
1. Punch in/out of any number of jobs (only one can be punched in at a time)
  - Punch in/out right now or at a set time
2. Add new jobs freely
3. Set hourly rate per job for reports to show amount earned
4. Run reports by dates (each punch is shown separately)


Under Development
=================
1. Punches can be classified as Unpaid, Invoiced or Paid
  - This is available in the database and in reports
  - There is no UI for adding or changing this status yet
  - There is no UI for specifying only one classification to be reported on.
    - Right now, all three statuses are shown, and are displayed in separate tables
2. There is no UI to change hourly rate for a job
3. There is the ability to disable a job to make it not show up on the drop-down without losing all the information
  - There is no UI for turning this on or off


Wish List
=========
1. Password protection
  - At least basic single-password protection
  - Multiple user login would be nice too
    - This would require a users database, session control, a column in `transactions` for user id...
  - I am equipped to add either of these options as I have time and motivation.
2. A pretty user interface (not my strong suit... will never be pretty if I don't have help)
3. An API for a possible simple iOS/Android App (or any other type of app)
