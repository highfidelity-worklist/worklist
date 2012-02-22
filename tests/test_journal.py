#!/usr/bin/env python

# Copyright (c) 2009-2010, LoveMachine Inc.
# All Rights Reserved.
# http://www.lovemachineinc.com
"""
Integration Tests for Journal

These tests can and should be run on every deploy of the code to
make sure that code quality and performance are headed in the
right direction

Author: Alex Lovell-Troy (alex@lovelltroy.org)
Date: Mon Dec  6 07:56:21 UTC 2010
"""

import datetime, time
import sys
import urllib, urllib2
import uuid

def journal_message(message):
    journal_req = urllib2.Request('https://dev.sendlove.us/journal/add.php')
    journal_data = urllib.urlencode({'user' : 'api_svn@dev.sendlove.us', 'message' : message})
    journal_response = urllib2.urlopen(journal_req, journal_data).read()
    if journal_response.rfind('ok') != -1:
        print "Journal message sent"
    else:
        print "Journal message failed"

class TimingTest(object):
    """
    An object to hold state throughout the timing test.
    """
    journal_url = 'https://dev.sendlove.us/journal/'
    referer_url = journal_url
    journal_user = 'api_svn@dev.sendlove.us'
    _csrf_token = None

    def __init__(self):
        # Generate a uuid that we'll use to know that this is *our* test
        self.test_instance = str(uuid.uuid4())
        # Set up urllib2 for cookies
        self.opener = urllib2.build_opener(urllib2.HTTPCookieProcessor())
        self.opener.addheaders = [('Referer', self.referer_url)]
        urllib2.install_opener(self.opener)

    def csrf_token(self):
        if self._csrf_token != None:
            return self._csrf_token
        else:
            # Let's go and get one!
            f = self.opener.open(self.journal_url)
            data = f.read()
            f.close()
            # The csrf Token is on a line in the javascript that looks like this:
            # var csrf_token = '6dc6b719428d5cd882cf15e02743f3de';
            for line in data:
                if line.lstrip().startswith('var csrf_token'):
                    junk, csrf_token, junk = line.split("'")
                    self._csrf_token = csrf_token
                    return self._csrf_token
            # If we've broken out of the loop without ending, we couldn't get a csrf token.  how strange.

    def fetch_latest_messages(self, count):
        post_data = "what=latest_longpoll&timeout=30&count=%s" % count
        f = self.opener.open("%saj.php" % self.journal_url, post_data)
        raw = f.read()
        f.close()
        return raw

def run_timing_test(threshold=None):
    """
    publish something to the journal and then test
    to see that it responds within the passed in
    threshold which is a floating point number in
    seconds.
    """

    test_iteration = str(uuid.uuid4())
    test1 = TimingTest()
    # Send the test message
    start_time = datetime.datetime.now()
    journal_message("Don't mind me, I'm a test message for calibrating Journal performance. (%s)" % test_iteration)
    while datetime.datetime.now() - start_time < datetime.timedelta(seconds=threshold):
        # Load the journal page and make sure that it shows up
        messages = test1.fetch_latest_messages(3)
        if test_iteration in messages:
            found_time = datetime.datetime.now() - start_time
            return str(found_time).split(':')[-1]
    return False

if __name__ == "__main__":
    revision = sys.argv[1] #passed in at the commandline
    threshold = 0.5 #in seconds
    found_time =  run_timing_test(threshold=threshold)
    if found_time != False:
        journal_message("Revision %s of the Journal printed the test message in %s seconds." % (str(revision), str(found_time)))
    else:
        journal_message("Revision %s of the Journal failed to deliver a test message in %s seconds." % (str(revision), str(threshold)))
