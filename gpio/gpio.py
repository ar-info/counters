#!/usr/bin/env python3
### BEGIN INIT INFO
# Provides:          gpio.py
# Required-Start:    $remote_fs $syslog
# Required-Stop:     $remote_fs $syslog
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Start daemon at boot time
# Description:       Enable service provided by daemon.
### END INIT INFO

import sys, time, threading, os
from daemon3x import daemon
import RPi.GPIO as GPIO
import smtplib, urllib.request, urllib.error
import dns.resolver
import logging
import logging.handlers
import socket
import http.client


PIN_COUNTER_HOT  = 13
PIN_COUNTER_COLD = 5

FILE_SAVE_SEND_TIMEOUT = 10*60
HTTP_REQUEST_TIMEOUT = FILE_SAVE_SEND_TIMEOUT / 20

SEND_EMAIL_TIMEOUT = 24 * 60 * 60 * 60


NUM_FILE_LIMIT_FOR_EMAIL = SEND_EMAIL_TIMEOUT / FILE_SAVE_SEND_TIMEOUT

temp_files_path = "/var/local/counters/"

LOG_LEVEL = logging.DEBUG
LOG_FILENAME = '/var/log/counters.log'
cnt_logger = logging.getLogger('counters')


class GlobalCounter:
	def __init__(self):
		self.m_Lock = threading.RLock()
		self.m_Counter = 0
		
	def InterlockedIncrement(self):
		try:
			self.m_Lock.acquire()
			self.m_Counter+=1
		finally:
			self.m_Lock.release()
		
		return self.m_Counter
	
	def InterlockedGetAndExchange(self, new_value):
		try:
			self.m_Lock.acquire()
			ret_value = self.m_Counter
			self.m_Counter = new_value
		finally:
			self.m_Lock.release()
			
		return ret_value
	
	
class StreamToLogger(object):
	"""
	Fake file-like stream object that redirects writes to a logger instance.
	"""
	def __init__(self, logger, log_level=logging.INFO):
		self.logger = logger
		self.log_level = log_level
		self.linebuf = ''

	def write(self, buf):
		for line in buf.rstrip().splitlines():
			self.logger.log(self.log_level, line.rstrip())

	def flush(self):
		self.logger.handlers[0].flush()
			
	
gColdCounter = GlobalCounter()
gHotCounter = GlobalCounter()
		
def send_warning_email(num_files):

	cnt_logger.warning('Send warning email: %d files', num_files)

	domain = 'inbox.ru'
	answers = dns.resolver.query(domain, 'MX')
	mailhost = ''

	for x in answers[0].exchange:
		if len (x) > 0:
			if len(mailhost) > 0:
				mailhost += '.'
			mailhost += x
		
	cnt_logger.debug('Mail host for domain %s is %s', domain, mailhost)
	
	sender = "ar_info@inbox.ru"
	receivers = ["ar_info@inbox.ru"]

	subject = "Counter warning: " + str(num_files) + " files are waiting"

	text = "This message was sent with Python's smtplib."

	message = """\
From: Counters <%s>
To: %s
Subject: %s

%s
""" % (sender, ", ".join(receivers), subject, text)

	s = smtplib.SMTP(mailhost)
	s.sendmail(sender, receivers, message)
	s.quit()
		
	
def check_internet_alive():

	try:
		r1 = urllib.request.urlopen('http://yandex.ru', timeout=HTTP_REQUEST_TIMEOUT)	
		r2 = urllib.request.urlopen('http://google.com', timeout=HTTP_REQUEST_TIMEOUT)
	except:
		return False
	
	if (r1.getcode() != 200) and (r2.getcode() != 200):
		return False
	
	return True
	

def email_thread():

	while True:
		
		files = os.listdir(temp_files_path)
		
		if (len(files) > 0):
		
			earliest = time.time()
		
			for f in files:
				if earliest > os.path.getctime(temp_files_path+f):
					earliest = os.path.getctime(temp_files_path+f)
						
			if (earliest + SEND_EMAIL_TIMEOUT < time.time()) and (check_internet_alive()):
				send_warning_email(len(files))
				
		time.sleep(SEND_EMAIL_TIMEOUT)

	
def report_string_to_server(report_str):	

	status = True
	cnt_logger.debug('report_string_to_server: string: %s', report_str)
	
	try:
		r = urllib.request.urlopen(report_str, timeout=HTTP_REQUEST_TIMEOUT)
		cnt_logger.debug('report_string_to_server: status: %d', r.getcode())
		if r.getcode() != 200:
			cnt_logger.warning('report_string_to_server: status: %d', r.status_code)
			status = False
	except urllib.error.URLError as e:	
		cnt_logger.warning('report_string_to_server: urllib.request exception: %s', e.reason)
		status = False
	except http.client.HTTPException as e:	
		cnt_logger.warning("report_string_to_server: HTTP exception: %s", e.reason)
		status = False
	except socket.timeout as e:	
		cnt_logger.warning('report_string_to_server: Socket timeout')
		status = False
		
	
	return status
	
	
def save_send(counter, counter_type):

	time_str = str(time.time())

	if counter_type == 'H':
		cnt_logger.debug('save_send: Hot counter')
		report_str = 'http://raevsky.com/counters/report_counter.php?time=' + time_str + '&counter=H&value=' + str(counter)
		temp_file_name = temp_files_path + 'H' + time_str	
	elif counter_type == 'C':
		cnt_logger.debug('save_send: Cold counter')
		report_str = 'http://raevsky.com/counters/report_counter.php?time=' + time_str + '&counter=C&value=' + str(counter)
		temp_file_name = temp_files_path + 'C' + time_str	
	else:
		cnt_logger.warning('save_send: Counter type unrecognized - internal error!')
		return
		
	status = report_string_to_server(report_str)
		
	if not status:
		cnt_logger.warning('save_send: Send counter to server error. Save to file.')
		f = open(temp_file_name, 'w')
		f.write(report_str)
		f.close()
	else:
		files = os.listdir(temp_files_path)
				
		if (len(files) > 0) and (check_internet_alive()):
				
			for file in files:
				temp_file_name = temp_files_path + file
				f = open(temp_file_name, 'r')
				report_str = f.readline()
				f.close()
				status = report_string_to_server(report_str)
				if status:
					os.remove(temp_file_name)
				else:
					cnt_logger.warning('save_send: Error sending file %s to server.', temp_file_name)
							
				
	
	
def save_send_thread():

	cnt_logger.debug('save_send_thread: Starting save_send_thread')

	if not os.path.exists(temp_files_path):
		os.makedirs(temp_files_path)

	while True:
	
		cold_counter = gColdCounter.InterlockedGetAndExchange(0)
		hot_counter = gHotCounter.InterlockedGetAndExchange(0)
	
		if cold_counter != 0:
			save_send(cold_counter, 'C')
	
		if hot_counter != 0:
			save_send(hot_counter, 'H')

		time.sleep(FILE_SAVE_SEND_TIMEOUT)



def counter_thread(pin, puk):

	global gColdCounter, gHotCounter

	while True:
		input_state = GPIO.input(pin)
		if input_state == False:
			if pin == PIN_COUNTER_COLD:
				x = gColdCounter.InterlockedIncrement()
				cnt_logger.debug('Cold counter: %d', x)
			else:
				x = gHotCounter.InterlockedIncrement()
				cnt_logger.debug('Hot counter: %d', x)
			
			while input_state == False:
				input_state = GPIO.input(pin)
				if input_state == True:
					time.sleep(0.5)
					input_state = GPIO.input(pin)
				time.sleep(0.2)
			
		time.sleep(0.5)


class MyDaemon(daemon):
	def run(self):
		
		t1 = threading.Thread(target=counter_thread, args=(PIN_COUNTER_HOT, 0))
		t1.start()
		
		t2 = threading.Thread(target=counter_thread, args=(PIN_COUNTER_COLD, 0))
		t2.start()
		
		t3 = threading.Thread(target=save_send_thread)
		t3.start()
	
		t4 = threading.Thread(target=email_thread)
		t4.start()
		
		while True:
			time.sleep(1)
			
	def start(self):

		GPIO.setmode(GPIO.BCM)
		GPIO.setup(PIN_COUNTER_COLD, GPIO.IN, pull_up_down=GPIO.PUD_UP)
		GPIO.setup(PIN_COUNTER_HOT, GPIO.IN, pull_up_down=GPIO.PUD_UP)
		
		# Set up a specific logger with desired output level
		cnt_logger.setLevel(LOG_LEVEL)
		loghandler = logging.handlers.RotatingFileHandler(LOG_FILENAME, maxBytes=32*1024, backupCount=4)
		logformatter = logging.Formatter(fmt='%(asctime)s  %(message)s', datefmt='%b %d %H:%M:%S')
		loghandler.setFormatter(logformatter)
		cnt_logger.addHandler(loghandler)

		cnt_logger.info('Start daemon')
		
		daemon.start(self)

		print ("redirect ...")
		sl = StreamToLogger(cnt_logger, logging.ERROR)
		sys.stderr = sl

			
			
if __name__ == "__main__":
		
	gpio_daemon = MyDaemon('/tmp/gpio-daemon.pid')
	if len(sys.argv) == 2:
			if 'start' == sys.argv[1]:
				gpio_daemon.start()
			elif 'stop' == sys.argv[1]:
				gpio_daemon.stop()
			elif 'restart' == sys.argv[1]:
				gpio_daemon.restart()
			else:
				print ("Unknown command")
				sys.exit(2)
			sys.exit(0)
	else:
			print ("usage: %s start|stop|restart" % sys.argv[0])
			sys.exit(2)
