#!/usr/bin/env python

import sys,re,urllib.request,json,fileinput
from prettytable import PrettyTable

def replaceAll(file,searchExp,replaceExp):
    for line in fileinput.input(file, inplace=1):
        if searchExp in line:
            line = line.replace(searchExp,replaceExp)
        sys.stdout.write(line)

filename = sys.argv[1];
updates = PrettyTable(['Dependency', 'Old Version', 'New Version'])
for line in open(filename).read().split("\n"):
	if "cdnjs" in line:
		dependency = re.match("(?:.+)\/ajax\/libs\/([a-z\-\.]+)\/([0-9a-zA-Z\.\-]+)\/", line);
		if dependency:
			dep_name = dependency.groups()[0];
			dep_version = dependency.groups()[1];
			with urllib.request.urlopen("https://api.cdnjs.com/libraries/"+dep_name+"?fields=name,version") as url:
				data = json.loads(url.read().decode())
				dep_version_new = data["version"]
				if dep_version != dep_version_new:
					updatedLine = line.replace(dep_version,dep_version_new)
					replaceAll(filename, line, updatedLine)
					updates.add_row([dep_name, dep_version, dep_version_new])

	if "use.fontawesome" in line:
		dependency = re.match("(?:.+)\/releases\/([0-9a-zA-Z\.\-]+)\/", line);
		if dependency:
			dep_name = "fontawesome";
			dep_version = dependency.groups()[0];
			with urllib.request.urlopen("https://api.github.com/repos/FortAwesome/Font-Awesome/releases/latest") as url:
				data = json.loads(url.read().decode())
				dep_version_new = "v"+data["tag_name"]
				if dep_version != dep_version_new:
					updatedLine = line.replace(dep_version,dep_version_new)
					replaceAll(filename, line, updatedLine)
					updates.add_row([dep_name, dep_version, dep_version_new])
		
print(updates)