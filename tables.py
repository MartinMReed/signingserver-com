#!/usr/bin/python
# -*- coding: utf-8 -*-

import os
import sqlite3

DB_FILENAME = os.path.join('work', 'stats.db')
DB_TABLE = 'stats'

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
  );' % DB_TABLE

def create_table(directory):
    
    database_file = os.path.join(directory, DB_FILENAME)
    
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
    
    finally:
    
        if connection:
            connection.close()
    
    return False

#if __name__ == '__main__':
#    directory = os.path.abspath(os.path.dirname(__file__)
#    create_table()
