#! /bin/python

import json
import urllib2
from random import random
from time import sleep, gmtime, strftime
from threading import Thread
from argparse import ArgumentParser


def get_url(baseurl, guid):
	"""
	Trigger the update of the arlearngame identified by 'guid' through an HTTP GET request.
	"""
	url = "%s/wespot_arlearn/update?guid=%d" % (baseurl, guid)
	# Wait from 0 to 60 seconds (not to overload the server with simultaneous requests)
	sleep(random()*60) 
	print "Requesting " + url
	urllib2.urlopen(url).read()


def update_games(baseurl):
	print "UPDATE START:", strftime("%a, %d %b %Y %H:%M:%S +0000", gmtime())
	if baseurl.endswith("/"):
		baseurl = baseurl[:-1]  # Always ending without final slash

    	# Get the GUIDs of all the arlearngames.
	result = urllib2.urlopen("%s/wespot_arlearn/update" % baseurl).read()
	guids = json.loads(result)

	# Trigger an update for each arlearngame.
	daemons = []
	for guid in guids:
		d = Thread(target=get_url, args=(baseurl, guid), name="Update_%d" % guid)
		d.setDaemon(True)
		d.start()
		daemons.append(d)

	# Waits until all the requests have been made (i.e., all the daemons have finished).
	for daemon in daemons:
		daemon.join()

	print "UPDATE END:", strftime("%a, %d %b %Y %H:%M:%S +0000", gmtime())


def main():
    parser = ArgumentParser("Trigger an update for each arlearn game in a 'wespot_arlearn' instance ('wespot_arlearn' is an Elgg plugin).")
    parser.add_argument('-u','--url', default="http://fake.wespot.org", dest="url",
    	help="Specify the base url for an Elgg instance which has the wespot_arlearn plugin installed.")
    args = parser.parse_args()
    update_games(args.url)


if __name__ == '__main__':
    main()
