create database mod_akrr CHARACTER SET utf8;
create database mod_appkernel CHARACTER SET utf8;
CREATE USER 'akrruser'@'localhost' IDENTIFIED BY 'akrruser';
GRANT ALL ON mod_akrr.* TO 'akrruser'@'localhost';
GRANT ALL ON mod_appkernel.* TO 'akrruser'@'localhost';
GRANT SELECT ON modw.* TO 'akrruser'@'localhost';
GRANT ALL ON mod_akrr.* TO 'xdmod'@'localhost';
GRANT ALL ON mod_appkernel.* TO 'xdmod'@'localhost';
FLUSH PRIVILEGES;
