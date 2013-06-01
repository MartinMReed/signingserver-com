#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys, os
working_directory = os.path.abspath(os.path.dirname(__file__))
sys.path.insert(0, working_directory)

import web
web.config.debug = True

import tables as _tables
import sql as _sql
import settings

urls = (
    '/tables', 'tables',
    '/status', 'status',
    '/sql', 'sql'
    )

app = web.application(urls, globals())
application = app.wsgifunc()

class tables:
    def GET(self):
        return  _tables.create_table(working_directory)

class sql:
    def GET(self):
        __sql = _sql.sql(working_directory)
        sigs = ('rcr','rbb')
        return __sql.status_all(sigs)

class status:
    def GET(self):
        __sql = _sql.sql(working_directory)
        sigs = ('rcr','rbb')
        return __sql.status_all(sigs)

def notfound():
    status = '404 Not Found'
    headers = {
        'Content-Type': 'text/html',
        'Connection': 'close'
    }
    return web.HTTPError(status, headers, "")

app.notfound = notfound
