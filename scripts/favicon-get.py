#!/usr/bin/env python

import mysql.connector
import os
import requests
from urllib.parse import urlparse 
from bs4 import BeautifulSoup
import configargparse

parser = configargparse.ArgParser(default_config_files=['./favicon-get.conf'])
parser.add_argument('--org-id', help='The id of the organizer to get the favicon for. If not set, will get favicons for all organizers in the database.')
args = parser.parse_args()


def isUsableFaviconContentType(content_type):
    if not content_type or not str(content_type).strip():
        return False
    mime = content_type.split(';')[0].strip().lower()
    if not mime.startswith('image/'):
        return False
    # e.g. image/svg+xml, image/png, image/x-icon, image/vnd.microsoft.icon
    return True


def downloadFavicon(url, site, orgId):
    if site != "":
        url = site + "" + url

    print(f"\tdl {site}{url}")

    res = requests.get(url)
        
    print(f"\t{res.status_code} from {url}")

    if res.status_code != 200:
        return False

    ct = res.headers.get('Content-Type', '')
    if not isUsableFaviconContentType(ct):
        print(f"\treject Content-Type {ct!r} (expected image/*, not HTML or other non-image types)")
        return False

    with open(str(orgId) + '.ico', 'wb') as f:
        f.write(res.content)

    return True

def isAbsolute(url):
    return urlparse(url).netloc != ""

def findFavicon(site, orgId):
    print("\tTrying to find favicon by parsing html")

    res = requests.get(site)
    res.raise_for_status()

    soup = BeautifulSoup(res.content, features = "lxml")
    
    for link in soup.find_all('link'):
        if "icon" in link['rel']:

            print(f"\tFound a favicon! {site}{link['href']}")

            if isAbsolute(link['href']):
                site = ""

            downloadFavicon(link['href'], site, orgId)

            return

    print ("\tCould not find a favicon")

mydb = mysql.connector.connect(
    host = 'localhost',
    user = os.environ['MYSQL_USER'],
    password = os.environ['MYSQL_PASS'],
    database = 'lanlist',
)

cur = mydb.cursor()

if args.org_id:
    cur.execute('SELECT o.websiteUrl, o.id FROM organizers o WHERE o.id = %s', (args.org_id,))
else:
    cur.execute('SELECT o.websiteUrl, o.id FROM organizers o')

print("cur", cur)

for row in cur:
    try: 
        print(f"Getting {row}")

        filename = str(row[1]) + '.ico'

        if os.path.exists(filename):
            print("\tAlready exists!")
            continue

        if not downloadFavicon('/favicon.ico', row[0], row[1]):
            findFavicon(row[0], row[1])

    except Exception as e:
        print("\tExcept", e, row[0])
