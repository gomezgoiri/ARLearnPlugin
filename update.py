#! /bin/python

import json
import urllib2
from random import random
from time import sleep
from threading import Thread


def get_url(guid):
	"""
	Trigger the update of the arlearngame identified by 'guid' through an HTTP GET request.
	"""
	url = "http://fake.wespot.org/wespot_arlearn/update?guid=%d" % guid
	# Wait from 0 to 60 seconds (not to overload the server with simultaneous requests)
	sleep(random()*60) 
	print "Requesting " + url
	urllib2.urlopen(url).read()


# Get the GUIDs of all the arlearngames.
result = urllib2.urlopen("http://fake.wespot.org/wespot_arlearn/update").read()
guids = json.loads(result)

# Trigger an update for each arlearngame.
daemons = []
for guid in guids:
	d = Thread(target=get_url, args=(guid,), name="Update_%d" % guid)
	d.setDaemon(True)
	d.start()
	daemons.append(d)

# Waits until all the requests have been made (i.e., all the daemons have finished).
for daemon in daemons:
	d.join()


print "Updates finished."