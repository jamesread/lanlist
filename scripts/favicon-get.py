#!/usr/bin/env python

"""
Fetch organizer favicons; writes at most one logs row per organizer when a fetch runs
(skip when ICO already on disk logs nothing, unless organisers.faviconRefetch requests a refetch).
Bulk runs only process organizers with faviconRefetch=1; that flag is cleared after a successful
fetch or when the site is unreachable (timeout, SSL error, HTTP 403/404/500) or HTML has no usable
rel=icon (so the next crawl skips them until refetch is requested).
Requires MYSQL_USER / MYSQL_PASS; optional MYSQL_HOST (default localhost), MYSQL_DATABASE (default lanlist).
"""

import mysql.connector
import os
import re
import requests
from urllib.parse import urlparse, urljoin
from bs4 import BeautifulSoup
import configargparse

parser = configargparse.ArgParser(default_config_files=['./favicon-get.conf'])
parser.add_argument('--org-id', help='The id of the organizer to get the favicon for. If not set, will get favicons for all organizers in the database.')
args = parser.parse_args()

LOG_EVENT_TYPE_FAVICON = 'FAVICON_FETCH'
NO_USABLE_REL_ICON = 'no usable rel=icon link'
CLEAR_REFETCH_HTTP_STATUSES = frozenset({403, 404, 500})


def failure_clears_favicon_refetch(detail_direct, detail_html):
    """True when fetch failure looks permanent (no point retrying on the next crawl)."""
    if detail_html == NO_USABLE_REL_ICON:
        return True

    combined = f'{detail_direct or ""} {detail_html or ""}'.lower()
    if 'timeout' in combined or 'timed out' in combined:
        return True
    if 'ssl' in combined or 'certificate' in combined:
        return True

    for detail in (detail_direct, detail_html):
        if not detail:
            continue
        text = str(detail)
        for status in CLEAR_REFETCH_HTTP_STATUSES:
            if re.search(rf'\bHTTP {status}\b', text, re.IGNORECASE):
                return True
            if re.search(rf'\b{status} client error\b', text, re.IGNORECASE):
                return True
            if re.search(rf'\b{status} server error\b', text, re.IGNORECASE):
                return True

    return False


def isUsableFaviconContentType(content_type):
    if not content_type or not str(content_type).strip():
        return False
    mime = content_type.split(';')[0].strip().lower()
    if not mime.startswith('image/'):
        return False
    return True


def _request_get(url):
    try:
        return requests.get(url, timeout=20), None
    except requests.RequestException as ex:
        return None, str(ex)


def resolve_fetch_url(base_url, href):
    if isAbsolute(href):
        return href
    if base_url and str(base_url).strip():
        return urljoin(str(base_url).strip(), href)
    return href


def downloadFavicon(href, base_url, org_id):
    fetch_url = resolve_fetch_url(base_url, href)

    print(f'\tdl {fetch_url}')
    res, err = _request_get(fetch_url)
    if err:
        print(f'\trequest error from {fetch_url}: {err}')
        return False, f'request error: {err}'

    print(f'\t{res.status_code} from {fetch_url}')
    if res.status_code != 200:
        return False, f'HTTP {res.status_code}'

    ct = res.headers.get('Content-Type', '')
    if not isUsableFaviconContentType(ct):
        print(f"\treject Content-Type {ct!r} (expected image/*)")
        return False, f'non-image Content-Type {ct!r}'

    out_name = str(org_id) + '.ico'
    with open(out_name, 'wb') as fh:
        fh.write(res.content)
    return True, f'saved {out_name}'


def isAbsolute(url):
    return urlparse(url).netloc != ''


def rel_contains_icon(rel):
    """True if relation indicates a favicon (matches prior behaviour for link rel lists)."""
    if rel is None:
        return False
    seq = rel if isinstance(rel, (list, tuple)) else [rel]
    for x in seq:
        if 'icon' in str(x).lower():
            return True
    return False


def findFavicon(site, org_id):
    print('\tTrying to find favicon by parsing html')
    try:
        res, err = _request_get(site)
        if err:
            return False, err
        res.raise_for_status()
    except Exception as ex:
        return False, f'homepage fetch failed: {ex}'

    soup = BeautifulSoup(res.content, features='lxml')

    for link in soup.find_all('link'):
        rel = link.get('rel')
        if not rel_contains_icon(rel):
            continue
        href = link.get('href')
        if not href:
            continue
        print(f'\tFound a favicon link: {href!r}')
        ok, detail = downloadFavicon(href, site, org_id)
        if ok:
            return True, f'via HTML link ({detail})'
        print(f'\tSkipping link after failed fetch: {detail}')

    print('\tCould not find a usable favicon in HTML')
    return False, NO_USABLE_REL_ICON


def write_favicon_log(cursor, conn, priority, organizer_id, website_url, summary):
    """One INSERT per call; callers avoid calling when ICO already exists locally."""
    url_part = ''
    if website_url is not None and str(website_url).strip() != '':
        u = str(website_url).strip()
        if len(u) > 400:
            u = u[:397] + '...'
        url_part = f' website={u!s}'
    content = (f'Favicon fetch organizer id={organizer_id}{url_part}: {summary}').strip()
    content = content[:2048]

    cursor.execute(
        """INSERT INTO logs (priority, content, eventType, relatedOrganizer, timestamp)
           VALUES (%s, %s, %s, %s, NOW())""",
        (priority, content, LOG_EVENT_TYPE_FAVICON, int(organizer_id)),
    )
    conn.commit()


def clear_favicon_refetch_flag(cursor, conn, org_id):
    cursor.execute('UPDATE organizers SET faviconRefetch = 0 WHERE id = %s', (org_id,))
    conn.commit()


def process_organizer_row(cursor, conn, row):
    favicon_refetch = row[2] if len(row) >= 3 else 0
    website_url = row[0]
    org_id = row[1]
    want_refetch = int(favicon_refetch) == 1

    print(f'Getting id={org_id} website={website_url!r} faviconRefetch={int(want_refetch)}')

    if website_url is None or str(website_url).strip() == '':
        write_favicon_log(cursor, conn, 'WARN', org_id, website_url, 'skipped: missing website URL')
        return

    filename = str(org_id) + '.ico'

    forced_prefix = '[forced refetch] ' if want_refetch else ''

    try:
        if want_refetch and os.path.exists(filename):
            os.unlink(filename)
            print('\tForced refetch: deleted existing ICO')

        if os.path.exists(filename):
            print('\tAlready exists!')
            return

        ok_direct, detail_direct = downloadFavicon('/favicon.ico', website_url, org_id)

        if ok_direct:
            clear_favicon_refetch_flag(cursor, conn, org_id)
            write_favicon_log(cursor, conn, 'INFO', org_id, website_url, f'{forced_prefix}ok /favicon.ico ({detail_direct})')
            return

        ok_html, detail_html = findFavicon(website_url, org_id)
        if ok_html:
            clear_favicon_refetch_flag(cursor, conn, org_id)
            write_favicon_log(cursor, conn, 'INFO', org_id, website_url, f'{forced_prefix}ok {detail_html}')
            return

        summary = f'{forced_prefix}failed: /favicon.ico ({detail_direct}); HTML ({detail_html})'
        if failure_clears_favicon_refetch(detail_direct, detail_html):
            clear_favicon_refetch_flag(cursor, conn, org_id)
            summary += '; cleared faviconRefetch (no retry on next crawl)'
        write_favicon_log(cursor, conn, 'WARN', org_id, website_url, summary)

    except Exception as ex:
        print('\tExcept', ex, website_url)
        write_favicon_log(cursor, conn, 'ERROR', org_id, website_url, f'exception: {ex}')


mydb = mysql.connector.connect(
    host=os.environ.get('MYSQL_HOST', 'localhost'),
    user=os.environ['MYSQL_USER'],
    password=os.environ['MYSQL_PASS'],
    database=os.environ.get('MYSQL_DATABASE', 'lanlist'),
)

cur_select = mydb.cursor()

if args.org_id:
    cur_select.execute(
        'SELECT o.websiteUrl, o.id, COALESCE(o.faviconRefetch, 0) FROM organizers o WHERE o.id = %s',
        (args.org_id,),
    )
else:
    cur_select.execute(
        'SELECT o.websiteUrl, o.id, COALESCE(o.faviconRefetch, 0) FROM organizers o WHERE COALESCE(o.faviconRefetch, 0) = 1',
    )

rows = cur_select.fetchall()
cur_select.close()

cur_write = mydb.cursor()
try:
    for row in rows:
        process_organizer_row(cur_write, mydb, row)
finally:
    cur_write.close()

mydb.close()
