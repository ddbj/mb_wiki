# mediawiki extensions
- CreateGraph - Create and display graphs, powered by jpgraph.
- ExtendedFunctions - Flexible and powerful parser functions.
- HTMLFormFunctions - Enable HTML form-related tags(form, input, textarea, etc).

# Requirements
- php 8
- mediawiki 1.39
- jpgraph (Operation confirmed with version 4.4.1)

# Installation
```
$ git clone git clone https://github.com/ddbj/mb_wiki.git  ### download extensions
$ mv mb_wiki/extensions/* /WIKI_HOME/extensions/
$ wget jpgraph.tgz      ### download jpgraph
$ tar zxf jpgraph.tgz
$ mv jpgraph-X.X.X/ /DOCUMENT_ROOT/jpgraph/
$ cd /WIKI_HOME/        ### settings
$ vi LocalSettings.php  ### add the following lines
wfLoadExtension( 'CreateGraph' );
wfLoadExtension( 'ExtendedFunctions' );
wfLoadExtension( 'HTMLFormFunctinos' );
$ mkdir scripts/
$ mv extensions/CreateGraph/graph/ scripts/
```

# Usage
See below.
- [CreateGraph document](http://metabolomics.jp/wiki/Help:Extension/CreateGraph)
- [ExtendedFunctions document](http://metabolomics.jp/wiki/Help:Extension/ExtendedFunctions)
- [HTMLForm document](http://metabolomics.jp/wiki/Help:Extension/HTMLForm)
