#!/usr/bin/env python

import sys, time, threading, os
from daemon3x import daemon
import RPi.GPIO as GPIO
import smtplib, requests
import dns.resolver

PIN_COUNTER_HOT  = 13;
PIN_COUNTER_COLD = 5;

FILE_SAVE_SEND_TIMEOUT = 5;

SEND_EMAIL_TIMEOUT = 24 * 60 * 60 * 60;

#SEND_EMAIL_TIMEOUT = 50;


NUM_FILE_LIMIT_FOR_EMAIL = SEND_EMAIL_TIMEOUT / FILE_SAVE_SEND_TIMEOUT;

temp_files_path = "/var/local/counters/";

gHotCounter = 0;
gColdCounter = 0;

gOldHotCounter = 0;
gOldColdCounter = 0;

	
def send_warning_email(num_files):

	print "send_warning_email: " + str(num_files)

	domain = 'inbox.ru';
	answers = dns.resolver.query(domain, 'MX');
	mailhost = '';

	for x in answers[0].exchange:
		if len (x) > 0:
			if len(mailhost) > 0:
				mailhost += '.';
			mailhost += x;
		
	print domain + " " + mailhost; 
	
	sender = "ar_info@inbox.ru";
	receivers = ["ar_info@inbox.ru"];

	subject = "Counter warning: " + str(num_files) + " files are waiting"

	text = "This message was sent with Python's smtplib."

	message = """\
From: Counters <%s>
To: %s
Subject: %s

%s
""" % (sender, ", ".join(receivers), subject, text);

	s = smtplib.SMTP(mailhost);
	s.set_debuglevel(1);
	s.sendmail(sender, receivers, message);
	s.quit();
		
	
def check_internet_alive():

	r1 = requests.get("http://yandex.ru");	
	r2 = requests.get("http://google.com");
	
	if (r1.status_code != 200) and (r2.status_code != 200):
		return False;
	
	return True;
	

def email_thread():

	print "start email thread";

	while True:
		
		files = os.listdir(temp_files_path);
		
		if (len(files) > 0):
		
			earliest = time.time();
		
			for f in files:
				if earliest > os.path.getctime(temp_files_path+f):
					earliest = os.path.getctime(temp_files_path+f);
			
			print "earliest: " + str(earliest);
			
			if (earliest + SEND_EMAIL_TIMEOUT < time.time()) and (check_internet_alive()):
				send_warning_email(len(files));
				
		time.sleep(SEND_EMAIL_TIMEOUT);

	
def save_send(counter, counter_type):

	time_str = str(time.time());

	if counter_type == 'H':
		report_str = 'http://raevsky.com/counters/report_counter.php?time=' + time_str + '&counter=H&value=' + str(counter);
		temp_file_name = temp_files_path + 'H' + time_str;	
	elif counter_type == 'C':
		report_str = 'http://raevsky.com/counters/report_counter.php?time=' + time_str + '&counter=C&value=' + str(counter);
		temp_file_name = temp_files_path + 'C' + time_str;		
	else:
		print "save_send invalid parameter!";
		return;
		
	r = requests.get(report_str);
			
	if r.status_code != 200:
		print "Error report, code is " + str(r.status_code) + ". Save to file."
		print r.text;
		print temp_file_name;
		f = open(temp_file_name, 'w');
		f.write(report_str);
		f.close();
	else:
		files = os.listdir(temp_files_path);
				
		if (len(files) > 0) and (check_internet_alive()):
				
			for file in files:
				print "report file " + file;
				temp_file_name = temp_files_path + file;
				f = open(temp_file_name, 'r');
				report_str = f.readline();
				f.close();
				r = requests.get(report_str);
				if r.status_code == 200:
					os.remove(temp_file_name);
				else:
					print str(r.status_code);
					print r.text;
							
				
	
	
def save_send_thread():

	global gColdCounter, gHotCounter;

	if not os.path.exists(temp_files_path):
		os.makedirs(temp_files_path)

	while True:
	
		if gColdCounter != 0:
			save_send(gColdCounter, 'C');
			gColdCounter = 0;
		
		if gHotCounter != 0:
			save_send(gHotCounter, 'H');
			gHotCounter = 0;
		
		time.sleep(FILE_SAVE_SEND_TIMEOUT);



def counter_thread(pin, puk):

	global gColdCounter, gHotCounter;

	while True:
		input_state = GPIO.input(pin)
		if input_state == False:
			if pin == PIN_COUNTER_COLD:
				print "cold";
				gColdCounter += 1;
			else:
				print "hot";
				gHotCounter += 1;
			
			print('peton %d!' % pin);
			while input_state == False:
				input_state = GPIO.input(pin);
				time.sleep(0.2);


class MyDaemon(daemon):
	def run(self):
		print "run";
		
		t1 = threading.Thread(target=counter_thread, args=(PIN_COUNTER_HOT, 0));
		t1.start();
		
		t2 = threading.Thread(target=counter_thread, args=(PIN_COUNTER_COLD, 0));
		t2.start();
		
		t3 = threading.Thread(target=save_send_thread);
		t3.start();
		
		t4 = threading.Thread(target=email_thread);
		t4.start();
		
		while True:
			time.sleep(1)
		print "bye bye"
			
	def start(self):
		print "start";

		GPIO.setmode(GPIO.BCM)
		GPIO.setup(PIN_COUNTER_COLD, GPIO.IN, pull_up_down=GPIO.PUD_UP)
		GPIO.setup(PIN_COUNTER_HOT, GPIO.IN, pull_up_down=GPIO.PUD_UP)
		
		daemon.start(self)

			
			
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
				print "Unknown command"
				sys.exit(2)
			sys.exit(0)
	else:
			print "usage: %s start|stop|restart" % sys.argv[0]
			sys.exit(2)
