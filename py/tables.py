#!/usr/bin/python
# -*- coding: utf-8 -*-

import os
import sqlite3
import settings

statement = 'CREATE TABLE %s (\n\
  id INT PRIMARY KEY,\n\
  date TIMESTAMP default CURRENT_TIMESTAMP NOT NULL,\n\
  signature VARCHAR NOT NULL,\n\
  count INT NOT NULL,\n\
  size BIGINT UNSIGNED NOT NULL,\n\
  successes INT NOT NULL,\n\
  failures INT NOT NULL,\n\
  duration INT NOT NULL,\n\
  retries INT NOT NULL\n\
  );' % settings.DB_TABLE

def create_table(directory):
    
    database_file = os.path.join(directory, settings.DB_FILENAME)
    
    try:
        with open(database_file):
            return False
    except IOError: pass
    
    connection = None
    
    try:
        connection = sqlite3.connect(database_file)
        cursor = connection.cursor()
        cursor.execute(statement)
        return True
    except:
        return False
    finally:
        if connection:
            connection.close()

if __name__ == '__main__':
    directory = os.path.abspath(os.path.dirname(__file__))
    create_table(directory)
