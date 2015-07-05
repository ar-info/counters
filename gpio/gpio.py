#!/usr/bin/env python

import sys, time, threading, os
from daemon3x import daemon
import RPi.GPIO as GPIO
import smtplib, requests
import dns.resolver

PIN_COUNTER_HOT  = 13;
PIN_COUNTER_COLD = 5;

FILE_SAVE_TIMEOUT = 5;
SERVER_SEND_TIMEOUT = 15 * 60;

temp_files_path = "/var/local/counters/";

gHotCounter = 0;
gColdCounter = 0;

gOldHotCounter = 0;
gOldColdCounter = 0;


	
def send_warning_email(num_files):

	domain = 'inbox.ru';
	answers = dns.resolver.query(domain, 'MX');
	mailhost = '';

	for x in answers[0].exchange:
		if len (x) > 0:
			if len(mailhost) > 0:
				mailhost += '.';
			mailhost += x;
		
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
	
	
send_warning_email(10);	
	
def check_internet_alive():

	r1 = requests.get("http://yandex.ru");	
	r2 = requests.get("http://google.com");
	
	if (r1 != 200) and (r2 != 200):
		return false;
	
	return true;
	
	
def save_send_thread():

	global gColdCounter, gHotCounter;

	if not os.path.exists(temp_files_path):
		os.makedirs(temp_files_path)

	while True:
	
		if gColdCounter != 0:
			report_str = 'http://raevsky.com/counters/report_counter.php?time=' + str(time.time()) + '&counter=COLD&value=' + str(gColdCounter);
			r = requests.get(report_str);
			
			if r.status_code != 200:
				temp_file_name = temp_files_path + 'C' + str(round(gColdCounter));
				f = open(temp_file_name, 'w');
				f.write(report_str);
				f.close();
			else:
				files = os.listdir();
				
				for file in files:
					temp_file_name = temp_files_path + file;
					f.open(temp_file_name, 'r');
					report_str = f.readline();
					f.close();
					r = requests.get(report_str);
					if r.status_code == 200:
						os.remove(temp_file_name);
					
			gColdCounter = 0;
		
		if gHotCounter != 0:
			time_str = str(time.time());
			report_str = 'http://raevsky.com/counters/report_counter.php?time=' + time_str + '&counter=HOT&value=' + str(gHotCounter);
			r = requests.get(report_str);
			
			if r.status_code != 200:
				print "Error report, code is " + str(r.status_code) + ". Save to file."
				temp_file_name = temp_files_path + 'H' + time_str;
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
							
				
				
					
					
			gHotCounter = 0;
		
		time.sleep(FILE_SAVE_TIMEOUT);



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
