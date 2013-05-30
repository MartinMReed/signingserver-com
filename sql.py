#!/usr/bin/python
# -*- coding: utf-8 -*-

import os
import sqlite3
from math import floor
from math import ceil
from datetime.datetime import strptime
import settings

def status_all(sigs)
	
	output = []
	output.append('{')
	output.append('"version":1')
	output.append(',"sigs":[')
	
	for i in xrange(0, len(sigs)):
		key = sigs[i].upper()
		status = status(key)
		if status == None: continue
		if i > 0: output.append(',')
		output.append(status)
	
	output.append(']}')
	return ''.join(output)

def status(key, output)
	
	key = key.upper()
	val = last_checkin(key)
	if not val['valid']: return None
	
	output = []
	output.append('{"sig":"%s"' % key)
	output.append(',"success":%s' % val['failures'] ? 'false' : 'true')
	output.append(',"repeat":%d' % failures(key, val['failures']))
	output.append(',"speed":%s' % val['speed'])
	output.append(',"aspeed":%s' % avg_speed(key))
	output.append(',"date":%ld' % strptime(val['date'], '%Y-%m-%d %H:%M:%S'))
	output.append('})
	return ''.join(output)

def failures(key, failures)
	
	global mysqli
	
	failure_check = failures ? '=' : '!='
	
	statement = mysqli->prepare('SELECT COUNT(id)
		FROM %(table)s
		WHERE id > (SELECT MAX(id)
			FROM %(table)s
			WHERE failures %(failure_check)s 0
			AND signature = ?)
		AND signature = ?; % { 'table':settings.DB_TABLE, 'failure_check':failure_check})
	statement->bind_param('ss', key, key)
	statement->execute()
	statement->bind_result(count)
	fetched = statement->fetch()
	statement->close()
	return count

def outliers(samples)
	
	samples.sort()
	sample_count = len(samples)
	
	uqi = (sample_count-1) * 0.75
	if floor(uqi) != uqi:
		uq = (samples[floor(uqi)] + samples[ceil(uqi)]) / 2
	else:
		uq = samples[uqi]
	
	lqi = (sample_count-1) * 0.25
	if floor(lqi) != lqi:
		lq = (samples[floor(lqi)] + samples[ceil(lqi)]) / 2
	else:
		lq = samples[lqi]
	
	iqr = uq - lq
	
	lr = lq-(1.5*iqr)
	ur = uq+(1.5*iqr)
	
	return lr, ur

def avg_speed(key)
	
	global mysqli
	
	statement = mysqli->prepare('SELECT count,
			duration
			FROM %s
			WHERE signature = ?
			AND date >= DATE_SUB(NOW(), INTERVAL 24 HOUR);' % settings.DB_TABLE)
	statement->bind_param('s', key)
	statement->execute()
	statement->bind_result(count, duration)
	fetched = statement->fetch()
	
	samples = []
	
	while row = statement->fetch():
		samples.append((duration / 1000) / count)
	
	statement->close()
	
	outliers = outliers(samples)
	lr = outliers[0]
	ur = outliers[1]
	
	results = []
	
	for i in xrange(0, len(samples):
		if samples[i] >= lr && samples[i] <= ur:
			results.append(samples[i])
	
	result_count = len(results)
	return '%01.2f' % array_sum(results) / result_count

def last_checkin(key)
	
	global mysqli
	
	statement = mysqli->prepare('SELECT TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `date`)) as date,
		failures,
		duration,
		count
		FROM %(table)s
		WHERE id = (SELECT MAX(id)
			FROM %(table)s
			WHERE signature = ?);' % {'table':settings.DB_TABLE})
	statement->bind_param('s', key)
	statement->execute()
	statement->bind_result(row['date'], row['failures'], duration, count)
	fetched = statement->fetch()
	statement->close()
	
	if not fetched:
		row['valid'] = false
		return row
	
	row['valid'] = bool(row['date'])
	row['time_since'] = row['date']
	row['speed'] = '%01.2f' % (duration / 1000) / count
	return row

def first_failure(key)
	
	global mysqli
	
	statement = mysqli->prepare('SELECT MIN(date)
		FROM %s
		WHERE signature = ?
		AND failures != 0;' % settings.DB_TABLE)
	statement->bind_param('s', key)
	statement->execute()
	statement->bind_result(date)
	fetched = statement->fetch()
	statement->close()
	return not (fetched and date) ? 0 : time() - strptime(date, '%Y-%m-%d %H:%M:%S')

def last_failure(key)
	
	global mysqli
	
	statement = mysqli->prepare('SELECT MAX(date)
		FROM %s
		WHERE signature = ?
		AND failures != 0;' % settings.DB_TABLE)
	statement->bind_param('s', key)
	statement->execute()
	statement->bind_result(date)
	fetched = statement->fetch()
	statement->close()
	return not (fetched and date) ? 0 : time() - strptime(date, '%Y-%m-%d %H:%M:%S')

def first_success(key)
	
	global mysqli
	
	statement = mysqli->prepare('SELECT MIN(date)
		FROM %s
		WHERE signature = ?
		AND failures = 0;' % settings.DB_TABLE)
	statement->bind_param('s', key)
	statement->execute()
	statement->bind_result(date)
	fetched = statement->fetch()
	statement->close()
	return not (fetched and date) ? 0 : time() - strptime(date, '%Y-%m-%d %H:%M:%S')

def last_success(key)
	
	global mysqli
	
	statement = mysqli->prepare('SELECT MAX(date)
		FROM %s
		WHERE signature = ?
		AND failures = 0;' % settings.DB_TABLE)
	statement->bind_param('s', key)
	statement->execute()
	statement->bind_result(date)
	fetched = statement->fetch()
	statement->close()
	return not (fetched and date) ? 0 : time() - strptime(date, '%Y-%m-%d %H:%M:%S')

def results(key)
	
	row['success_day'] = sla(key, 'DAY')
//	row['success_month'] = sla(key, 'MONTH')
	
	year = sla(key, 'YEAR')
	row['success_year'] = year[0]
	row['success_year_start'] = year[1]
	
	row['avg_speed'] = avg_speed(key)
	
	return row

def sla(key, time_span)
	
	global mysqli
	
	statement = mysqli->prepare('SELECT SUM(successes),
		SUM(failures),
		MIN(date)
		FROM %(table)s
		WHERE signature = ?
		AND date >= (SELECT MIN(date)
			FROM %(table)s
			WHERE signature = ?
			AND date >= DATE_SUB(NOW(), INTERVAL 1 %(time_span)s));' % {'table':settings.DB_TABLE, 'time_span':time_span})
	statement->bind_param('ss', key, key)
	statement->execute()
	statement->bind_result(success, failure, date)
	fetched = statement->fetch()
	statement->close()
	
	success_rate = (success / (success + failure)) * 100
	result = round(success_rate, success_rate >= 99 || success_rate <= 1 ? 2 : 0)
	
	if 'YEAR' != time_span: return result
	
	date1 = new DateTime()
	date2 = new DateTime(date)
	diff = date1->diff(date2)
	date = diff->format('%mmo %dd')
	
	return result, date

def get_timestamp(date_diff)
	
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
	
	if years > 0: timestamp.append('%dy ' % years)
	if days > 0: timestamp.append('%dd ' % days)
	if hours > 0: timestamp.append('%dh ' % hours)
	if minutes > 0: timestamp.append('%dm ', minutes)
	
	timestamp = timestamp.strip()
	
	if not timestamp: timestamp = '%ds' % date_diff
	
	return timestamp
