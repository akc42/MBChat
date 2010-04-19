--
-- 	Copyright (c) 2009,2010 Alan Chandler
--    This file is part of MBChat.
--
--    MBChat is free software: you can redistribute it and/or modify
--    it under the terms of the GNU General Public License as published by
--    the Free Software Foundation, either version 3 of the License, or
--    (at your option) any later version.
--
--    MBChat is distributed in the hope that it will be useful,
--    but WITHOUT ANY WARRANTY; without even the implied warranty of
--    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--    GNU General Public License for more details.
--
--    You should have received a copy of the GNU General Public License
--    along with MBChat (file COPYING.txt).  If not, see <http://www.gnu.org/licenses/>.
--




CREATE TABLE users (
  uid integer primary key NOT NULL,
  time bigint DEFAULT (strftime('%s','now')) NOT NULL,
  name character varying NOT NULL,
  role char(1) NOT NULL default 'R',
  rid integer NOT NULL default 0,
  moderator char(1) NOT NULL default 'N',
  question character varying,
  private integer NOT NULL default 0,
  permanent text,                -- will be an md5 of the password
  groups text, -- a colon separated list of "smf_groups" for the committee rooms they are allowed to see
  present boolean NOT NULL DEFAULT 0
);
    
CREATE table wid_sequence ( value integer);
INSERT INTO wid_sequence (value) VALUES (1);

CREATE TABLE participant (
  uid integer NOT NULL REFERENCES users (uid) ON DELETE CASCADE ON UPDATE CASCADE,
  wid integer NOT NULL,
  primary key (uid,wid)
);


-- expect to do insert into participant values ('Some uid Value', (select value from wid_sequence));
-- sequence is updated manually in the code (only needs to be increased when a completely new whisper box is generated - not 
-- when a new participant is

CREATE TABLE log (
  lid integer primary key,
  time bigint DEFAULT (strftime('%s','now')) NOT NULL,
  uid integer NOT NULL,
  name character varying NOT NULL,
  role char(1) NOT NULL,
  rid integer NOT NULL,
  type char(2) NOT NULL,
  text character varying
);

PRAGMA foreign_keys = true;
VACUUM;



