#!/usr/bin/python
# -*- coding: utf-8 -*-

exit('disabled')

import sqlite3

database_file = 'stats.db'
DB_TABLE = 'stats'

try:
    with open(database_file): pass
    exit('file already exists')
except IOError:
    pass

create_table = 'CREATE TABLE %s (\n\
  id INT PRIMARY KEY,\n\
  date TIMESTAMP default CURRENT_TIMESTAMP NOT NULL,\n\
  signature VARCHAR NOT NULL,\n\
  count INT NOT NULL,\n\
  size BIGINT UNSIGNED NOT NULL,\n\
  successes INT NOT NULL,\n\
  failures INT NOT NULL,\n\
  duration INT NOT NULL,\n\
  retries INT NOT NULL\n\
  );' % DB_TABLE

connection = None

try:

    connection = sqlite3.connect(database_file)
    cursor = connection.cursor()
    cursor.execute(create_table)
    
finally:
    
    if connection:
        connection.close()