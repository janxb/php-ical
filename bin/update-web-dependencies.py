#!/usr/bin/env python

import os,sys,re,urllib.request,json,fileinput
from prettytable import PrettyTable

def replaceLinesInFile(file,searchExp,replaceExp):
    for line in fileinput.input(file, inplace=1):
        if searchExp in line:
            line = line.replace(searchExp,replaceExp)
        sys.stdout.write(line)

def updateDependency(name, oldVersion, newVersion, line, filename, updates):
	if oldVersion != newVersion:
		updatedLine = line.replace(oldVersion,newVersion)
		replaceLinesInFile(filename, line, updatedLine)
		updates.add_row([name, oldVersion, newVersion])

filename = os.path.dirname(os.path.realpath(__file__))+"/../templates/base.html.twig";
updates = PrettyTable(['Dependency', 'Old Version', 'New Version'])
for line in open(filename).read().split("\n"):
	if "cdnjs" in line:
		dependency = re.match("(?:.+)\/ajax\/libs\/([a-z\-\.]+)\/([0-9a-zA-Z\.\-]+)\/", line);
		if dependency:
			dep_name = dependency.groups()[0];
			dep_version = dependency.groups()[1];
			with urllib.request.urlopen("https://api.cdnjs.com/libraries/"+dep_name+"?fields=name,version") as url:
				data = json.loads(url.read().decode())
				updateDependency(dep_name, dep_version, data["version"], line, filename, updates)

	if "use.fontawesome" in line:
		dependency = re.match("(?:.+)\/releases\/([0-9a-zA-Z\.\-]+)\/", line);
		if dependency:
			dep_name = "fontawesome";
			dep_version = dependency.groups()[0];
			with urllib.request.urlopen("https://api.github.com/repos/FortAwesome/Font-Awesome/releases/latest") as url:
				data = json.loads(url.read().decode())
				updateDependency(dep_name, dep_version, "v"+data["tag_name"], line, filename, updates)
		
print(updates)