#!/usr/bin/env python

import sys, time, threading
from daemon3x import daemon
import RPi.GPIO as GPIO

PIN_COUNTER_HOT  = 13
PIN_COUNTER_COLD = 5

gStartTime = time.time();
print gStartTime;
print time.gmtime(gStartTime);
print time.localtime(gStartTime);

gHotCounter = 0;
gColdCounter = 0;

def save_send_thread:



def counter(pin, puk):
	while True:
		input_state = GPIO.input(pin)
		if input_state == False:
			if pin == PIN_COUNTER_COLD:
				gColdCounter++;
			else:
				gHotCounter++;
			
			print('peton %d!' % pin)
			while input_state == False:
				input_state = GPIO.input(pin)
				time.sleep(0.2)


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
