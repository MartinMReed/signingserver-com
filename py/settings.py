#!/usr/bin/python
# -*- coding: utf-8 -*-

import os

# accepted signatures
VALID_SIG = 'rrt,rbb,rcr,rcc,pbk'
VALID_CHART_SIG = '%s,all' % VALID_SIG
VALID_CHART_SIG_COMP = 'rrt,rbb,rcr,rcc,all'

# apikey for submit.php
SUBMISSION_KEY = ''

# database connection
DB_FILENAME = os.path.join('work', 'stats.db')
DB_TABLE = 'stats'
DB_HOST = ''
DB_USER = ''
DB_PASSWD = ''
DB_NAME = ''

# twitter connection
CONSUMER_KEY = ''
CONSUMER_SECRET = ''
OAUTH_TOKEN = ''
OAUTH_SECRET = ''

# number of failures before it goes to twitter
TWEETER_THRESHOLD = '3'
