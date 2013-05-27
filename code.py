#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys, os
working_directory = os.path.abspath(os.path.dirname(__file__))
sys.path.insert(0, working_directory)

import web
web.config.debug = True

import tables as _tables

urls = (
    '/tables', 'tables'
    )

app = web.application(urls, globals())
application = app.wsgifunc()

class tables:
    def GET(self):
        result = _tables.create_table(working_directory)
        return result

def notfound():
    status = '404 Not Found'
    headers = {
        'Content-Type': 'text/html',
        'Connection': 'close'
    }
    return web.HTTPError(status, headers, "")

app.notfound = notfound
