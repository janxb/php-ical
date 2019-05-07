# PHP iCAL Viewer

## Parameters

### CALENDAR_LANGUAGE
Which language are the calendar events in?
### CALENDAR_URLS
The `.ics` URLs of your calendars. For multiple entries, input them comma-separated.
### CALENDAR_NAMES
The names for your calendars. For multiple entries, input them comma-separated.
The values have to be in the same order than `CALENDAR_URLS`
### CALENDAR_COLORS
The colors for your calendars. For multiple entries, input them comma-separated.
The values have to be in the same order than `CALENDAR_URLS`
### CALENDAR_PASSWORDS
For protecting access to the calendar, you can define multiple passwords.
When opening the page, a password popup is displayed. The password is stored
for a longer timeframe in a browser cookie. Multiple values can be defined comma-separated.
### CALENDAR_CACHE_TTL
How long should fetched `.ics` files be cached locally, until they are refreshed from
the remote source? Value is in seconds.
### APP_SECRET
A random string used by the Symfony framework.
### CALENDAR_PUBLIC_AVAILABILITY
Normally, when `CALENDAR_PASSWORDS` is set, the calendar can't be accessed by the public.
If you enable this option, a limited view with available / not available times is 
shown instead. This way you can communicate availability without leaking details.