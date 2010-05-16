BEGIN;


INSERT INTO users (name,role,cap,rooms,password) VALUES ('alan','A',20,'5:9','alan');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('bill','B',0,'','bill');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('graham','G',0,'5:10','graham');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('henry','H',2,'5:9:10','henry');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('larry','L',16,'6','larry');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('roy','R',0,'6:8','roy');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('joe','R',1,'6:7:8','joe');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('peter','R',0,'','peter');

INSERT INTO users (name,role,cap,rooms,password) VALUES ('chris','B',0,'','chris');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('dave','B',0,'','dave');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('evan','B',0,'','evan');

INSERT INTO users (name,role,cap,rooms,password) VALUES ('fred','R',0,'','fred');

INSERT INTO users (name,role,cap,rooms,password) VALUES ('mark','R',8,'','mark');
INSERT INTO users (name,role,cap,rooms,password) VALUES ('steve','R',16,'','steve');


COMMIT;


