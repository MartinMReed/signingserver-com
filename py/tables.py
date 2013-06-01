#!/usr/bin/python
# -*- coding: utf-8 -*-

import os
import sqlite3
import settings

statement = 'CREATE TABLE %s (\
    id INT PRIMARY KEY,\
    date TIMESTAMP default CURRENT_TIMESTAMP NOT NULL,\
    signature VARCHAR NOT NULL,\
    count INT NOT NULL,\
    size BIGINT UNSIGNED NOT NULL,\
    successes INT NOT NULL,\
    failures INT NOT NULL,\
    duration INT NOT NULL,\
    retries INT NOT NULL\
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
#        cursor.execute("insert into stats values (1, CURRENT_TIMESTAMP, 'RCR', 10, 5000, 10, 0, 4000, 0)")
#        connection.commit()
        return True
    except:
        return False
    finally:
        if connection:
            connection.close()

if __name__ == '__main__':
    directory = os.path.abspath(os.path.dirname(__file__))
    create_table(directory)
