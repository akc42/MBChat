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

INSERT INTO capabilities (cid,description) VALUES (1,'Leader');
INSERT INTO capabilities (cid,description) VALUES (2,'Director');
INSERT INTO capabilities (cid,description) VALUES (3,'Moderator');
INSERT INTO capabilities (cid,description) VALUES (4,'Guest');
INSERT INTO capabilities (cid,description) VALUES (5,'Speaker');
INSERT INTO capabilities (cid,description) VALUES (10,'Child');
INSERT INTO capabilities (cid,description) VALUES (20,'Administrator');
INSERT INTO capabilities (cid,description) VALUES (30,'Marketing');
INSERT INTO capabilities (cid,description) VALUES (32,'Engineering');
INSERT INTO capabilities (cid,description) VALUES (34,'IT');
INSERT INTO capabilities (cid,description) VALUES (36,'Finance');

COMMIT;
VACUUM;
    
