#! /bin/python

import os
import json
import inspect
import urllib2
import datetime
from random import random
from time import sleep, gmtime, strftime
from threading import Thread
from argparse import ArgumentParser


def is_previous_update_still_running(url):
	current_pid = str(os.getpid())
	current_script = inspect.getfile(inspect.currentframe())
	ret = os.popen("ps xeo \"%p %a\"", "r")
	l = ret.readline()
	while (l):
		pieces = l.split()
		if pieces[0]!=current_pid and len(pieces)>4:
			if pieces[1]=="python" and pieces[2]==current_script and \
				# Other server's info might be updated from this server...
				pieces[3]=="-u" and pieces[4]==url:
				return True
		l = ret.readline()
	return False


def get_url(baseurl, guid, waitFor=0):
	"""
	Trigger the update of the arlearngame identified by 'guid' through an HTTP GET request.
	"""
	url = "%s/wespot_arlearn/update?guid=%d" % (baseurl, guid)
	# Wait from 0 to 60 seconds (not to overload the server with simultaneous requests)
	if (waitFor>0):
		sleep(waitFor)
	print "Requesting " + url
	start = datetime.datetime.now()
	urllib2.urlopen(url).read()
	diff = datetime.datetime.now() - start
	print "  (it took %d ms approximately)" % round(diff.microseconds / 1000)


def update_games(baseurl, sequential):
	init_time = datetime.datetime.now()
	print "UPDATE START:", strftime("%a, %d %b %Y %H:%M:%S +0000", gmtime())
	if baseurl.endswith("/"):
		baseurl = baseurl[:-1]  # Always ending without final slash

    	# Get the GUIDs of all the arlearngames.
	url = "%s/wespot_arlearn/update" % baseurl
	print "Requesting " + url
	result = urllib2.urlopen(url).read()
	guids = json.loads(result)

	# Trigger an update for each arlearngame.
	daemons = []
	for guid in guids:
		if (sequential):
			get_url(baseurl, guid)
		else:
			waitFor = random()*60
			d = Thread(target=get_url, args=(baseurl, guid, waitFor), name="Update_%d" % guid)
			d.setDaemon(True)
			d.start()
			daemons.append(d)

	# Waits until all the requests have been made (i.e., all the daemons have finished).
	for daemon in daemons:
		daemon.join()

	elapsed = datetime.datetime.now() - init_time
	print "UPDATE END (elapsed %ss):" % elapsed.seconds, strftime("%a, %d %b %Y %H:%M:%S +0000", gmtime())


def set_proxy():
        proxy = urllib2.ProxyHandler({'http':'wwwcache.open.ac.uk:80', 'https': 'wwwcache.open.ac.uk:80'})
        opener = urllib2.build_opener(proxy)
        urllib2.install_opener(opener)


def main():
	parser = ArgumentParser("Trigger an update for each arlearn game in a 'wespot_arlearn' instance ('wespot_arlearn' is an Elgg plugin).")
	parser.add_argument('-u','--url', default="http://fake.wespot.org", dest="url",
				help="Specify the base url for an Elgg instance which has the wespot_arlearn plugin installed.")
        parser.add_argument('-s','--seq', default=True, dest="sequential",
                                help="Should the HTTP requests to update runIds be run parallelly or sequentially.")
	args = parser.parse_args()
	#set_proxy()
	if is_previous_update_still_running(args.url):
		print "UPDATE CANCELLED (a previous update is still running):", strftime("%a, %d %b %Y %H:%M:%S +0000", gmtime())
	else:
		update_games(args.url, args.sequential)


if __name__ == '__main__':
    main()
