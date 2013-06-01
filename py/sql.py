#!/usr/bin/python
# -*- coding: utf-8 -*-

import os
import sqlite3
from math import floor
from math import ceil
from datetime import datetime
import time
import settings

class sql(object):
    
    connection = None
    cursor = None
    
    def __init__(self, directory):
        database_file = os.path.join(directory, settings.DB_FILENAME)
        self.connection = sqlite3.connect(database_file)
        self.connection.row_factory = sqlite3.Row
        self.cursor = self.connection.cursor()
    
    def __del__(self):
        if self.connection:
            self.connection.close()
    
    def status_all(self, sigs):
        
        output = []
        output.append('{')
        output.append('"version":1')
        output.append(',"sigs":[')
        
        for i in xrange(0, len(sigs)):
            signature = sigs[i].upper()
            status = self.status(signature)
            if status == None: continue
            if i > 0: output.append(',')
            output.append(status)
        
        output.append(']}')
        return ''.join(output)
    
    def millis(self, timestamp):
        t = datetime.strptime(timestamp, '%Y-%m-%d %H:%M:%S')
        return long((time.mktime(t.timetuple()) * 1000) + (t.microsecond / 1000))
    
    def status(self, signature):
        
        signature = signature.upper()
        val = self.last_checkin(signature)
        if not val['valid']: return None
        
        output = []
        output.append('{')
        output.append('"sig":"{0}"'.format(signature))
        output.append(',"success":{0}'.format('false' if val['failures'] else 'true'))
        output.append(',"repeat":{0}'.format(self.failures(signature, val['failures'])))
        output.append(',"speed":{0}'.format(val['speed']))
        output.append(',"aspeed":{0}'.format(self.avg_speed(signature)))
        output.append(',"date":{0}'.format(self.millis(val['date'])))
        output.append('}')
        return ''.join(output)
    
    def failures(self, signature, failures):
        
        failure_check = '=' if failures else '!='
        
        self.cursor.execute("SELECT\
            COUNT(id) as count\
            FROM {table}\
            WHERE id > (SELECT MAX(id)\
                FROM {table}\
                WHERE failures {failure_check} 0\
                AND signature = :signature)\
            AND signature = :signature;".format(
            **{'table':settings.DB_TABLE, 'failure_check':failure_check}),
            {'signature':signature})
        
        row = self.cursor.fetchone()
        
        return row['count']
    
    def outliers(self, samples):
        
        samples.sort()
        sample_count = len(samples)
        
        uqi = (sample_count-1) * 0.75
        if floor(uqi) != uqi:
            uq = (samples[int(floor(uqi))] + samples[int(ceil(uqi))]) / 2
        else:
            uq = samples[int(uqi)]
        
        lqi = (sample_count-1) * 0.25
        if floor(lqi) != lqi:
            lq = (samples[int(floor(lqi))] + samples[int(ceil(lqi))]) / 2
        else:
            lq = samples[int(lqi)]
        
        iqr = uq - lq
        
        lr = lq-(1.5*iqr)
        ur = uq+(1.5*iqr)
        
        return lr, ur
    
    def avg_speed(self, signature):
        
        self.cursor.execute("SELECT\
            count,\
            duration\
            from {table}\
            where signature = :signature\
            and date >= date('now', '-24 hour');".format(
            **{'table':settings.DB_TABLE}),
            {'signature':signature})
        
        samples = []
        
        for row in self.cursor.fetchall():
            samples.append((row['duration'] / 1000) / row['count'])
        
        outliers = self.outliers(samples)
        lr = outliers[0]
        ur = outliers[1]
        
        results = []
        
        for i in xrange(0, len(samples)):
            if samples[i] >= lr and samples[i] <= ur:
                results.append(samples[i])
        
        result_count = len(results)
        return '%01.2f' % (sum(results) / result_count)
    
    def last_checkin(self, signature):
        
        self.cursor.execute("SELECT\
            (strftime('%s', 'now') - strftime('%s', date)) as time_since,\
            date,\
            failures,\
            duration,\
            count\
            FROM {table}\
            WHERE id = (SELECT MAX(id)\
                FROM {table}\
                WHERE signature = :signature);".format(
            **{'table':settings.DB_TABLE}),
            {'signature':signature})
        
        row = self.cursor.fetchone()
        
        result = {}
        
        if row is None:
            result['valid'] = False
            return result
        
        result['valid'] = True
        result['date'] = row['date']
        result['duration'] = row['duration']
        result['count'] = row['count']
        result['failures'] = row['failures']
        result['time_since'] = row['time_since']
        result['speed'] = '%01.2f' % ((row['duration'] / 1000) / row['count'])
        return result
    
    def first_failure(self, signature):
        
        self.cursor.execute("SELECT\
            MIN(date) as date\
            FROM {table}\
            WHERE signature = :signature\
            AND failures != 0;".format(
            **{'table':settings.DB_TABLE}),
            {'signature':signature})
        
        row = self.cursor.fetchone()
        
        if not (fetched and row['date']): return 0
        return datetime.now() - datetime.strptime(row['date'], '%Y-%m-%d %H:%M:%S')
    
    def last_failure(self, signature):
        
        self.cursor.execute("SELECT\
            MAX(date) as date\
            FROM {table}\
            WHERE signature = :signature\
            AND failures != 0;".format(
            **{'table':settings.DB_TABLE}),
            {'signature':signature})
        
        row = self.cursor.fetchone()
        
        if not (fetched and row['date']): return 0
        return datetime.now() - datetime.strptime(row['date'], '%Y-%m-%d %H:%M:%S')
    
    def first_success(self, signature):
        
        self.cursor.execute("SELECT\
            MIN(date) as date\
            FROM {table}\
            WHERE signature = :signature\
            AND failures = 0;".format(
            **{'table':settings.DB_TABLE}),
            {'signature':signature})
        
        row = self.cursor.fetchone()
        
        if not (fetched and row['date']): return 0
        return datetime.now() - datetime.strptime(row['date'], '%Y-%m-%d %H:%M:%S')
    
    def last_success(self, signature):
        
        self.cursor.execute("SELECT\
            MAX(date) as date\
            FROM {table}\
            WHERE signature = :signature\
            AND failures = 0;".format(
            **{'table':settings.DB_TABLE}),
            {'signature':signature})
        
        row = self.cursor.fetchone()
        
        if not (fetched and row['date']): return 0
        return datetime.now() - datetime.strptime(row['date'], '%Y-%m-%d %H:%M:%S')
    
    def results(self, signature):
        
        row['success_day'] = self.sla(signature, 'DAY')
        #row['success_month'] = self.sla(signature, 'MONTH')
        
        year = self.sla(signature, 'YEAR')
        row['success_year'] = year[0]
        row['success_year_start'] = year[1]
        
        row['avg_speed'] = self.avg_speed(signature)
        
        return row
    
    def sla(self, signature, time_span):
        
        self.cursor.execute("SELECT\
            SUM(successes) as successes,\
            SUM(failures) as failures,\
            MIN(date) as date\
            FROM {table}\
            WHERE signature = :signature\
            AND date >= (SELECT MIN(date)\
                FROM {table}\
                WHERE signature = :signature\
                AND date >= DATE_SUB(NOW(), INTERVAL 1 {time_span}));".format(
            **{'table':settings.DB_TABLE, 'time_span':time_span}),
            {'signature':signature})
        
        row = self.cursor.fetchone()
        
        success_rate = (row['successes'] / (row['successes'] + row['failures'])) * 100
        result = round(success_rate, 2 if success_rate >= 99 or success_rate <= 1 else 0)
        
        if 'YEAR' != time_span: return result
        
        diff = datetime.now() - row['date']
        date = time.strftime('%mmo %dd', diff)
        
        return result, date
    
    def get_timestamp(self, date_diff):
        
        MINUTE = 60
        HOUR = MINUTE * 60
        DAY = HOUR * 24
        YEAR = DAY * 365
        
        years = floor(date_diff / YEAR)
        date_diff -= years * YEAR
        
        days = floor(date_diff / DAY)
        date_diff -= days * DAY
        
        hours = floor(date_diff / HOUR)
        date_diff -= hours * HOUR
        
        minutes = floor(date_diff / MINUTE)
        date_diff -= minutes * MINUTE
        
        timestamp = ''
        
        if years > 0: timestamp.append('{0}y '.format(years))
        if days > 0: timestamp.append('{0}d '.format(days))
        if hours > 0: timestamp.append('{0}h '.format(hours))
        if minutes > 0: timestamp.append('{0}m '.format(minutes))
        
        timestamp = timestamp.strip()
        
        if not timestamp: return '{0}s'.format(date_diff)
        
        return timestamp
