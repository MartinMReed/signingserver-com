#!/usr/bin/python
# -*- coding: utf-8 -*-

import web
import os

urls = (
    '/tables', 'tables'
    )

app = web.application(urls, globals())
application = app.wsgifunc()

class tables:
    def GET(self):
#        os.system('tables.py')
        return 'd'

def notfound():
    status = '404 Not Found'
    headers = {
        'Content-Type': 'text/html',
        'Connection': 'close'
    }
    return web.HTTPError(status, headers, "")

app.notfound = notfound
