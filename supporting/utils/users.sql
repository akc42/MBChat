BEGIN;

CREATE TABLE users (
  uid integer primary key autoincrement NOT NULL,
  time bigint DEFAULT (strftime('%s','now')) NOT NULL,
  name character varying NOT NULL,
  password character varying NOT NULL, --md5 hash of (name:realm:password) where realm is defined elsewhere
  capability character varying, -- a ":" separated list of capabilities (equivalent to smf_groups) which define that user
  isguest boolean DEFAULT 0 NOT NULL
);

CREATE TABLE capabilities (
    cid integer primary key NOT NULL,
    description character varying NOT NULL
);

INSERT INTO capabilities (cid,description) VALUES (1,'Admin'); -- can take logs of open rooms
INSERT INTO capabilities (cid,description) VALUES (2,'Mod'); -- can moderate in auditorium
INSERT INTO capabilities (cid,description) VALUES (3,'Speaker');  -- can speak in auditorium
INSERT INTO capabilities (cid,description) VALUES (4,'Secretary'); -- can take logs of meeting rooms which allowed in.

INSERT INTO capabilities (cid,description) VALUES (10,'CEO'); -- Brown
INSERT INTO capabilities (cid,description) VALUES (12,'Director'); -- Teal
INSERT INTO capabilities (cid,description) VALUES (14,'Dept Head'); -- Olive
INSERT INTO capabilities (cid,description) VALUES (16,'Sponsor'); -- Purple


INSERT INTO capabilities (cid,description) VALUES (20,'Board');
INSERT INTO capabilities (cid,description) VALUES (22,'Technology');
INSERT INTO capabilities (cid,description) VALUES (24,'Marketing');
INSERT INTO capabilities (cid,description) VALUES (26,'Engineering');
INSERT INTO capabilities (cid,description) VALUES (28,'IT');
INSERT INTO capabilities (cid,description) VALUES (30,'Finance');

COMMIT;
VACUUM;
    
